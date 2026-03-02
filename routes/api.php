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
    Route::post('/click', [ApiController::class, 'trackClick']);
    Route::post('/conversion', [ApiController::class, 'reportConversion']);
    Route::get('/link/{shortCode}', [ApiController::class, 'getLink']);
    Route::get('/user/{userId}/stats', [ApiController::class, 'getUserStats']);
});

// Product endpoints
Route::prefix('products')->group(function () {
    Route::get('/', [App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/{id}', [App\Http\Controllers\ProductController::class, 'show']);
});

// Points API endpoints
Route::prefix('points')->group(function () {
    Route::post('/credit', [App\Http\Controllers\ApiController::class, 'creditPoints']);
    Route::get('/balance/{userId}', [App\Http\Controllers\ApiController::class, 'getPointsBalance']);
});

// Referral API endpoints
Route::prefix('referral')->group(function () {
    Route::get('/{code}', [App\Http\Controllers\ApiController::class, 'getReferralInfo']);
    Route::post('/track', [App\Http\Controllers\ApiController::class, 'trackReferral']);
});
