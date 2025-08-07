<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api and assigned the "api" middleware group.
|
| Structure:
|   1. Public (no auth)
|   2. Authenticated (auth:sanctum)
|   3. Verified (auth:sanctum + verified)
*/

/**
 * --------------------------------------------------------------------------
 * PUBLIC ROUTES (No Authentication Required)
 * --------------------------------------------------------------------------
 * - Auth (register, login, refresh)
 * - Google OAuth
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('/refresh',  [AuthController::class, 'refresh'])->name('auth.refresh');

    Route::post('/google',          [GoogleAuthController::class, 'handle'])->name('auth.google.start');
    Route::post('/google/complete', [GoogleAuthController::class, 'complete'])->name('auth.google.complete');
});

// TEMP: Legacy compatibility for frontend
Route::post('/register', [AuthController::class, 'register'])->name('legacy.register');
Route::post('/login',    [AuthController::class, 'login'])->name('legacy.login');

/**
 * --------------------------------------------------------------------------
 * EMAIL VERIFICATION (Authenticated only)
 * --------------------------------------------------------------------------
 */
Route::prefix('email')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json(['message' => __('auth.verification_success')]);
    })->middleware('signed')->name('verification.verify');

    Route::post('/resend', [AuthController::class, 'resendVerificationEmail'])->name('verification.resend');
});

/**
 * --------------------------------------------------------------------------
 * AUTHENTICATED ROUTES (auth:sanctum)
 * --------------------------------------------------------------------------
 */
Route::middleware('auth:sanctum')->group(function () {
    // Auth context
    Route::get('/auth/me', [UserController::class, 'me'])->name('auth.me');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',             [UserController::class, 'notifications'])->name('notifications.index');
        Route::post('/send',        [UserController::class, 'notify'])->name('notifications.send');
        Route::post('/{id}/read',   [UserController::class, 'markNotificationAsRead'])->name('notifications.read');
    });

    // Read-only access to identities
    Route::get('/identities', [IdentityController::class, 'index'])->name('identities.index');
});

/**
 * --------------------------------------------------------------------------
 * VERIFIED ROUTES (auth:sanctum + verified)
 * --------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Session
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    // Roles & Permissions (RBAC)
    Route::prefix('users')->group(function () {
        Route::post('/{user}/roles/{role}/attach',         [UserController::class, 'attachRole'])->name('users.roles.attach');
        Route::post('/{user}/roles/{role}/detach',         [UserController::class, 'detachRole'])->name('users.roles.detach');
        Route::post('/{user}/permissions/{permission}/attach', [UserController::class, 'attachPermission'])->name('users.permissions.attach');
        Route::post('/{user}/permissions/{permission}/detach', [UserController::class, 'detachPermission'])->name('users.permissions.detach');
        Route::post('/{user}/assign',                      [UserController::class, 'assignRolesAndPermissions'])->name('users.assign');
        Route::post('/{user}/remove',                      [UserController::class, 'removeRolesAndPermissions'])->name('users.remove');
    });

    // Profiles
    Route::prefix('profiles')->group(function () {
        Route::get('/me',            [UserController::class, 'getMyProfiles'])->name('profiles.me');
        Route::put('/me/public',     [UserController::class, 'updatePublicProfile'])->name('profiles.me.public.update');
        Route::put('/me/private',    [UserController::class, 'updatePrivateProfile'])->name('profiles.me.private.update');
        Route::get('/{id}/public',   [UserController::class, 'getPublicProfile'])->name('profiles.public.show');
    });

    // Identities
    Route::prefix('identities')->group(function () {
        Route::post('/',             [IdentityController::class, 'store'])->name('identities.store');
        Route::put('/{identity}',    [IdentityController::class, 'update'])->name('identities.update');
        Route::delete('/{identity}', [IdentityController::class, 'destroy'])->name('identities.destroy');
        Route::post('/switch',       [IdentityController::class, 'switch'])->name('identities.switch');
    });

    // Availability
    Route::prefix('availability')->group(function () {
        Route::get('/me',     [UserController::class, 'getMyAvailability'])->name('availability.me');
        Route::put('/me',     [UserController::class, 'updateMyAvailability'])->name('availability.me.update');
        Route::get('/{user}', [UserController::class, 'getUserAvailability'])->name('availability.user.show');
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::post('/',  [UserController::class, 'createBookingRequest'])->name('bookings.store');
        Route::get('/me', [UserController::class, 'getMyBookings'])->name('bookings.me');
    });

    // Messaging (refactored to MessageController)
    Route::prefix('messages')->group(function () {
        Route::post('/send',         [MessageController::class, 'send'])->name('messages.send');
        Route::get('/threads',       [MessageController::class, 'threads'])->name('messages.threads');
        Route::get('/thread/{id}',   [MessageController::class, 'show'])->name('messages.thread.show');
        Route::post('/{id}/read',    [MessageController::class, 'markAsRead'])->name('messages.read');
        Route::delete('/{id}',       [MessageController::class, 'destroy'])->name('messages.destroy');
    });
});

/**
 * --------------------------------------------------------------------------
 * DEMO: Permission-Protected Routes
 * --------------------------------------------------------------------------
 */
Route::middleware(['role:admin'])->get('/admin-dashboard', fn () => [])->name('admin.dashboard');

Route::middleware(['permission:edit-users'])->post('/user/{id}/edit', fn () => [])->name('users.edit.permission');

/**
 * --------------------------------------------------------------------------
 * DEBUG & DIAGNOSTICS
 * --------------------------------------------------------------------------
 */
Route::post('/debug/ping', fn () => response()->json(['message' => 'Ping sent.']))->name('debug.ping');
