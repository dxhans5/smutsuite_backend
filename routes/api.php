<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Authentication Controllers
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;

// Core Resource Controllers
use App\Http\Controllers\UserController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AvailabilityController;

// Access Control Controllers
use App\Http\Controllers\AccessControl\RoleController;
use App\Http\Controllers\AccessControl\PermissionController;

/*
|--------------------------------------------------------------------------
| SmutSuite API Routes
|--------------------------------------------------------------------------
|
| All endpoints are prefixed with /api (by RouteServiceProvider) and use
| the "api" middleware group for rate limiting and JSON responses.
|
| ARCHITECTURE PRINCIPLES:
| • API-only design - no Blade views, pure JSON responses
| • Resource pattern - all responses via JsonResource with {"data": ...}
| • UUID primary keys - no auto-incrementing IDs
| • Multi-identity architecture - one user, multiple personas/roles
| • Role-based permissions - complex RBAC via role_user, permission_role
| • Sanctum authentication - token-based with refresh tokens (hashed)
| • Localization ready - __('key') for all user-facing messages
|
| RESPONSE CONVENTIONS:
| • Success: {"data": {...}, "meta": {"success": true, "message": "..."}}
| • Validation: {"message": "...", "errors": {"field": ["error"]}}
| • Error: {"message": "Internal server error"}
|
| MIDDLEWARE GROUPS:
| • auth:sanctum - requires valid API token
| • verified - requires email verification
| • role:admin - requires admin role
| • permission:action - requires specific permission
|
*/

// =============================================================================
// PUBLIC ROUTES (No Authentication Required)
// =============================================================================

/**
 * Authentication & Registration
 *
 * Handles user registration, login, token refresh, and OAuth flows.
 * Uses hashed refresh tokens for security.
 */
Route::prefix('auth')->name('auth.')->group(function () {
    // Core authentication
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');

    // Google OAuth handshake
    Route::post('google', [GoogleAuthController::class, 'handle'])->name('google.start');
    Route::post('google/complete', [GoogleAuthController::class, 'complete'])->name('google.complete');
});

/**
 * Legacy Authentication Routes (DEPRECATED)
 *
 * Maintained for frontend compatibility during transition.
 * Will be removed in future version.
 */
Route::post('register', [AuthController::class, 'register'])->name('legacy.register');
Route::post('login', [AuthController::class, 'login'])->name('legacy.login');

/**
 * System Diagnostics
 *
 * Health checks and debugging endpoints.
 */
Route::post('debug/ping', fn () => response()->json([
    'data' => ['timestamp' => now()->toISOString()],
    'meta' => ['success' => true, 'message' => __('system.ping_success')]
]))->name('debug.ping');

// =============================================================================
// AUTHENTICATED ROUTES (Valid Token Required)
// =============================================================================

Route::middleware('auth:sanctum')->group(function () {

    /**
     * Email Verification Flow
     *
     * Handles email verification links and resend requests.
     */
    Route::prefix('email')->name('verification.')->group(function () {
        Route::get('verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();
            return response()->json([
                'data' => ['verified_at' => now()->toISOString()],
                'message' => __('auth.verification_success'),
                'meta' => ['success' => true]
            ]);
        })->middleware('signed')->name('verify');

        Route::post('resend', [AuthController::class, 'resendVerificationEmail'])->name('resend');
    });

    /**
     * User Context & Session Management
     *
     * Current user info, active identity, session details.
     */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('me', [UserController::class, 'me'])->name('me');
    });

    /**
     * Notifications System
     *
     * Real-time notifications, push messaging, read status tracking.
     */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [UserController::class, 'notifications'])->name('index');
        Route::post('send', [UserController::class, 'notify'])->name('send');
        Route::post('{id}/read', [UserController::class, 'markNotificationAsRead'])->name('read');
    });

    /**
     * Identity Management (Read-Only)
     *
     * List user's identities. CRUD operations require verification.
     */
    Route::get('identities', [IdentityController::class, 'index'])->name('identities.index');

});

