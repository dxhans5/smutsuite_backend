<?php

use App\Http\Controllers\AccessControl\PermissionController;
use App\Http\Controllers\AccessControl\RoleController;
use App\Http\Controllers\Scheduling\AvailabilityController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;

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

    Route::prefix('users')->group(function () {
        // Roles
        Route::post('/{user}/roles/{role}/attach',   [RoleController::class, 'attach'])->name('users.roles.attach');
        Route::post('/{user}/roles/{role}/detach',   [RoleController::class, 'detach'])->name('users.roles.detach');

        // Permissions
        Route::post('/{user}/permissions/{permission}/attach', [PermissionController::class, 'attach'])->name('users.permissions.attach');
        Route::post('/{user}/permissions/{permission}/detach', [PermissionController::class, 'detach'])->name('users.permissions.detach');

        // Bulk
        Route::post('/{user}/assign', [RoleController::class, 'assignRolesAndPermissions'])->name('users.assign');
        Route::post('/{user}/remove', [RoleController::class, 'removeRolesAndPermissions'])->name('users.remove');
    });

    // Profiles
    Route::prefix('profiles')->controller(ProfileController::class)->group(function () {
        Route::get('/me',            'getMyProfiles')->name('profiles.me');
        Route::put('/me/public',     'updatePublicProfile')->name('profiles.me.public.update');
        Route::put('/me/private',    'updatePrivateProfile')->name('profiles.me.private.update');
        Route::get('/{id}/public',   'getPublicProfile')->name('profiles.public.show');
    });

    // Identities
    Route::prefix('identities')->group(function () {
        Route::post('/',             [IdentityController::class, 'store'])->name('identities.store');
        Route::put('/{identity}',    [IdentityController::class, 'update'])->name('identities.update');
        Route::delete('/{identity}', [IdentityController::class, 'destroy'])->name('identities.destroy');
        Route::post('/switch',       [IdentityController::class, 'switch'])->name('identities.switch');
    });

    // Availability
    Route::prefix('availability')->controller(AvailabilityController::class)->group(function () {
        Route::get('/me', 'getMyAvailability')->name('availability.me');
        Route::put('/me', 'updateMyAvailability')->name('availability.me.update');
        Route::get('/{identity}', 'getIdentityAvailability')->name('availability.identity.show');
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
