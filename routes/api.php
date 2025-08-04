<?php

use App\Events\TestPingEvent;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserController;
use App\Middleware\RoleMiddleware;
use App\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api and assigned the "api" middleware group.
| Organize endpoints by access level: Public, Authenticated, Verified.
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/google', [GoogleAuthController::class, 'handle']);
Route::post('/google/complete', [GoogleAuthController::class, 'complete']);

/*
|--------------------------------------------------------------------------
| Email Verification Routes (Requires Authentication)
|--------------------------------------------------------------------------
*/
Route::prefix('email')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json([
            'message' => __('auth.verification_success'),
        ]);
    })->middleware('signed')->name('verification.verify');

    Route::post('/resend', [AuthController::class, 'resendVerificationEmail']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Requires Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Basic authenticated actions
    Route::get('/me', [UserController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Verified Routes (Requires Login + Verified Email)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('users')->middleware(['auth:sanctum', 'verified'])->group(function () {
    // Single-item role attach/detach
    Route::post('/{user}/roles/{role}/attach', [UserController::class, 'attachRole']);
    Route::post('/{user}/roles/{role}/detach', [UserController::class, 'detachRole']);

    // Single-item permission attach/detach
    Route::post('/{user}/permissions/{permission}/attach', [UserController::class, 'attachPermission']);
    Route::post('/{user}/permissions/{permission}/detach', [UserController::class, 'detachPermission']);

    // Bulk role/permission attach/detach
    Route::post('/{user}/assign', [UserController::class, 'assignRolesAndPermissions']);
    Route::post('/{user}/remove', [UserController::class, 'removeRolesAndPermissions']);
});

Route::prefix('profiles')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/me', [UserController::class, 'getMyProfiles']);
    Route::put('/me/public', [UserController::class, 'updatePublicProfile']);
    Route::put('/me/private', [UserController::class, 'updatePrivateProfile']);
    Route::get('/{id}/public', [UserController::class, 'getPublicProfile']);
});

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/availability/me', [UserController::class, 'getMyAvailability']);
    Route::put('/availability/me', [UserController::class, 'updateMyAvailability']);
    Route::get('/availability/{user}', [UserController::class, 'getUserAvailability']);

    Route::post('/bookings', [UserController::class, 'createBookingRequest']);
    Route::get('/bookings/me', [UserController::class, 'getMyBookings']);
});

Route::middleware(['role:admin'])->get('/admin-dashboard', function () {
    // Only accessible to users with role 'admin'
});

Route::middleware(['permission:edit-users'])->post('/user/{id}/edit', function () {
    // Only accessible to users with permission 'edit-users'
});

Route::prefix('messages')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/send', [UserController::class, 'sendMessage']);
    Route::get('/threads', [UserController::class, 'getThreads']);
    Route::get('/thread/{id}', [UserController::class, 'getThreadMessages']);
    Route::post('/{id}/read', [UserController::class, 'markAsRead']);
    Route::delete('/{id}', [UserController::class, 'deleteMessage']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/notifications/send', [UserController::class, 'notify']);
    Route::get('/notifications', [UserController::class, 'notifications']);
    Route::post('/notifications/{id}/read', [UserController::class, 'markNotificationAsRead']);
});

Route::post('/debug/ping', function () {
    broadcast(new TestPingEvent('pong'));
    return response()->json(['message' => 'Ping sent.']);
});
