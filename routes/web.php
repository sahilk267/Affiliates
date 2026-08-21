<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Consumer routes (public)
Route::get('/', [App\Http\Controllers\ConsumerController::class, 'home'])->name('home');

// Product routes (public)
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');
Route::get('/buy/{productId}/{programId}', [App\Http\Controllers\ProductController::class, 'buy'])->name('products.buy');

// Consumer routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ConsumerController::class, 'dashboard'])->name('dashboard');
    Route::get('/wallet', [App\Http\Controllers\ConsumerController::class, 'wallet'])->name('wallet');
    Route::get('/transactions', [App\Http\Controllers\ConsumerController::class, 'transactions'])->name('transactions');
    Route::get('/referrals', [App\Http\Controllers\ConsumerController::class, 'referrals'])->name('referrals');
    Route::get('/referral-link/{code}', [App\Http\Controllers\ConsumerController::class, 'referralLink'])->name('referral.link');
    Route::post('/generate-referral', [App\Http\Controllers\ConsumerController::class, 'generateReferral'])->name('referral.generate');
    Route::get('/profile', [App\Http\Controllers\ConsumerController::class, 'profile'])->name('profile');
    Route::post('/profile', [App\Http\Controllers\ConsumerController::class, 'updateProfile'])->name('profile.update');
    Route::post('/withdraw', [App\Http\Controllers\ConsumerController::class, 'withdraw'])->name('withdraw');
    Route::get('/gifts', [App\Http\Controllers\ConsumerController::class, 'gifts'])->name('gifts');
    Route::post('/gifts/{giftId}/redeem', [App\Http\Controllers\ConsumerController::class, 'redeemGift'])->name('gifts.redeem');
});

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'database' => 'connected',
        ]);
    } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'status' => 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'database' => 'unavailable',
        ], 503);
    }
});

// Admin routes (JSON API)
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/programs', [AdminController::class, 'programs']);
    Route::get('/links', [AdminController::class, 'links']);
    Route::get('/clicks', [AdminController::class, 'clicks']);
    Route::get('/conversions', [AdminController::class, 'conversions']);
    Route::get('/commissions', [AdminController::class, 'commissions']);
    Route::get('/analytics', [AdminController::class, 'analytics']);
});

