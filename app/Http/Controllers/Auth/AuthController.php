<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
            ], 401);
        }

        $user = User::where('email', $request->email)->first();


        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
            ], 401);
        }

        if (Carbon::parse($user->date_of_birth)->age < 21) {
            return response()->json([
                'success' => false,
                'message' => __('auth.underage'),
            ], 403);
        }

        // Optional: revoke old tokens if only one should be active
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        $rawRefreshToken = Str::random(64);

        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);
        $refreshToken->hash($rawRefreshToken);

        return response()->json([
            'success' => true,
            'message' => __('auth.login_success'),
            'data' => [
                'token' => $token,
                'user'  => $user->only(['id', 'nickname', 'email', 'age', 'city']),
                'refresh_token' => $refreshToken,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->currentAccessToken()) {
            return response()->json([
                'message' => __('auth.unauthenticated'),
            ], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => __('auth.logged_out'),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        // This will be replaced with a FormRequest + MustBe21 Rule later
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'date_of_birth' => ['required', 'date'],
            'role' => ['required', Rule::in(['user', 'service provider', 'content provider', 'host'])],
        ]);

        // Check age manually for now
        if (now()->diffInYears($validated['date_of_birth']) < 21) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'You must be at least 21 years old to register.',
            ]);
        }

        $user = User::create([
            'display_name' => $validated['display_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'date_of_birth' => $validated['date_of_birth'],
        ]);

        // Attach role
        $roleModel = Role::where('name', $validated['role'])->first();
        $user->roles()->attach($roleModel);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
        ], 201);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => ['required', 'string'],
        ]);

        $token = $request->input('refresh_token');

        $refreshToken = RefreshToken::all()->first(fn($t) => $t->matchesRawToken($token));

        if (!$refreshToken) {
            return response()->json(['message' => __('auth.invalid_refresh_token')], 401);
        }

        if ($refreshToken->isExpired()) {
            return response()->json(['message' => __('auth.expired_refresh_token')], 401);
        }

        if ($refreshToken->revoked_at) {
            return response()->json(['message' => __('auth.invalid_refresh_token')], 401);
        }

        // ✅ Revoke the old token
        $refreshToken->update(['revoked_at' => now()]);

        $user = $refreshToken->user;
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        // ✅ Generate new refresh token
        $newToken = Str::random(64);
        $hashedToken = Hash::make($newToken);

        RefreshToken::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays(30),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'token_hash' => $hashedToken,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('auth.refresh_success'),
            'data' => [
                'token' => $accessToken,
                'refresh_token' => $newToken,
            ],
        ]);
    }
}