// =============================================================================
// VERIFIED ROUTES (Email Verification Required)
// =============================================================================

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    /**
     * Session Management
     *
     * Logout and session termination.
     */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });

    /**
     * User Role & Permission Management
     *
     * RBAC system with both RESTful and legacy routes.
     * Supports individual and bulk assignment/removal.
     */
    Route::prefix('users')->name('users.')->group(function () {

        // Role Management (Legacy Format - Preserved)
        Route::post('{user}/roles/{role}/attach', [RoleController::class, 'attach'])->name('roles.attach');
        Route::post('{user}/roles/{role}/detach', [RoleController::class, 'detach'])->name('roles.detach');

        // Bulk Permission Operations
        Route::post('{user}/permissions/bulk', [PermissionController::class, 'bulkAssign'])->name('permissions.bulk.assign');
        Route::delete('{user}/permissions/bulk', [PermissionController::class, 'bulkRemove'])->name('permissions.bulk.remove');

        // Permission Management (RESTful - Primary)
        Route::post('{user}/permissions/{permission}', [PermissionController::class, 'attach'])->name('permissions.attach');
        Route::delete('{user}/permissions/{permission}', [PermissionController::class, 'detach'])->name('permissions.detach');

        // Legacy Permission Routes (DEPRECATED - Will be removed)
        Route::post('{user}/permissions/{permission}/attach', [PermissionController::class, 'attach'])->name('permissions.attach.legacy');
        Route::post('{user}/permissions/{permission}/detach', [PermissionController::class, 'detach'])->name('permissions.detach.legacy');
        Route::post('{user}/assign', [PermissionController::class, 'bulkAssign'])->name('assign');
        Route::post('{user}/remove', [PermissionController::class, 'bulkRemove'])->name('remove');
    });

    /**
     * Profile Management
     *
     * Public and private profiles per identity.
     * Supports multi-identity architecture with separate visibility controls.
     */
    Route::prefix('profiles')->name('profiles.')->controller(ProfileController::class)->group(function () {
        Route::get('me', 'getMyProfiles')->name('me');
        Route::put('me/public', 'updatePublicProfile')->name('me.public.update');
        Route::put('me/private', 'updatePrivateProfile')->name('me.private.update');
        Route::get('{id}/public', 'getPublicProfile')->name('public.show');
    });

    /**
     * Identity Management (Full CRUD)
     *
     * Create, update, delete identities. Switch active identity.
     * Each identity can have different roles, wallets, and reputation.
     */
    Route::prefix('identities')->name('identities.')->group(function () {
        Route::post('/', [IdentityController::class, 'store'])->name('store');
        Route::put('{identity}', [IdentityController::class, 'update'])->name('update');
        Route::delete('{identity}', [IdentityController::class, 'destroy'])->name('destroy');
        Route::post('switch', [IdentityController::class, 'switch'])->name('switch');
    });

    /**
     * Availability & Scheduling System
     *
     * Real-time availability broadcasting, booking windows, status updates.
     * Supports creator availability notifications and scheduling coordination.
     */
    Route::prefix('availability')->name('availability.')->group(function () {
        Route::get('me', [AvailabilityController::class, 'show'])->name('me.show');
        Route::put('me', [AvailabilityController::class, 'updateMyAvailability'])->name('me.update');
        Route::get('{identity}', [AvailabilityController::class, 'showByIdentity'])->name('identity.show');

        // CRUD operations for availability rules
        Route::apiResource('', AvailabilityController::class, ['parameters' => ['' => 'availabilityRule']]);

        // Real-time status updates (online/offline/busy)
        Route::post('status', [AvailabilityController::class, 'updateStatus'])->name('status.update');
    });

    /**
     * Booking & Scheduling
     *
     * Create booking requests, view user's bookings.
     */
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::post('/', [UserController::class, 'createBookingRequest'])->name('store');
        Route::get('me', [UserController::class, 'getMyBookings'])->name('me');
    });

    /**
     * Messaging System
     *
     * Real-time messaging with WebSocket support, thread management,
     * message status tracking, and media attachments.
     */
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::post('send', [MessageController::class, 'send'])->name('send');
        Route::get('threads', [MessageController::class, 'threads'])->name('threads');
        Route::get('thread/{id}', [MessageController::class, 'show'])->name('thread.show');
        Route::post('{id}/read', [MessageController::class, 'markAsRead'])->name('read');
        Route::delete('{id}', [MessageController::class, 'destroy'])->name('destroy');
    });

});

// =============================================================================
// ROLE & PERMISSION PROTECTED ROUTES
// =============================================================================

/**
 * Administrative Dashboard
 *
 * Admin-only routes for platform management and oversight.
 */
Route::middleware(['auth:sanctum', 'verified', 'role:admin'])->group(function () {
    Route::get('admin-dashboard', fn () => response()->json([
        'data' => ['admin_access' => true, 'timestamp' => now()->toISOString()],
        'meta' => ['success' => true, 'message' => __('admin.dashboard_access')]
    ]))->name('admin.dashboard');
});

/**
 * Permission-Specific Actions
 *
 * Fine-grained permission checks for specific actions.
 */
Route::middleware(['auth:sanctum', 'verified', 'permission:edit-users'])->group(function () {
    Route::post('user/{id}/edit', fn () => response()->json([
        'data' => ['edit_access' => true],
        'meta' => ['success' => true, 'message' => __('users.edit_permission_granted')]
    ]))->name('users.edit.permission');
});

/*
|--------------------------------------------------------------------------
| Route Summary
|--------------------------------------------------------------------------
|
| PUBLIC ROUTES:
| • POST /api/auth/register - User registration
| • POST /api/auth/login - Authentication
| • POST /api/auth/refresh - Token refresh
| • POST /api/auth/google - OAuth initiation
| • POST /api/debug/ping - Health check
|
| AUTHENTICATED ROUTES:
| • GET /api/auth/me - Current user context
| • GET /api/identities - List user identities
| • GET /api/notifications - User notifications
|
| VERIFIED ROUTES:
| • Identity CRUD - /api/identities/*
| • Profile Management - /api/profiles/*
| • Availability System - /api/availability/*
| • Messaging - /api/messages/*
| • Booking System - /api/bookings/*
| • Role/Permission Management - /api/users/{user}/roles/* and /api/users/{user}/permissions/*
|
| ADMIN ROUTES:
| • GET /api/admin-dashboard - Administrative interface
|
| DEVELOPMENT NOTES:
| • All routes return JsonResource wrapped responses
| • UUID-based model binding throughout
| • Multi-identity support on all user-related endpoints
| • Real-time broadcasting ready (WebSocket support)
| • Comprehensive RBAC with granular permissions
|
*/
