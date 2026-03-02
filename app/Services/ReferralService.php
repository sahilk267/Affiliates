<?php

namespace App\Services;

use App\User;
use App\Program;
use App\Referral;
use App\Conversion;
use App\Services\PointsService;
use App\Services\CashbackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;

class ReferralService
{
    protected PointsService $pointsService;
    protected CashbackService $cashbackService;

    public function __construct(PointsService $pointsService, CashbackService $cashbackService)
    {
        $this->pointsService = $pointsService;
        $this->cashbackService = $cashbackService;
    }

    /**
     * Generate referral code for user
     *
     * @param int $userId
     * @param int|null $programId
     * @return Referral
     */
    public function generateReferralCode(int $userId, ?int $programId = null): Referral
    {
        // Check if referral already exists
        $existingReferral = Referral::where('referrer_id', $userId)
            ->when($programId, function ($query) use ($programId) {
                return $query->where('program_id', $programId);
            })
            ->first();

        if ($existingReferral) {
            return $existingReferral;
        }

        // Generate new referral code
        $referralCode = Referral::generateReferralCode($userId, $programId);

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $userId,
            'referral_code' => $referralCode,
            'program_id' => $programId,
            'status' => Referral::STATUS_PENDING,
        ]);

        Log::info('Referral code generated', [
            'user_id' => $userId,
            'program_id' => $programId,
            'referral_code' => $referralCode,
        ]);

        return $referral;
    }

    /**
     * Track referral click (store in cookie/session)
     *
     * @param string $referralCode
     * @return Referral|null
     */
    public function trackReferral(string $referralCode): ?Referral
    {
        $referral = Referral::where('referral_code', $referralCode)->first();

        if (!$referral) {
            Log::warning('Invalid referral code', ['referral_code' => $referralCode]);
            return null;
        }

        // Store referral code in cookie (30 days)
        Cookie::queue('referral_code', $referralCode, 30 * 24 * 60);

        Log::info('Referral tracked', [
            'referral_code' => $referralCode,
            'referrer_id' => $referral->referrer_id,
        ]);

        return $referral;
    }

    /**
     * Get referral code from cookie
     *
     * @return string|null
     */
    public function getReferralCodeFromCookie(): ?string
    {
        return request()->cookie('referral_code');
    }

    /**
     * Link user to referrer (when user signs up or makes purchase)
     *
     * @param int $userId
     * @param string|null $referralCode
     * @return Referral|null
     */
    public function linkUserToReferrer(int $userId, ?string $referralCode = null): ?Referral
    {
        // Get referral code from parameter or cookie
        $code = $referralCode ?? $this->getReferralCodeFromCookie();

        if (!$code) {
            return null;
        }

        $referral = Referral::where('referral_code', $code)->first();

        if (!$referral) {
            return null;
        }

        // Prevent self-referral
        if ($referral->referrer_id === $userId) {
            Log::warning('Self-referral attempted', [
                'user_id' => $userId,
                'referral_code' => $code,
            ]);
            return null;
        }

        // Update referral record
        if (!$referral->referred_id) {
            $referral->update([
                'referred_id' => $userId,
                'status' => Referral::STATUS_ACTIVE,
            ]);

            Log::info('User linked to referrer', [
                'user_id' => $userId,
                'referrer_id' => $referral->referrer_id,
                'referral_code' => $code,
            ]);
        }

        return $referral;
    }

    /**
     * Calculate referral commission
     *
     * @param Program $program
     * @param float $purchaseValue
     * @return float
     */
    public function calculateReferralCommission(Program $program, float $purchaseValue): float
    {
        // Check if program supports referrals (non-e-commerce only)
        if (!$program->supports_sub_affiliate) {
            return 0.0;
        }

        return $this->cashbackService->calculateReferralCommission($program, $purchaseValue);
    }

    /**
     * Credit referral points to referrer
     *
     * @param Conversion $conversion
     * @return bool
     */
    public function creditReferralPoints(Conversion $conversion): bool
    {
        try {
            $program = $conversion->program;

            // Check if program supports referrals
            if (!$program->supports_sub_affiliate) {
                return false;
            }

            // Find referral for this conversion
            $referral = Referral::where('referred_id', $conversion->user_id)
                ->where('program_id', $program->id)
                ->where('status', Referral::STATUS_ACTIVE)
                ->first();

            if (!$referral) {
                // Try to find any active referral for this user
                $referral = Referral::where('referred_id', $conversion->user_id)
                    ->where('status', Referral::STATUS_ACTIVE)
                    ->first();
            }

            if (!$referral) {
                Log::info('No referral found for conversion', [
                    'conversion_id' => $conversion->id,
                    'user_id' => $conversion->user_id,
                ]);
                return false;
            }

            $purchaseValue = (float) ($conversion->conversion_value ?? $conversion->order_value ?? 0);

            if ($purchaseValue <= 0) {
                return false;
            }

            // Calculate referral commission
            $referralCommission = $this->calculateReferralCommission($program, $purchaseValue);

            if ($referralCommission <= 0) {
                return false;
            }

            // Calculate points
            $cashbackSetting = \App\CashbackSetting::where('program_id', $program->id)
                ->where('status', \App\CashbackSetting::STATUS_ACTIVE)
                ->first();

            if (!$cashbackSetting) {
                return false;
            }

            $points = $cashbackSetting->calculatePoints($referralCommission);

            if ($points <= 0) {
                return false;
            }

            // Credit points to referrer
            $transaction = $this->pointsService->creditPoints(
                $referral->referrer_id,
                $points,
                "Referral commission - User: {$conversion->user_id}, Order: {$conversion->order_id}",
                \App\PointsTransaction::REF_REFERRAL,
                $referral->id
            );

            if ($transaction) {
                // Update referral stats
                $referral->incrementConversions();
                $referral->addPointsEarned($points);

                Log::info('Referral points credited', [
                    'conversion_id' => $conversion->id,
                    'referrer_id' => $referral->referrer_id,
                    'referred_id' => $conversion->user_id,
                    'referral_commission' => $referralCommission,
                    'points' => $points,
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to credit referral points', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get referral statistics for user
     *
     * @param int $userId
     * @return array
     */
    public function getReferralStats(int $userId): array
    {
        $referrals = Referral::where('referrer_id', $userId)->get();

        $totalReferrals = $referrals->count();
        $activeReferrals = $referrals->where('status', Referral::STATUS_ACTIVE)->count();
        $totalConversions = $referrals->sum('total_conversions');
        $totalPointsEarned = $referrals->sum('total_points_earned');

        $conversionRate = $totalReferrals > 0 
            ? ($totalConversions / $totalReferrals) * 100 
            : 0;

        return [
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'total_conversions' => $totalConversions,
            'total_points_earned' => $totalPointsEarned,
            'conversion_rate' => round($conversionRate, 2),
        ];
    }
}

