<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductService;
use App\Services\PayoutService;
use App\Services\PointsService;
use App\Services\ReferralService;
use App\Services\CashbackService;
use App\Product;
use App\Gift;
use App\PointsRedemption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ConsumerController extends Controller
{
    protected ProductService $productService;
    protected PointsService $pointsService;
    protected PayoutService $payoutService;
    protected ReferralService $referralService;
    protected CashbackService $cashbackService;

    public function __construct(
        ProductService $productService,
        PointsService $pointsService,
        PayoutService $payoutService,
        ReferralService $referralService,
        CashbackService $cashbackService
    ) {
        $this->productService = $productService;
        $this->pointsService = $pointsService;
        $this->payoutService = $payoutService;
        $this->referralService = $referralService;
        $this->cashbackService = $cashbackService;
    }

    /**
     * Home page
     */
    public function home()
    {
        // Get featured products (highest commission)
        $featuredProducts = $this->productService->getProductsByCommission(8);
        
        // Get latest products
        $latestProducts = Product::where('status', Product::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return view('home', compact('featuredProducts', 'latestProducts'));
    }

    /**
     * User dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Points stats
        $pointsStats = $this->pointsService->getPointsStats($user->id);
        
        // Referral stats
        $referralStats = $this->referralService->getReferralStats($user->id);
        
        // Recent transactions
        $recentTransactions = $this->pointsService->getTransactionHistory($user->id, 10);

        return view('dashboard.index', compact('pointsStats', 'referralStats', 'recentTransactions'));
    }

    /**
     * Wallet/Points page
     */
    public function wallet()
    {
        $user = auth()->user();
        
        $pointsBalance = $this->pointsService->getBalance($user->id);
        $transactions = $this->pointsService->getTransactionHistory($user->id, 50);

        return view('wallet.index', compact('pointsBalance', 'transactions'));
    }

    /**
     * Transaction history
     */
    public function transactions(Request $request)
    {
        $user = auth()->user();
        
        $filters = [
            'type' => $request->get('type'), // credit, debit
            'reference_type' => $request->get('reference_type'),
        ];

        $query = \App\PointsTransaction::where('user_id', $user->id);

        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        if ($filters['reference_type']) {
            $query->where('reference_type', $filters['reference_type']);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('wallet.transactions', compact('transactions', 'filters'));
    }

    /**
     * Referrals page
     */
    public function referrals()
    {
        $user = auth()->user();
        
        // Get or generate referral code
        $referral = $this->referralService->generateReferralCode($user->id);
        
        // Get referral stats
        $referralStats = $this->referralService->getReferralStats($user->id);
        
        // Get all referrals
        $referrals = \App\Referral::where('referrer_id', $user->id)
            ->with(['referred', 'program'])
            ->orderBy('created_at', 'desc')
            ->get();

        $referralLink = url('/referral-link/' . $referral->referral_code);

        return view('referrals.index', compact('referral', 'referralStats', 'referrals', 'referralLink'));
    }

    /**
     * Handle referral link click
     */
    public function referralLink($code)
    {
        $referral = $this->referralService->trackReferral($code);
        
        if (!$referral) {
            return redirect('/')->with('error', 'Invalid referral link');
        }

        // If user is logged in, link them to referrer
        if (auth()->check()) {
            $this->referralService->linkUserToReferrer(auth()->id(), $code);
        }

        // Redirect to home or registration
        return redirect('/')->with('success', 'Referral link activated!');
    }

    /**
     * Generate referral code
     */
    public function generateReferral(Request $request)
    {
        $user = auth()->user();
        $programId = $request->get('program_id');

        $referral = $this->referralService->generateReferralCode($user->id, $programId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'referral_code' => $referral->referral_code,
                'referral_link' => url('/referral-link/' . $referral->referral_code),
            ]
        ]);
    }

    /**
     * User profile page
     */
    public function profile()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bank_account' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $user->update($request->only([
                'name', 'phone', 'address', 'bank_account', 'ifsc_code', 'pan_number'
            ]));

            return redirect()->route('profile')
                ->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to update profile', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Failed to update profile')
                ->withInput();
        }
    }

    /**
     * Withdrawal request
     */
    public function withdraw(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'points' => 'required|integer|min:100',
            'idempotency_key' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $idempotencyKey = (string) ($request->header('Idempotency-Key') ?: $request->input('idempotency_key'));

        try {
            $redemption = $this->payoutService->createWithdrawal(
                $user,
                $request->integer('points'),
                $idempotencyKey ?: null
            );

            return redirect()->route('wallet')
                ->with('success', 'Withdrawal request submitted successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to process withdrawal', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Gifts catalog
     */
    public function gifts()
    {
        $gifts = Gift::where('status', Gift::STATUS_ACTIVE)
            ->where('stock', '>', 0)
            ->orderBy('points_required', 'asc')
            ->get();

        $user = auth()->user();
        $pointsBalance = $this->pointsService->getAvailableBalance($user->id);

        return view('gifts.index', compact('gifts', 'pointsBalance'));
    }

    /**
     * Redeem gift
     */
    public function redeemGift(Request $request, $giftId)
    {
        $user = auth()->user();
        $gift = Gift::findOrFail($giftId);

        // Check if gift is available
        if (!$gift->isAvailable()) {
            return redirect()->back()
                ->with('error', 'Gift is not available');
        }

        // Check if user has enough points
        if (!$this->pointsService->hasEnoughPoints($user->id, $gift->points_required)) {
            return redirect()->back()
                ->with('error', 'Insufficient points. You need ' . $gift->points_required . ' points.');
        }

        try {
            DB::beginTransaction();

            // Debit points
            $transaction = $this->pointsService->debitPoints(
                $user->id,
                $gift->points_required,
                "Gift redemption: {$gift->name}",
                \App\PointsTransaction::REF_GIFT,
                $gift->id
            );

            if (!$transaction) {
                throw new \Exception('Failed to debit points');
            }

            // Decrement gift stock
            $gift->decrementStock();

            // Create redemption request
            $redemption = PointsRedemption::create([
                'user_id' => $user->id,
                'redemption_type' => PointsRedemption::TYPE_GIFT,
                'points_used' => $gift->points_required,
                'gift_id' => $gift->id,
                'status' => PointsRedemption::STATUS_PENDING,
            ]);

            DB::commit();

            return redirect()->route('gifts')
                ->with('success', 'Gift redemption request submitted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to redeem gift', [
                'user_id' => $user->id,
                'gift_id' => $giftId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to redeem gift: ' . $e->getMessage());
        }
    }
}

