<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'service' => 'ZenithSoles API'
    ]);
});

// Affiliate tracking endpoints
Route::prefix('affiliate')->group(function () {
    Route::post('/click', [ApiController::class, 'trackClick'])->middleware('throttle:affiliate-click');
    Route::post('/conversion', [ApiController::class, 'reportConversion'])->middleware(['partner.signature', 'throttle:affiliate-conversion']);
    Route::get('/link/{shortCode}', [ApiController::class, 'getLink']);
    Route::get('/user/{userId}/stats', [ApiController::class, 'getUserStats'])->middleware(['web', 'auth']);
});

// Product endpoints
Route::prefix('products')->group(function () {
    Route::get('/', [App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show']);
});

// Points API endpoints
Route::prefix('points')->group(function () {
    Route::post('/credit', [App\Http\Controllers\ApiController::class, 'creditPoints'])->middleware(['partner.signature', 'throttle:points-credit']);
    Route::get('/balance/{userId}', [App\Http\Controllers\ApiController::class, 'getPointsBalance'])->middleware(['web', 'auth']);
});

// Referral API endpoints
Route::prefix('referral')->group(function () {
    Route::get('/info/{code}', [ApiController::class, 'getReferralInfo'])->middleware(['web', 'auth']);
    Route::post('/track', [ApiController::class, 'trackReferral'])->middleware('throttle:referral-track');
});
