<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Link;
use App\Click;
use App\Conversion;
use App\Commission;
use App\User;
use App\Services\CashbackService;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    protected CashbackService $cashbackService;
    protected ReferralService $referralService;

    public function __construct(CashbackService $cashbackService, ReferralService $referralService)
    {
        $this->cashbackService = $cashbackService;
        $this->referralService = $referralService;
    }
    /**
     * Track a click on an affiliate link
     */
    public function trackClick(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'link_id' => 'required|exists:links,id',
            'ip_address' => 'required|ip',
            'user_agent' => 'required|string|max:500',
            'referrer' => 'nullable|string|max:500',
            'referral_code' => 'nullable|string|max:50', // ⭐ NEW - For referral tracking
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $link = Link::findOrFail($request->link_id);

            // Check if link is valid
            if (!$link->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Link is not valid or has expired'
                ], 400);
            }

            // Track referral if referral code provided
            if ($request->has('referral_code')) {
                $this->referralService->trackReferral($request->referral_code);
            }

            // Create click record
            $click = Click::create([
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'program_id' => $link->program_id,
                'ip_address' => $request->ip_address,
                'user_agent' => $request->user_agent,
                'referrer' => $request->referrer ?? null,
                'country' => $this->getCountryFromIP($request->ip_address),
                'city' => $this->getCityFromIP($request->ip_address),
                'device_type' => $this->getDeviceType($request->user_agent),
                'browser' => $this->getBrowser($request->user_agent),
                'os' => $this->getOS($request->user_agent),
                'clicked_at' => now(),
            ]);

            // Update link click count
            $link->increment('click_count');

            // Generate affiliate URL
            $affiliateUrl = $link->generateAffiliateUrl();

            Log::info('Click tracked', [
                'click_id' => $click->id,
                'link_id' => $link->id,
                'user_id' => $link->user_id,
                'ip_address' => $request->ip_address
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'click_id' => $click->id,
                    'affiliate_url' => $affiliateUrl,
                    'redirect_url' => $affiliateUrl,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Click tracking failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Click tracking failed'
            ], 500);
        }
    }

    /**
     * Report a conversion
     */
    public function reportConversion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'click_id' => 'required|exists:clicks,id',
            'event_type' => 'required|string|in:purchase,signup,download,install,lead,click,other',
            'conversion_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'order_id' => 'nullable|string|max:100',
            'customer_id' => 'nullable|string|max:100',
            'product_id' => 'nullable|string|max:100',
            'product_name' => 'nullable|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'event_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $click = Click::with(['link', 'user', 'program'])->findOrFail($request->click_id);

            // Check if click has already converted
            if ($click->is_converted) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Click has already been converted'
                ], 400);
            }

            // Calculate commission
            $commissionAmount = $this->calculateCommission($click->program, $request->conversion_value);

            // Create conversion record
            $conversion = Conversion::create([
                'click_id' => $click->id,
                'link_id' => $click->link_id,
                'user_id' => $click->user_id,
                'program_id' => $click->program_id,
                'event_type' => $request->event_type,
                'event_data' => $request->event_data,
                'commission_amount' => $commissionAmount,
                'status' => Conversion::STATUS_PENDING,
                'conversion_value' => $request->conversion_value ?? 0,
                'currency' => $request->currency ?? 'INR',
                'order_id' => $request->order_id,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'product_name' => $request->product_name,
                'quantity' => $request->quantity ?? 1,
            ]);

            // Mark click as converted
            $click->markAsConverted();

            // Update link conversion count
            $click->link->increment('conversion_count');
            $click->link->increment('total_commission', $commissionAmount);

            // Create commission record
            $commission = $conversion->commissions()->create([
                'user_id' => $click->user_id,
                'amount' => $commissionAmount,
                'status' => Commission::STATUS_PENDING,
                'commission_type' => Commission::TYPE_AFFILIATE,
            ]);

            // Credit cashback points to user
            $this->cashbackService->creditCashback($conversion);

            // Credit referral points (if applicable)
            $this->referralService->creditReferralPoints($conversion);

            Log::info('Conversion reported', [
                'conversion_id' => $conversion->id,
                'click_id' => $click->id,
                'user_id' => $click->user_id,
                'commission_amount' => $commissionAmount
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'conversion_id' => $conversion->id,
                    'commission_amount' => $commissionAmount,
                    'status' => $conversion->status,
                    'points_credited' => true,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Conversion reporting failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Conversion reporting failed'
            ], 500);
        }
    }

    /**
     * Get link information
     */
    public function getLink(Request $request, $shortCode)
    {
        try {
            $link = Link::where('short_code', $shortCode)
                ->with(['user', 'program'])
                ->first();

            if (!$link) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Link not found'
                ], 404);
            }

            if (!$link->isValid()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Link is not valid or has expired'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'link_id' => $link->id,
                    'original_url' => $link->original_url,
                    'affiliate_url' => $link->generateAffiliateUrl(),
                    'program' => $link->program->name,
                    'user' => $link->user->name,
                    'click_count' => $link->click_count,
                    'conversion_count' => $link->conversion_count,
                    'conversion_rate' => $link->conversion_rate,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get link failed', [
                'error' => $e->getMessage(),
                'short_code' => $shortCode
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get link information'
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $stats = [
                'total_clicks' => $user->clicks()->count(),
                'total_conversions' => $user->conversions()->count(),
                'total_commission' => $user->total_commission,
                'pending_commission' => $user->pending_commission,
                'conversion_rate' => $user->clicks()->count() > 0 
                    ? ($user->conversions()->count() / $user->clicks()->count()) * 100 
                    : 0,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Get user stats failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get user statistics'
            ], 500);
        }
    }

    /**
     * Calculate commission based on program structure
     */
    private function calculateCommission($program, $conversionValue)
    {
        $commissionStructure = $program->commission_structure;
        
        if (!$commissionStructure) {
            return 0;
        }

        // Simple percentage-based commission
        if (isset($commissionStructure['percentage'])) {
            return ($conversionValue ?? 0) * ($commissionStructure['percentage'] / 100);
        }

        // Fixed commission
        if (isset($commissionStructure['fixed'])) {
            return $commissionStructure['fixed'];
        }

        return 0;
    }

    // ========== POINTS API METHODS ==========

    /**
     * Credit points to user (API)
     */
    public function creditPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'points' => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $pointsService = app(\App\Services\PointsService::class);
            
            $transaction = $pointsService->creditPoints(
                $request->user_id,
                $request->points,
                $request->description,
                $request->reference_type ?? \App\PointsTransaction::REF_BONUS,
                $request->reference_id
            );

            if (!$transaction) {
                throw new \Exception('Failed to credit points');
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'points' => $transaction->points,
                    'balance_after' => $transaction->balance_after,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to credit points', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to credit points: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get points balance (API)
     */
    public function getPointsBalance(Request $request, $userId)
    {
        try {
            $pointsService = app(\App\Services\PointsService::class);
            $balance = $pointsService->getBalance($userId);
            $stats = $pointsService->getPointsStats($userId);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'balance' => $stats['balance'],
                    'pending_balance' => $stats['pending_balance'],
                    'total_earned' => $stats['total_earned'],
                    'total_redeemed' => $stats['total_redeemed'],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get points balance', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get points balance'
            ], 500);
        }
    }

    // ========== REFERRAL API METHODS ==========

    /**
     * Track referral click (API)
     */
    public function trackReferral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'referral_code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $referral = $this->referralService->trackReferral($request->referral_code);

            if (!$referral) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid referral code'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'referral_code' => $referral->referral_code,
                    'referrer_id' => $referral->referrer_id,
                    'program_id' => $referral->program_id,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track referral', [
                'error' => $e->getMessage(),
                'referral_code' => $request->referral_code
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track referral'
            ], 500);
        }
    }

    /**
     * Get referral information (API)
     */
    public function getReferralInfo($code)
    {
        try {
            $referral = \App\Referral::where('referral_code', $code)
                ->with(['referrer', 'program'])
                ->first();

            if (!$referral) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Referral code not found'
                ], 404);
            }

            $stats = $this->referralService->getReferralStats($referral->referrer_id);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'referral_code' => $referral->referral_code,
                    'referrer' => [
                        'id' => $referral->referrer->id,
                        'name' => $referral->referrer->name,
                    ],
                    'program' => $referral->program ? [
                        'id' => $referral->program->id,
                        'name' => $referral->program->name,
                    ] : null,
                    'status' => $referral->status,
                    'stats' => $stats,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get referral info', [
                'error' => $e->getMessage(),
                'code' => $code
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get referral information'
            ], 500);
        }
    }

    /**
     * Get country from IP address (simplified)
     */
    private function getCountryFromIP($ip)
    {
        // This is a simplified implementation
        // In production, you would use a proper GeoIP service
        return 'IN'; // Default to India
    }

    /**
     * Get city from IP address (simplified)
     */
    private function getCityFromIP($ip)
    {
        // This is a simplified implementation
        // In production, you would use a proper GeoIP service
        return 'Mumbai'; // Default to Mumbai
    }

    /**
     * Get device type from user agent
     */
    private function getDeviceType($userAgent)
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Get browser from user agent
     */
    private function getBrowser($userAgent)
    {
        if (preg_match('/Chrome/', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/', $userAgent)) return 'Safari';
        if (preg_match('/Edge/', $userAgent)) return 'Edge';
        return 'Unknown';
    }

    /**
     * Get operating system from user agent
     */
    private function getOS($userAgent)
    {
        if (preg_match('/Windows/', $userAgent)) return 'Windows';
        if (preg_match('/Mac/', $userAgent)) return 'macOS';
        if (preg_match('/Linux/', $userAgent)) return 'Linux';
        if (preg_match('/Android/', $userAgent)) return 'Android';
        if (preg_match('/iOS/', $userAgent)) return 'iOS';
        return 'Unknown';
    }
}
