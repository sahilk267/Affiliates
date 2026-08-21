<?php

namespace App\Services;

use App\Commission;
use App\PointsRedemption;
use App\PointsTransaction;
use App\User;
use App\UserPoints;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PayoutService
{
    public function __construct(private PointsService $pointsService)
    {
    }

    public function createWithdrawal(User $user, int $points, ?string $idempotencyKey = null): PointsRedemption
    {
        $idempotencyKey = $idempotencyKey ?: 'withdrawal-' . Str::uuid();
        $existing = PointsRedemption::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($user, $points, $idempotencyKey) {
            $wallet = UserPoints::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$wallet || $wallet->balance < $points) {
                throw new \DomainException('Insufficient points balance');
            }

            if (!$user->bank_account || !$user->ifsc_code) {
                throw new \DomainException('Bank account details are required before withdrawing');
            }

            $transaction = $this->pointsService->debitPoints(
                $user->id,
                $points,
                'Withdrawal request',
                PointsTransaction::REF_REDEMPTION,
                null,
                'withdrawal-debit-' . $idempotencyKey
            );
            if (!$transaction) {
                throw new \RuntimeException('Withdrawal debit could not be recorded');
            }

            return PointsRedemption::create([
                'user_id' => $user->id,
                'redemption_type' => PointsRedemption::TYPE_CASH,
                'points_used' => $points,
                'cash_amount' => $points,
                'status' => PointsRedemption::STATUS_PENDING,
                'payout_method' => PointsRedemption::TYPE_CASH,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
    }

    public function approveCommission(Commission $commission, ?int $actorId, ?string $notes = null): Commission
    {
        return DB::transaction(function () use ($commission, $actorId, $notes) {
            $commission = Commission::lockForUpdate()->findOrFail($commission->id);
            $commission->approve($actorId, $notes);
            return $commission->fresh();
        });
    }

    public function cancelCommission(Commission $commission, ?int $actorId, ?string $notes = null): Commission
    {
        return DB::transaction(function () use ($commission, $actorId, $notes) {
            $commission = Commission::lockForUpdate()->findOrFail($commission->id);
            $commission->cancel($actorId, $notes);
            return $commission->fresh();
        });
    }

    public function payCommission(
        Commission $commission,
        ?int $actorId,
        string $method,
        string $transactionId,
        ?array $details = null
    ): Commission {
        return DB::transaction(function () use ($commission, $actorId, $method, $transactionId, $details) {
            $commission = Commission::lockForUpdate()->findOrFail($commission->id);
            $commission->markAsPaid($actorId, $method, $transactionId, $details);
            return $commission->fresh();
        });
    }

    public function approveRedemption(PointsRedemption $redemption, ?int $actorId, ?string $notes = null): PointsRedemption
    {
        return DB::transaction(function () use ($redemption, $actorId, $notes) {
            $redemption = PointsRedemption::lockForUpdate()->findOrFail($redemption->id);
            $redemption->approve($notes, $actorId);
            return $redemption->fresh();
        });
    }

    public function rejectRedemption(PointsRedemption $redemption, ?int $actorId, ?string $notes = null): PointsRedemption
    {
        return DB::transaction(function () use ($redemption, $actorId, $notes) {
            $redemption = PointsRedemption::lockForUpdate()->findOrFail($redemption->id);
            $redemption->reject($notes, $actorId);

            $refund = $this->pointsService->creditPoints(
                $redemption->user_id,
                $redemption->points_used,
                'Redemption rejected - Refund',
                PointsTransaction::REF_ADJUSTMENT,
                $redemption->id,
                'redemption-refund-' . $redemption->id
            );
            if (!$refund) {
                throw new \RuntimeException('Redemption refund could not be recorded');
            }

            $redemption->update(['refund_transaction_id' => $refund->id]);
            return $redemption->fresh();
        });
    }

    public function completeRedemption(
        PointsRedemption $redemption,
        ?int $actorId,
        string $method,
        string $reference,
        ?array $details = null
    ): PointsRedemption {
        return DB::transaction(function () use ($redemption, $actorId, $method, $reference, $details) {
            $redemption = PointsRedemption::lockForUpdate()->findOrFail($redemption->id);
            $redemption->markAsCompleted($actorId, $reference, $method, $details);
            return $redemption->fresh();
        });
    }
}