// Admin UI routes (HTML views)
Route::prefix('admin/ui')->middleware('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboardView'])->name('admin.dashboard');
    
    // User management routes
    Route::get('/users', [AdminController::class, 'usersView'])->name('admin.users');
    Route::get('/users/create', [AdminController::class, 'createUserView'])->name('admin.users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUserView'])->name('admin.users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

    // Program management routes
    Route::get('/programs', [AdminController::class, 'programsView'])->name('admin.programs');
    Route::get('/programs/create', [AdminController::class, 'createProgramView'])->name('admin.programs.create');
    Route::post('/programs', [AdminController::class, 'storeProgram'])->name('admin.programs.store');
    Route::get('/programs/{program}/edit', [AdminController::class, 'editProgramView'])->name('admin.programs.edit');
    Route::put('/programs/{program}', [AdminController::class, 'updateProgram'])->name('admin.programs.update');
    Route::delete('/programs/{program}', [AdminController::class, 'deleteProgram'])->name('admin.programs.delete');
    
    // Link management routes
    Route::get('/links', [AdminController::class, 'linksView'])->name('admin.links');
    Route::get('/links/create', [AdminController::class, 'createLinkView'])->name('admin.links.create');
    Route::post('/links', [AdminController::class, 'storeLink'])->name('admin.links.store');
    Route::get('/links/{link}/edit', [AdminController::class, 'editLinkView'])->name('admin.links.edit');
    Route::put('/links/{link}', [AdminController::class, 'updateLink'])->name('admin.links.update');
    Route::delete('/links/{link}', [AdminController::class, 'deleteLink'])->name('admin.links.delete');
    Route::post('/links/{link}/toggle', [AdminController::class, 'toggleLinkStatus'])->name('admin.links.toggle');
    Route::get('/clicks', [AdminController::class, 'clicksView'])->name('admin.clicks');
    Route::get('/conversions', [AdminController::class, 'conversionsView'])->name('admin.conversions');
    Route::get('/commissions', [AdminController::class, 'commissionsView'])->name('admin.commissions');
    Route::post('/commissions/{commission}/approve', [AdminController::class, 'approveCommission'])->name('admin.commissions.approve');
    Route::post('/commissions/{commission}/reject', [AdminController::class, 'rejectCommission'])->name('admin.commissions.reject');
    Route::post('/commissions/{commission}/pay', [AdminController::class, 'markCommissionPaid'])->name('admin.commissions.pay');
    Route::get('/analytics', [AdminController::class, 'analyticsView'])->name('admin.analytics');
    Route::get('/api-test', [AdminController::class, 'apiTestView'])->name('admin.api-test');
    Route::post('/api-test/click', [AdminController::class, 'testClickApi'])->name('admin.api-test.click');
    Route::post('/api-test/conversion', [AdminController::class, 'testConversionApi'])->name('admin.api-test.conversion');

    // Product management routes
    Route::get('/products', [App\Http\Controllers\ProductController::class, 'adminIndex'])->name('admin.products.index');
    Route::get('/products/create', [App\Http\Controllers\ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [App\Http\Controllers\ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{id}/edit', [App\Http\Controllers\ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{id}', [App\Http\Controllers\ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{id}', [App\Http\Controllers\ProductController::class, 'destroy'])->name('admin.products.delete');
    
    // Product commission management routes
    Route::get('/products/{productId}/commissions', [App\Http\Controllers\ProductController::class, 'commissions'])->name('admin.products.commissions');
    Route::post('/products/{productId}/commissions', [App\Http\Controllers\ProductController::class, 'storeCommission'])->name('admin.products.commissions.store');
    Route::put('/products/{productId}/commissions/{commissionId}', [App\Http\Controllers\ProductController::class, 'updateCommission'])->name('admin.products.commissions.update');
    Route::delete('/products/{productId}/commissions/{commissionId}', [App\Http\Controllers\ProductController::class, 'deleteCommission'])->name('admin.products.commissions.delete');
    Route::post('/products/{productId}/commissions/import', [App\Http\Controllers\ProductController::class, 'importCommissions'])->name('admin.products.commissions.import');

    // Platform quick-add routes
    Route::get('/programs/quick-add', [AdminController::class, 'quickAddProgramView'])->name('admin.programs.quick-add');
    Route::post('/programs/quick-add', [AdminController::class, 'storeProgramFromTemplate'])->name('admin.programs.quick-add.store');
    Route::post('/programs/import', [AdminController::class, 'importPrograms'])->name('admin.programs.import');

    // Wallet/Points management routes
    Route::get('/wallets', [AdminController::class, 'walletsView'])->name('admin.wallets');
    Route::post('/wallets/{userId}/adjust', [AdminController::class, 'adjustPoints'])->name('admin.wallets.adjust');

    // Cashback settings routes
    Route::get('/cashback-settings', [AdminController::class, 'cashbackSettingsView'])->name('admin.cashback-settings');
    Route::post('/cashback-settings/{programId}', [AdminController::class, 'updateCashbackSettings'])->name('admin.cashback-settings.update');

    // Referral management routes
    Route::get('/referrals', [AdminController::class, 'referralsView'])->name('admin.referrals');

    // Redemption management routes
    Route::get('/redemptions', [AdminController::class, 'redemptionsView'])->name('admin.redemptions');
    Route::post('/redemptions/{redemptionId}/approve', [AdminController::class, 'approveRedemption'])->name('admin.redemptions.approve');
    Route::post('/redemptions/{redemptionId}/reject', [AdminController::class, 'rejectRedemption'])->name('admin.redemptions.reject');
    Route::post('/redemptions/{redemptionId}/complete', [AdminController::class, 'completeRedemption'])->name('admin.redemptions.complete');
});

// Auth routes (session-based)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/auth/status', [AuthController::class, 'status']);

// Password reset routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// SMTP test route (admin only)
Route::get('/test-smtp', [AuthController::class, 'testSmtp'])->name('test.smtp')->middleware('admin');
