<?php

use App\Events\TestPingEvent;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IdentityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api and assigned the "api" middleware group.
| Organization:
|   1) Public (no auth)
|   2) Authenticated (auth:sanctum)
|   3) Verified (auth:sanctum + verified)
|
| Notes:
| - All auth endpoints live under /auth/*
| - Email verification uses the standard signed route name: verification.verify
| - Route names are explicit for stability across refactors
*/

/**
 * --------------------------------------------------------------------------
 * Public Routes (No Authentication Required)
 * --------------------------------------------------------------------------
 * - Registration / Login / Token Refresh
 * - Google OAuth steps
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('/refresh',  [AuthController::class, 'refresh'])->name('auth.refresh');

    // Back-compat aliases (remove once clients switch to /auth/*)
    Route::post('/google',          [GoogleAuthController::class, 'handle'])->name('auth.google.start');
    Route::post('/google/complete', [GoogleAuthController::class, 'complete'])->name('auth.google.complete');
});

// LEGACY aliases to avoid breaking existing consumers; safe to delete later.
Route::post('/register', [AuthController::class, 'register'])->name('legacy.register');
Route::post('/login',    [AuthController::class, 'login'])->name('legacy.login');

/**
 * --------------------------------------------------------------------------
 * Email Verification (Requires Authentication)
 * --------------------------------------------------------------------------
 * - Verification link consumption (signed)
 * - Resend verification email
 */
Route::prefix('email')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return response()->json([
            'message' => __('auth.verification_success'),
        ]);
    })->middleware('signed')->name('verification.verify');

    Route::post('/resend', [AuthController::class, 'resendVerificationEmail'])
        ->name('verification.resend');
});

/**
 * --------------------------------------------------------------------------
 * Authenticated Routes (Requires Login)
 * --------------------------------------------------------------------------
 * - Lightweight user context (me)
 * - Notifications (do not require verified email to receive in-app)
 */
Route::middleware('auth:sanctum')->group(function () {

    // Current user context
    Route::get('/auth/me', [UserController::class, 'me'])->name('auth.me');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',               [UserController::class, 'notifications'])->name('notifications.index');
        Route::post('/send',          [UserController::class, 'notify'])->name('notifications.send');
        Route::post('/{id}/read',     [UserController::class, 'markNotificationAsRead'])->name('notifications.read');
    });

    // Identities (index is allowed for auth users; mutating actions require verified below)
    Route::get('/identities', [IdentityController::class, 'index'])->name('identities.index');
});

/**
 * --------------------------------------------------------------------------
 * Verified Routes (Requires Login + Verified Email)
 * --------------------------------------------------------------------------
 * - Session management (logout)
 * - Profile management (public/private)
 * - Identity management (create/update/delete/switch)
 * - Availability, Bookings, Messages
 * - RBAC & Permissions management endpoints
 */
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    // Auth session
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    // Users: Roles & Permissions (admin UX helpers)
    Route::prefix('users')->group(function () {
        // Single-item role attach/detach
        Route::post('/{user}/roles/{role}/attach',   [UserController::class, 'attachRole'])->name('users.roles.attach');
        Route::post('/{user}/roles/{role}/detach',   [UserController::class, 'detachRole'])->name('users.roles.detach');

        // Single-item permission attach/detach
        Route::post('/{user}/permissions/{permission}/attach', [UserController::class, 'attachPermission'])->name('users.permissions.attach');
        Route::post('/{user}/permissions/{permission}/detach', [UserController::class, 'detachPermission'])->name('users.permissions.detach');

        // Bulk role/permission assign/remove
        Route::post('/{user}/assign', [UserController::class, 'assignRolesAndPermissions'])->name('users.assign');
        Route::post('/{user}/remove', [UserController::class, 'removeRolesAndPermissions'])->name('users.remove');
    });

    // Profiles
    Route::prefix('profiles')->group(function () {
        Route::get('/me',           [UserController::class, 'getMyProfiles'])->name('profiles.me');
        Route::put('/me/public',    [UserController::class, 'updatePublicProfile'])->name('profiles.me.public.update');
        Route::put('/me/private',   [UserController::class, 'updatePrivateProfile'])->name('profiles.me.private.update');
        Route::get('/{id}/public',  [UserController::class, 'getPublicProfile'])->name('profiles.public.show');
    });

    // Identities (mutations gated by verified + policy)
    Route::prefix('identities')->group(function () {
        Route::post('/',                 [IdentityController::class, 'store'])->name('identities.store');
        Route::put('/{identity}',        [IdentityController::class, 'update'])->name('identities.update');
        Route::delete('/{identity}',     [IdentityController::class, 'destroy'])->name('identities.destroy');
        Route::post('/switch',           [IdentityController::class, 'switch'])->name('identities.switch');
    });

    // Availability
    Route::prefix('availability')->group(function () {
        Route::get('/me',        [UserController::class, 'getMyAvailability'])->name('availability.me');
        Route::put('/me',        [UserController::class, 'updateMyAvailability'])->name('availability.me.update');
        Route::get('/{user}',    [UserController::class, 'getUserAvailability'])->name('availability.user.show');
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::post('/',   [UserController::class, 'createBookingRequest'])->name('bookings.store');
        Route::get('/me',  [UserController::class, 'getMyBookings'])->name('bookings.me');
    });

    // Messaging
    Route::prefix('messages')->group(function () {
        Route::post('/send',       [UserController::class, 'sendMessage'])->name('messages.send');
        Route::get('/threads',     [UserController::class, 'getThreads'])->name('messages.threads');
        Route::get('/thread/{id}', [UserController::class, 'getThreadMessages'])->name('messages.thread.show');
        Route::post('/{id}/read',  [UserController::class, 'markAsRead'])->name('messages.read');
        Route::delete('/{id}',     [UserController::class, 'deleteMessage'])->name('messages.destroy');
    });
});

/**
 * --------------------------------------------------------------------------
 * Admin / Permission Demo Routes
 * --------------------------------------------------------------------------
 * Keep these samples if you need them for quick checks.
 */
Route::middleware(['role:admin'])->get('/admin-dashboard', function () {
    // Only accessible to users with role 'admin'
})->name('admin.dashboard');

Route::middleware(['permission:edit-users'])->post('/user/{id}/edit', function () {
    // Only accessible to users with permission 'edit-users'
})->name('users.edit.permission');

/**
 * --------------------------------------------------------------------------
 * Debug / Diagnostics
 * --------------------------------------------------------------------------
 */
Route::post('/debug/ping', function () {
    broadcast(new TestPingEvent('pong'));
    return response()->json(['message' => 'Ping sent.']);
})->name('debug.ping');
