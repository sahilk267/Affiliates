<?php

namespace App\Services;

use App\Program;
use App\Conversion;
use App\Commission;
use App\ProductCommission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Calculate commission based on program structure
     *
     * @param Program $program
     * @param float $conversionValue
     * @param int|null $productId
     * @return float
     */
    public function calculateCommission(Program $program, float $conversionValue, ?int $productId = null): float
    {
        // If product ID is provided, try to get product-specific commission
        if ($productId) {
            $productCommission = ProductCommission::where('product_id', $productId)
                ->where('program_id', $program->id)
                ->where('status', ProductCommission::STATUS_ACTIVE)
                ->first();

            if ($productCommission) {
                return $productCommission->getEffectiveCommission($conversionValue);
            }
        }

        // Fall back to program's commission structure
        $commissionStructure = $program->commission_structure;

        if (!$commissionStructure) {
            return 0.0;
        }

        // Percentage-based commission
        if (isset($commissionStructure['percentage'])) {
            return $conversionValue * ($commissionStructure['percentage'] / 100);
        }

        // Fixed commission
        if (isset($commissionStructure['fixed'])) {
            return (float) $commissionStructure['fixed'];
        }

        return 0.0;
    }

    /**
     * Split commission between affiliate and sub-affiliate
     *
     * @param Conversion $conversion
     * @return array
     */
    public function splitCommission(Conversion $conversion): array
    {
        $program = $conversion->program;
        $conversionValue = (float) ($conversion->conversion_value ?? $conversion->order_value ?? 0);

        // Calculate total commission
        $totalCommission = $this->calculateCommission(
            $program,
            $conversionValue,
            $conversion->product_id
        );

        // If program doesn't support sub-affiliates, all commission goes to main affiliate
        if (!$program->supports_sub_affiliate || !$conversion->sub_affiliate_id) {
            return [
                'affiliate_commission' => $totalCommission,
                'sub_affiliate_commission' => 0.0,
                'total_commission' => $totalCommission,
            ];
        }

        // Get commission structure for splitting
        $commissionStructure = $program->commission_structure;

        // Default split: 70% affiliate, 30% sub-affiliate
        $affiliatePercentage = $commissionStructure['affiliate_percentage'] ?? 70;
        $subAffiliatePercentage = $commissionStructure['sub_affiliate_percentage'] ?? 30;

        // Ensure percentages add up to 100
        $totalPercentage = $affiliatePercentage + $subAffiliatePercentage;
        if ($totalPercentage !== 100) {
            $affiliatePercentage = ($affiliatePercentage / $totalPercentage) * 100;
            $subAffiliatePercentage = ($subAffiliatePercentage / $totalPercentage) * 100;
        }

        $affiliateCommission = $totalCommission * ($affiliatePercentage / 100);
        $subAffiliateCommission = $totalCommission * ($subAffiliatePercentage / 100);

        return [
            'affiliate_commission' => $affiliateCommission,
            'sub_affiliate_commission' => $subAffiliateCommission,
            'total_commission' => $totalCommission,
        ];
    }

    /**
     * Process commission for a conversion
     *
     * @param Conversion $conversion
     * @return array
     */
    public function processCommission(Conversion $conversion): array
    {
        try {
            return DB::transaction(function () use ($conversion) {
                $split = $this->splitCommission($conversion);

                // Create commission for main affiliate
                $affiliateCommission = Commission::create([
                    'conversion_id' => $conversion->id,
                    'user_id' => $conversion->user_id,
                    'amount' => $split['affiliate_commission'],
                    'status' => Commission::STATUS_PENDING,
                    'commission_type' => Commission::TYPE_AFFILIATE,
                ]);

                $commissions = [$affiliateCommission];

                // Create commission for sub-affiliate if exists
                if ($split['sub_affiliate_commission'] > 0 && $conversion->sub_affiliate_id) {
                    $subAffiliateCommission = Commission::create([
                        'conversion_id' => $conversion->id,
                        'user_id' => $conversion->sub_affiliate_id,
                        'amount' => $split['sub_affiliate_commission'],
                        'status' => Commission::STATUS_PENDING,
                        'commission_type' => Commission::TYPE_SUB_AFFILIATE,
                    ]);

                    $commissions[] = $subAffiliateCommission;
                }

                Log::info('Commission processed', [
                    'conversion_id' => $conversion->id,
                    'affiliate_commission' => $split['affiliate_commission'],
                    'sub_affiliate_commission' => $split['sub_affiliate_commission'],
                ]);

                return [
                    'success' => true,
                    'commissions' => $commissions,
                    'split' => $split,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to process commission', [
                'conversion_id' => $conversion->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if program supports sub-affiliates
     *
     * @param Program $program
     * @return bool
     */
    public function checkSubAffiliateSupport(Program $program): bool
    {
        return $program->supports_sub_affiliate;
    }

    /**
     * Get commission rate for product+program combination
     *
     * @param int $productId
     * @param int $programId
     * @return float
     */
    public function getProductCommissionRate(int $productId, int $programId): float
    {
        $productCommission = ProductCommission::where('product_id', $productId)
            ->where('program_id', $programId)
            ->where('status', ProductCommission::STATUS_ACTIVE)
            ->first();

        return $productCommission ? (float) $productCommission->commission_rate : 0.0;
    }

    /**
     * Get effective commission for a purchase
     *
     * @param int $productId
     * @param int $programId
     * @param float $purchaseValue
     * @return float
     */
    public function getEffectiveCommission(int $productId, int $programId, float $purchaseValue): float
    {
        $productCommission = ProductCommission::where('product_id', $productId)
            ->where('program_id', $programId)
            ->where('status', ProductCommission::STATUS_ACTIVE)
            ->first();

        if ($productCommission) {
            return $productCommission->getEffectiveCommission($purchaseValue);
        }

        return 0.0;
    }
}

