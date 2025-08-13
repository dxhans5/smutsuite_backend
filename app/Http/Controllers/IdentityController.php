<?php

namespace App\Http\Controllers;

use App\Http\Resources\IdentityResource;
use App\Models\Identity;
use App\Models\User;
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
     * Create an Identity for the authenticated user.
     *
     * Request (JSON):
     *  - alias      (string, required)  Globally unique alias (DB unique index).
     *  - type       (string, required)  user|creator|service_provider|content_provider|host
     *  - label      (string, required)  UI label
     *  - is_active  (bool,    required) Whether to make it the user's active identity now
     *
     * Behavior:
     *  - 72h cooldown for UNVERIFIED users only.
     *  - If is_active=true: set users.active_identity_id to this id and deactivate all others.
     *  - All writes are transactional.
     *
     * Response (201):
     *  - Top-level "id" for convenience/back-compat
     *  - "data" containing the IdentityResource
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $COOLDOWN_HOURS = 72;

        // Cooldown for unverified users only
        if (! $user->hasVerifiedEmail()) {
            $last = Identity::query()
                ->where('user_id', $user->id)
                ->latest('created_at')
                ->first(['created_at']);

            if ($last) {
                $hoursSince = $last->created_at->diffInHours(now());
                if ($hoursSince < $COOLDOWN_HOURS) {
                    return response()->json([
                        'message' => __('identities.cooldown_active', [
                            'hours' => $COOLDOWN_HOURS - $hoursSince
                        ]),
                    ], 422);
                }
            }
        }

        // Validate inputs
        $validated = $request->validate([
            'alias'      => ['required', 'string', 'max:255', Rule::unique('identities', 'alias')],
            'type'       => ['required', 'string', Rule::in(['user','creator','service_provider','content_provider','host'])],
            'label'      => ['required', 'string', 'max:255'],
            'is_active'  => ['required', 'boolean'],
        ]);

        // Create + optionally flip active pointer atomically
        $identity = DB::transaction(function () use ($user, $validated) {
            $identity = Identity::create([
                'id'        => (string) Str::uuid(), // explicit; HasUuids could also auto-generate
                'user_id'   => $user->id,
                'alias'     => $validated['alias'],
                'type'      => $validated['type'],
                'label'     => $validated['label'],
                // visibility + verification_status rely on DB defaults
                'is_active' => (bool) $validated['is_active'],
            ]);

            if ($validated['is_active'] === true) {
                // Point user at this identity
                $user->forceFill(['active_identity_id' => $identity->id])->save();

                // Deactivate all other identities
                $user->identities()
                    ->whereKeyNot($identity->id)
                    ->update(['is_active' => false]);
            }

            return $identity;
        });

        // Back-compat: expose id at top-level, and keep the resource under "data"
        return response()->json([
            'id'   => $identity->id,
            'data' => new IdentityResource($identity),
        ], 201);
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
