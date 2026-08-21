<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Link;
use App\Click;
use App\Conversion;
use App\Commission;
use App\User;
use App\Services\AffiliateTrackingService;
use App\Services\CashbackService;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    protected AffiliateTrackingService $trackingService;
    protected CashbackService $cashbackService;
    protected ReferralService $referralService;

    public function __construct(
        AffiliateTrackingService $trackingService,
        CashbackService $cashbackService,
        ReferralService $referralService
    )
    {
        $this->trackingService = $trackingService;
        $this->cashbackService = $cashbackService;
        $this->referralService = $referralService;
    }
    /**
     * Track a click on an affiliate link.
     */
    public function trackClick(Request $request)
    {
        $requestId = (string) ($request->header('X-Request-ID') ?: Str::uuid());
        $validator = Validator::make($request->all(), [
            'link_id' => 'required|integer|exists:links,id',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:500',
            'referrer' => 'nullable|string|max:500',
            'referral_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $link = Link::findOrFail($request->integer('link_id'));
            $click = $this->trackingService->track($link, $request);
            $affiliateUrl = $link->generateAffiliateUrl();
            $separator = str_contains($affiliateUrl, '?') ? '&' : '?';

            return response()->json([
                'status' => 'success',
                'data' => [
                    'click_id' => $click->id,
                    'affiliate_url' => $affiliateUrl . $separator . http_build_query(['click_id' => $click->id]),
                    'redirect_url' => $affiliateUrl . $separator . http_build_query(['click_id' => $click->id]),
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            Log::error('Click tracking failed', [
                'request_id' => $requestId,
                'link_id' => $request->input('link_id'),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Click tracking failed'], 500);
        }
    }

    /**
     * Report a conversion from an authenticated partner integration.
     */
    public function reportConversion(Request $request)
    {
        $requestId = (string) ($request->header('X-Request-ID') ?: Str::uuid());
        $partnerEventId = (string) ($request->input('partner_event_id') ?: $request->header('Idempotency-Key'));
        $validator = Validator::make(array_merge($request->all(), [
            'partner_event_id' => $partnerEventId,
        ]), [
            'click_id' => 'required|integer|exists:clicks,id',
            'partner_event_id' => 'required|string|max:100',
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
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $existing = Conversion::where('partner_event_id', $partnerEventId)->first();
            if ($existing) {
                Log::info('Conversion report replayed idempotently', [
                    'request_id' => $requestId,
                    'conversion_id' => $existing->id,
                    'partner_event_id' => $partnerEventId,
                    'click_id' => $existing->click_id,
                    'user_id' => $existing->user_id,
                ]);

                return response()->json([
                    'status' => 'success',
                    'idempotent' => true,
                    'data' => [
                        'conversion_id' => $existing->id,
                        'commission_amount' => $existing->commission_amount,
                        'status' => $existing->status,
                        'points_credited' => true,
                    ],
                ]);
            }

            $result = DB::transaction(function () use ($request, $partnerEventId) {
                $click = Click::with(['link', 'user', 'program'])
                    ->lockForUpdate()
                    ->findOrFail($request->integer('click_id'));

                if ($click->is_converted) {
                    throw new \DomainException('Click has already been converted');
                }

                $conversionValue = (float) ($request->input('conversion_value') ?? 0);
                $commissionAmount = $this->calculateCommission($click->program, $conversionValue);
                $conversion = Conversion::create([
                    'click_id' => $click->id,
                    'link_id' => $click->link_id,
                    'user_id' => $click->user_id,
                    'program_id' => $click->program_id,
                    'event_type' => $request->input('event_type'),
                    'event_data' => $request->input('event_data'),
                    'commission_amount' => $commissionAmount,
                    'status' => Conversion::STATUS_PENDING,
                    'conversion_id' => 'conv_' . Str::uuid(),
                    'partner_event_id' => $partnerEventId,
                    'conversion_value' => $conversionValue,
                    'order_value' => $conversionValue,
                    'currency' => strtoupper($request->input('currency', 'INR')),
                    'order_id' => $request->input('order_id'),
                    'customer_id' => $request->input('customer_id'),
                    'product_id' => $request->input('product_id'),
                    'product_name' => $request->input('product_name'),
                    'quantity' => $request->input('quantity', 1),
                    'converted_at' => now(),
                ]);

                $click->markAsConverted();
                $click->link->increment('conversion_count');
                $click->link->increment('total_commission', $commissionAmount);

                $conversion->commissions()->create([
                    'user_id' => $click->user_id,
                    'amount' => $commissionAmount,
                    'status' => Commission::STATUS_PENDING,
                    'commission_type' => Commission::TYPE_AFFILIATE,
                    'currency' => strtoupper($request->input('currency', 'INR')),
                ]);

                $cashbackCredited = $this->cashbackService->creditCashback($conversion);
                $referralCredited = $this->referralService->creditReferralPoints($conversion);
                $conversion->update(['processed_at' => now()]);

                return [
                    'conversion' => $conversion->fresh(),
                    'cashback_credited' => $cashbackCredited,
                    'referral_credited' => $referralCredited,
                ];
            });

            $conversion = $result['conversion'];
            Log::info('Conversion reported', [
                'request_id' => $requestId,
                'conversion_id' => $conversion->id,
                'partner_event_id' => $partnerEventId,
                'click_id' => $conversion->click_id,
                'user_id' => $conversion->user_id,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'conversion_id' => $conversion->id,
                    'commission_amount' => $conversion->commission_amount,
                    'status' => $conversion->status,
                    'cashback_points_credited' => $result['cashback_credited'],
                    'referral_points_credited' => $result['referral_credited'],
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Throwable $e) {
            Log::error('Conversion reporting failed', [
                'request_id' => $requestId,
                'click_id' => $request->input('click_id'),
                'partner_event_id' => $partnerEventId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Conversion reporting failed',
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
            if (!Auth::check() || ((int) Auth::id() !== (int) $userId && !Auth::user()->isAdmin())) {
                return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
            }

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
     * Credit points to a user through an authenticated partner integration.
     */
    public function creditPoints(Request $request)
    {
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        $validator = Validator::make(array_merge($request->all(), [
            'idempotency_key' => $idempotencyKey,
        ]), [
            'user_id' => 'required|integer|exists:users,id',
            'points' => 'required|integer|min:1|max:1000000',
            'description' => 'required|string|max:255',
            'reference_type' => 'nullable|string|in:purchase_cashback,referral,redemption,gift,bonus,adjustment',
            'reference_id' => 'nullable|integer',
            'idempotency_key' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pointsService = app(\App\Services\PointsService::class);
            $transaction = $pointsService->creditPoints(
                $request->integer('user_id'),
                $request->integer('points'),
                $request->string('description')->toString(),
                $request->input('reference_type', \App\PointsTransaction::REF_BONUS),
                $request->integer('reference_id') ?: null,
                $idempotencyKey
            );

            if (!$transaction) {
                throw new \RuntimeException('Failed to credit points');
            }

            return response()->json([
                'status' => 'success',
                'idempotent' => $transaction->idempotency_key === $idempotencyKey,
                'data' => [
                    'transaction_id' => $transaction->id,
                    'points' => $transaction->points,
                    'balance_after' => $transaction->balance_after,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to credit points', [
                'error' => $e->getMessage(),
                'user_id' => $request->input('user_id'),
                'idempotency_key' => $idempotencyKey,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to credit points',
            ], 500);
        }
    }

    /**
     * Get points balance (API)
     */
    public function getPointsBalance(Request $request, $userId)
    {
        try {
            if (!Auth::check() || ((int) Auth::id() !== (int) $userId && !Auth::user()->isAdmin())) {
                return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
            }

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

            if (!Auth::check() || ((int) Auth::id() !== (int) $referral->referrer_id && !Auth::user()->isAdmin())) {
                return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
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

}
