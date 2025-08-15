<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Role;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Handle login request and return user with token and refresh token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['data' => null, 'message' => __('auth.failed')], 401);
        }

        $user = User::with([
            'roles.permissions',
            'permissions',
            'activeIdentity',
            'identities',
        ])->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['data' => null, 'message' => __('auth.failed')], 403);
        }

        if (Carbon::parse($user->date_of_birth)->age < 21) {
            return response()->json([
                'success' => false,
                'message' => __('auth.underage'),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $rawRefreshToken = Str::random(64);

        $refreshToken = RefreshToken::factory()->create([
            'user_id'     => $user->id,
            'expires_at'  => now()->addDays(30),
            'user_agent'  => $request->userAgent(),
            'ip_address'  => $request->ip(),
        ]);
        $refreshToken->hash($rawRefreshToken);

        return response()->json([
            'data' => [
                'token'         => $token,
                'refresh_token' => $rawRefreshToken,
                'user'          => new UserResource($user),
            ],
        ]);
    }

    /**
     * Logout and revoke the access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['data' => null, 'message' => __('auth.unauthenticated')], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json(['data' => null, 'message' => __('auth.unauthenticated')], 401);
        }

        $accessToken->delete();

        return response()->json([
            'data' => [
                'success'         => true,
                'message' => __('auth.logged_out'),
            ],
        ]);
    }

    /**
     * Register a new user with 21+ age validation.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_name'   => ['required', 'string', 'max:255'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'password'       => ['required', 'string', 'min:8'],
            'date_of_birth'  => ['required', 'date'],
            'type'           => ['required', Rule::in(['user','creator','service_provider','content_provider','host'])],
        ]);

        if (Carbon::parse($validated['date_of_birth'])->gt(now()->subYears(21))) {
            throw ValidationException::withMessages([
                'date_of_birth' => __('auth.underage'),
            ]);
        }

        $user = User::create([
            'display_name'  => $validated['display_name'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'date_of_birth' => $validated['date_of_birth'],
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'data' => [
                'success'         => true,
                'message' => __('auth.register_success'),
            ]
        ], 201);


    }

    /**
     * Issue a new access token using a valid refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $rawToken = $request->input('refresh_token');

        $refreshToken = RefreshToken::matchingRawTokenLoose($rawToken);
        // For production use: RefreshToken::matchingRawToken($rawToken);

        if (! $refreshToken || $refreshToken->revoked_at || $refreshToken->isExpired()) {
            return response()->json(['message' => __('auth.invalid_refresh_token')], 401);
        }

        $refreshToken->update(['revoked_at' => now()]);

        $user = $refreshToken->user;

        $accessToken = $user->createToken('auth_token')->plainTextToken;
        $newToken = Str::random(64);

        RefreshToken::create([
            'user_id'     => $user->id,
            'expires_at'  => now()->addDays(30),
            'user_agent'  => $request->userAgent(),
            'ip_address'  => $request->ip(),
            'token_hash'  => Hash::make($newToken),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('auth.refresh_success'),
            'data' => [
                'token'          => $accessToken,
                'refresh_token'  => $newToken,
            ],
        ]);

        return response()->json([
            'data' => [
                'success'         => true,
                'message' => __('auth.refresh_success'),
                'token' => $access
            ]
        ], 201);
    }

    /**
     * Resend the email verification link to the current user.
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => __('auth.email_already_verified'),
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => __('auth.verification_resent'),
        ]);
    }
}
