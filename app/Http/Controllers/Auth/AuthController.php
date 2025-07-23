<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
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
}
