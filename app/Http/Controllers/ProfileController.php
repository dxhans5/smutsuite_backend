<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePrivateProfileRequest;
use App\Http\Requests\UpdatePublicProfileRequest;
use App\Models\Identity;
use App\Models\PrivateProfile;
use App\Models\PublicProfile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles profile-related functionality for the authenticated user's identity.
 */
class ProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Returns both public and private profiles for the currently active identity.
     */
    public function getMyProfiles(Request $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        return response()->json([
            'public_profile'  => PublicProfile::where('identity_id', $identity->id)->first(),
            'private_profile' => PrivateProfile::where('identity_id', $identity->id)->first(),
        ]);
    }

    /**
     * Updates or creates the public profile for the currently active identity.
     */
    public function updatePublicProfile(UpdatePublicProfileRequest $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $profile = PublicProfile::firstOrNew(['identity_id' => $identity->id]);

        // Set relation and FK explicitly for policy checks and persistence
        $profile->setRelation('identity', $identity);
        $profile->identity_id = $identity->id;

        $this->authorize('updatePublic', $profile);

        $profile->fill($request->validated())->save();

        return response()->json($profile);
    }

    /**
     * Updates or creates the private profile for the currently active identity.
     */
    public function updatePrivateProfile(UpdatePrivateProfileRequest $request): JsonResponse
    {
        $identity = $request->currentIdentity();
        abort_unless($identity, 403, 'No active identity selected.');

        $profile = PrivateProfile::firstOrNew(['identity_id' => $identity->id]);

        $profile->setRelation('identity', $identity);
        $profile->identity_id = $identity->id;

        $this->authorize('updatePrivate', $profile);

        $profile->fill($request->validated())->save();

        return response()->json($profile);
    }

    /**
     * Publicly fetches a visible profile by identity ID. If profile is hidden, only owner can view.
     */
    public function getPublicProfile(string $identityId, Request $request): JsonResponse
    {
        $viewer = $request->user();
        $profile = PublicProfile::where('identity_id', $identityId)->firstOrFail();

        if (!$profile->is_visible) {
            $owns = $viewer && Identity::forUser($viewer)->where('id', $identityId)->exists();
            abort_unless($owns, 404);
        }

        return response()->json($profile);
    }
}
