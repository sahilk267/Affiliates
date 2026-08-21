<?php

namespace App\Services;

use App\Program;
use App\CashbackSetting;
use App\Services\PointsService;
use App\Conversion;
use Illuminate\Support\Facades\Log;

class CashbackService
{
    protected PointsService $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Calculate cashback amount for a purchase
     *
     * @param Program $program
     * @param float $purchaseValue
     * @return float
     */
    public function calculateCashback(Program $program, float $purchaseValue): float
    {
        $cashbackSetting = CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->first();

        if (!$cashbackSetting) {
            Log::warning('Cashback setting not found for program', [
                'program_id' => $program->id,
            ]);
            return 0.0;
        }

        return $cashbackSetting->calculateCashback($purchaseValue);
    }

    /**
     * Calculate points for cashback amount
     *
     * @param Program $program
     * @param float $cashbackAmount
     * @return int
     */
    public function calculatePoints(Program $program, float $cashbackAmount): int
    {
        $cashbackSetting = CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->first();

        if (!$cashbackSetting) {
            return 0;
        }

        return $cashbackSetting->calculatePoints($cashbackAmount);
    }

    /**
     * Credit cashback points to user
     *
     * @param Conversion $conversion
     * @return bool
     */
    public function creditCashback(Conversion $conversion): bool
    {
        try {
            $program = $conversion->program;
            $purchaseValue = (float) ($conversion->conversion_value ?? $conversion->order_value ?? 0);

            if ($purchaseValue <= 0) {
                Log::warning('Invalid purchase value for cashback', [
                    'conversion_id' => $conversion->id,
                    'partner_event_id' => $conversion->partner_event_id,
                    'click_id' => $conversion->click_id,
                    'user_id' => $conversion->user_id,
                    'order_id' => $conversion->order_id,
                ]);
                return false;
            }

            // Calculate cashback
            $cashbackAmount = $this->calculateCashback($program, $purchaseValue);

            if ($cashbackAmount <= 0) {
                Log::info('No cashback for conversion', [
                    'conversion_id' => $conversion->id,
                    'partner_event_id' => $conversion->partner_event_id,
                    'click_id' => $conversion->click_id,
                    'user_id' => $conversion->user_id,
                    'order_id' => $conversion->order_id,
                    'purchase_value' => $purchaseValue,
                ]);
                return false;
            }

            // Calculate points
            $points = $this->calculatePoints($program, $cashbackAmount);

            if ($points <= 0) {
                return false;
            }

            // Credit points to user
            $transaction = $this->pointsService->creditPoints(
                $conversion->user_id,
                $points,
                "Cashback for purchase - Order: {$conversion->order_id}",
                \App\PointsTransaction::REF_PURCHASE_CASHBACK,
                $conversion->id,
                'cashback-conversion-' . $conversion->id
            );

            if ($transaction) {
                Log::info('Cashback credited', [
                    'conversion_id' => $conversion->id,
                    'partner_event_id' => $conversion->partner_event_id,
                    'click_id' => $conversion->click_id,
                    'user_id' => $conversion->user_id,
                    'order_id' => $conversion->order_id,
                    'cashback_amount' => $cashbackAmount,
                    'points' => $points,
                    'idempotency_key' => 'cashback-conversion-' . $conversion->id,
                    'transaction_id' => $transaction->id,
                ]);
                return true;
            }

            throw new \RuntimeException('Cashback points transaction was not created');
        } catch (\Throwable $e) {
            Log::error('Failed to credit cashback', [
                'conversion_id' => $conversion->id,
                'partner_event_id' => $conversion->partner_event_id,
                'click_id' => $conversion->click_id,
                'user_id' => $conversion->user_id,
                'order_id' => $conversion->order_id,
                'idempotency_key' => 'cashback-conversion-' . $conversion->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get cashback rate for a program
     *
     * @param Program $program
     * @return float
     */
    public function getCashbackRate(Program $program): float
    {
        $cashbackSetting = CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->first();

        return $cashbackSetting ? (float) $cashbackSetting->cashback_rate : 0.0;
    }

    /**
     * Get referral rate for a program
     *
     * @param Program $program
     * @return float
     */
    public function getReferralRate(Program $program): float
    {
        $cashbackSetting = CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->first();

        return $cashbackSetting ? (float) $cashbackSetting->referral_rate : 0.0;
    }

    /**
     * Calculate referral commission for a purchase
     *
     * @param Program $program
     * @param float $purchaseValue
     * @return float
     */
    public function calculateReferralCommission(Program $program, float $purchaseValue): float
    {
        $cashbackSetting = CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->first();

        if (!$cashbackSetting) {
            return 0.0;
        }

        return $cashbackSetting->calculateReferralCommission($purchaseValue);
    }

    /**
     * Check if program supports cashback
     *
     * @param Program $program
     * @return bool
     */
    public function supportsCashback(Program $program): bool
    {
        return CashbackSetting::where('program_id', $program->id)
            ->where('status', CashbackSetting::STATUS_ACTIVE)
            ->exists();
    }
}

