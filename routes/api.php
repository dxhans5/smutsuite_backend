<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and assigned to the "api"
| middleware group. Build something badass.
|
*/

// Public
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/google', [GoogleAuthController::class, 'handle']);
Route::post('/google/complete', [GoogleAuthController::class, 'complete']);

// Email Verification
Route::prefix('email')->middleware('auth:sanctum')->group(function () {
    Route::get('/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return response()->json([
            'message' => __('auth.verification_success'),
        ]);
    })->middleware('signed')->name('verification.verify');

    Route::post('/resend', [AuthController::class, 'resendVerificationEmail']);
});


// Authenticated
Route::middleware(['auth:sanctum', 'verified'])->prefix('auth')->group(function () {
    Route::post('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
