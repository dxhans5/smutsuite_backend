<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class IdentityController extends Controller
{
    /**
     * Return all identities owned by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $identities = Identity::ownedBy($request->user()->id)
            ->with([
                'publicProfile:id,identity_id,is_visible',
                'privateProfile:id,identity_id',
            ])
            ->orderByDesc('is_active')
            ->get();

        return response()->json(['success' => true, 'data' => $identities]);
    }

    /**
     * Create a new identity (max 1 every 72h).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $last = Identity::ownedBy($user->id)->latest()->first();
        if ($last && now()->diffInHours($last->created_at) < 72) {
            return response()->json([
                'message' => __('identities.cooldown_active', ['hours' => 72 - now()->diffInHours($last->created_at)]),
            ], 422);
        }

        $validated = $request->validate([
            'alias' => [
                'required', 'string', 'max:255',
                Rule::unique('identities', 'alias')->where(fn ($q) => $q->where('user_id', $user->id)),
            ],
            'role' => ['required', 'string', 'in:user,creator,host,service_provider,admin'],
            'visibility_level' => ['nullable', 'string', 'in:public,members,hidden'],
        ]);

        $identity = Identity::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'alias' => $validated['alias'],
            'role' => $validated['role'],
            'visibility_level' => $validated['visibility_level'] ?? 'public',
            'verification_status' => 'pending',
            'is_active' => false,
        ]);

        return response()->json(['success' => true, 'data' => $identity], 201);
    }

    /**
     * Switch active identity if owned by user and verified.
     */
    public function switch(Request $request): JsonResponse
    {
        $request->validate([
            'identity_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();
        $identityId = $request->string('identity_id');

        /** @var Identity|null $identity */
        $identity = Identity::where('id', $identityId)
            ->where('user_id', $user->id)
            ->first();

        // Ownership check
        if (! $identity) {
            return response()->json([
                'message' => __('identities.forbidden'),
            ], 403);
        }

        // Verification check
        if (! $identity->isVerified()) {
            return response()->json([
                'message' => __('identities.verification_required'),
            ], 403);
        }

        // Must be active
        if (! $identity->is_active) {
            return response()->json([
                'message' => __('identities.inactive'),
            ], 403);
        }

        DB::transaction(function () use ($user, $identity, $request) {
            DB::table('identity_switch_logs')->insert([
                'user_id'          => $user->id,
                'from_identity_id' => $user->active_identity_id,
                'to_identity_id'   => $identity->id,
                'ip'               => $request->ip(),
                'user_agent'       => substr((string) $request->userAgent(), 0, 512),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            Identity::where('user_id', $user->id)->update(['is_active' => false]);

            $identity->forceFill(['is_active' => true])->save();

            $user->forceFill(['active_identity_id' => $identity->id])->save();
        });

        return response()->json([
            'message' => __('identities.switched'),
            'data' => [
                'active_identity_id' => $user->fresh()->active_identity_id,
            ],
        ]);
    }

    /**
     * Delete an identity if it's not active and not the last remaining one.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $identity = Identity::ownedBy($user->id)->findOrFail($id);

        $count = Identity::ownedBy($user->id)->count();

        if ($identity->is_active || $count <= 1) {
            return response()->json(['message' => __('identities.delete_forbidden')], 422);
        }

        $identity->delete();

        return response()->json(['message' => __('identities.deleted')]);
    }
}
