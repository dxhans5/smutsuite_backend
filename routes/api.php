<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;

use App\Http\Controllers\Scheduling\AvailabilityController;

use App\Http\Controllers\AccessControl\RoleController;
use App\Http\Controllers\AccessControl\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All endpoints are prefixed with /api (by RouteServiceProvider) and use
| the "api" middleware group.
|
| Conventions (Vixen Bible):
| - Responses are JSON envelopes: { data: {...}, meta: { success, message?, ... } }
| - Authenticated: auth:sanctum
| - Verified-only: auth:sanctum + verified
| - RESTful where sensible (e.g., DELETE to detach).
*/

/**
 * --------------------------------------------------------------------------
 * PUBLIC: Auth + OAuth
 * --------------------------------------------------------------------------
 */
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login',    [AuthController::class, 'login'])->name('auth.login');
    Route::post('refresh',  [AuthController::class, 'refresh'])->name('auth.refresh');

    // Google OAuth handshake
    Route::post('google',          [GoogleAuthController::class, 'handle'])->name('auth.google.start');
    Route::post('google/complete', [GoogleAuthController::class, 'complete'])->name('auth.google.complete');
});

// Legacy paths kept temporarily for frontend compatibility
Route::post('register', [AuthController::class, 'register'])->name('legacy.register'); // DEPRECATED
Route::post('login',    [AuthController::class, 'login'])->name('legacy.login');       // DEPRECATED

/**
 * --------------------------------------------------------------------------
 * AUTH-ONLY: Email verification flow
 * --------------------------------------------------------------------------
 */
Route::prefix('email')->middleware('auth:sanctum')->group(function () {
    Route::get('verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        // Envelope is handled at controller level elsewhere; here we return a minimal message.
        return response()->json(['data' => [], 'meta' => ['success' => true, 'message' => __('auth.verification_success')]]);
    })->middleware('signed')->name('verification.verify');

    Route::post('resend', [AuthController::class, 'resendVerificationEmail'])->name('verification.resend');
});

/**
 * --------------------------------------------------------------------------
 * AUTH-ONLY: Session context + read-only resources
 * --------------------------------------------------------------------------
 */
Route::middleware('auth:sanctum')->group(function () {
    // Auth context
    Route::get('auth/me', [UserController::class, 'me'])->name('auth.me');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',           [UserController::class, 'notifications'])->name('notifications.index');
        Route::post('send',       [UserController::class, 'notify'])->name('notifications.send');
        Route::post('{id}/read',  [UserController::class, 'markNotificationAsRead'])->name('notifications.read');
    });

    // Read-only identities listing for current user
    Route::get('identities', [IdentityController::class, 'index'])->name('identities.index');
});

/**
 * --------------------------------------------------------------------------
 * VERIFIED: Mutating endpoints
 * --------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    /**
     * Session
     */
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    /**
     * Users → Roles & Permissions
     * - New RESTful permission routes (match tests):
     *     POST   /users/{user}/permissions/{permission}       -> attach
     *     DELETE /users/{user}/permissions/{permission}       -> detach
     *     POST   /users/{user}/permissions/bulk                -> bulkAssign
     *     DELETE /users/{user}/permissions/bulk                -> bulkRemove
     * - Back-compat routes retained (attach/detach suffixes; assign/remove).
     */
    Route::prefix('users')->group(function () {

        // ---- Roles (existing behavior preserved) ----
        Route::post('{user}/roles/{role}/attach', [RoleController::class, 'attach'])->name('users.roles.attach');
        Route::post('{user}/roles/{role}/detach', [RoleController::class, 'detach'])->name('users.roles.detach');

        // ---- Permissions (NEW RESTful) ----
        Route::post('{user}/permissions/{permission}',   [PermissionController::class, 'attach'])->name('users.permissions.attach.rest');
        Route::delete('{user}/permissions/{permission}', [PermissionController::class, 'detach'])->name('users.permissions.detach.rest');

        Route::post('{user}/permissions/bulk',   [PermissionController::class, 'bulkAssign'])->name('users.permissions.bulk.assign');
        Route::delete('{user}/permissions/bulk', [PermissionController::class, 'bulkRemove'])->name('users.permissions.bulk.remove');

        // ---- Back-compat (will be removed later) ----
        Route::post('{user}/permissions/{permission}/attach', [PermissionController::class, 'attach'])->name('users.permissions.attach'); // legacy
        Route::post('{user}/permissions/{permission}/detach', [PermissionController::class, 'detach'])->name('users.permissions.detach'); // legacy

        // legacy bulk names that used RoleController previously
        Route::post('{user}/assign', [PermissionController::class, 'bulkAssign'])->name('users.assign');  // legacy alias
        Route::post('{user}/remove', [PermissionController::class, 'bulkRemove'])->name('users.remove');  // legacy alias
    });

    /**
     * Profiles (Public & Private)
     */
    Route::prefix('profiles')->controller(ProfileController::class)->group(function () {
        Route::get('me',          'getMyProfiles')->name('profiles.me');
        Route::put('me/public',   'updatePublicProfile')->name('profiles.me.public.update');
        Route::put('me/private',  'updatePrivateProfile')->name('profiles.me.private.update');
        Route::get('{id}/public', 'getPublicProfile')->name('profiles.public.show');
    });

    /**
     * Identities (CRUD + switch)
     */
    Route::prefix('identities')->group(function () {
        Route::post('/',            [IdentityController::class, 'store'])->name('identities.store');
        Route::put('{identity}',    [IdentityController::class, 'update'])->name('identities.update');
        Route::delete('{identity}', [IdentityController::class, 'destroy'])->name('identities.destroy');
        Route::post('switch',       [IdentityController::class, 'switch'])->name('identities.switch');
    });

    /**
     * Availability
     */
    Route::prefix('availability')->controller(AvailabilityController::class)->group(function () {
        Route::get('me',         'getMyAvailability')->name('availability.me');
        Route::put('me',         'updateMyAvailability')->name('availability.me.update');
        Route::get('{identity}', 'getIdentityAvailability')->name('availability.identity.show');
    });

    /**
     * Bookings
     */
    Route::prefix('bookings')->group(function () {
        Route::post('/',  [UserController::class, 'createBookingRequest'])->name('bookings.store');
        Route::get('me',  [UserController::class, 'getMyBookings'])->name('bookings.me');
    });

    /**
     * Messaging
     */
    Route::prefix('messages')->group(function () {
        Route::post('send',       [MessageController::class, 'send'])->name('messages.send');
        Route::get('threads',     [MessageController::class, 'threads'])->name('messages.threads');
        Route::get('thread/{id}', [MessageController::class, 'show'])->name('messages.thread.show');
        Route::post('{id}/read',  [MessageController::class, 'markAsRead'])->name('messages.read');
        Route::delete('{id}',     [MessageController::class, 'destroy'])->name('messages.destroy');
    });
});

/**
 * --------------------------------------------------------------------------
 * DEMO: Permission-protected examples
 * --------------------------------------------------------------------------
 */
Route::middleware(['role:admin'])->get('admin-dashboard', fn () => response()->json(['data' => [], 'meta' => ['success' => true]]))
    ->name('admin.dashboard');

Route::middleware(['permission:edit-users'])->post('user/{id}/edit', fn () => response()->json(['data' => [], 'meta' => ['success' => true]]))
    ->name('users.edit.permission');

/**
 * --------------------------------------------------------------------------
 * Diagnostics
 * --------------------------------------------------------------------------
 */
Route::post('debug/ping', fn () => response()->json(['data' => [], 'meta' => ['success' => true, 'message' => 'Ping sent.']]))
    ->name('debug.ping');
