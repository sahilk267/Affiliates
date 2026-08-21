<?php

namespace App\Services;

use App\User;
use App\UserPoints;
use App\PointsTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointsService
{
    /**
     * Credit points to user
     *
     * @param int $userId
     * @param int $points
     * @param string $description
     * @param string $referenceType
     * @param int|null $referenceId
     * @return PointsTransaction|null
     */
    public function creditPoints(
        int $userId,
        int $points,
        string $description,
        string $referenceType = PointsTransaction::REF_BONUS,
        ?int $referenceId = null,
        ?string $idempotencyKey = null
    ): ?PointsTransaction {
        try {
            return DB::transaction(function () use ($userId, $points, $description, $referenceType, $referenceId, $idempotencyKey) {
                if ($idempotencyKey) {
                    $existing = PointsTransaction::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        Log::info('Points credit replayed idempotently', [
                            'transaction_id' => $existing->id,
                            'user_id' => $userId,
                            'idempotency_key' => $idempotencyKey,
                            'reference_type' => $existing->reference_type,
                            'reference_id' => $existing->reference_id,
                        ]);
                        return $existing;
                    }
                }

                // Get or create user points record
                $userPoints = UserPoints::where('user_id', $userId)->lockForUpdate()->first();
                if (!$userPoints) {
                    $userPoints = UserPoints::create([
                        'user_id' => $userId,
                        'balance' => 0,
                        'pending_balance' => 0,
                        'total_earned' => 0,
                        'total_redeemed' => 0,
                    ]);
                }


                // Update balance
                $oldBalance = $userPoints->balance;
                $newBalance = $oldBalance + $points;

                $userPoints->increment('balance', $points);
                $userPoints->increment('total_earned', $points);

                // Create transaction record
                $transaction = PointsTransaction::create([
                    'user_id' => $userId,
                    'type' => PointsTransaction::TYPE_CREDIT,
                    'points' => $points,
                    'balance_after' => $newBalance,
                    'description' => $description,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'status' => PointsTransaction::STATUS_COMPLETED,
                    'idempotency_key' => $idempotencyKey,
                ]);

                Log::info('Points credited', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $userId,
                    'points' => $points,
                    'balance_after' => $newBalance,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $transaction;
            });
        } catch (\Exception $e) {
            Log::error('Failed to credit points', [
                'user_id' => $userId,
                'points' => $points,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Debit points from user
     *
     * @param int $userId
     * @param int $points
     * @param string $description
     * @param string $referenceType
     * @param int|null $referenceId
     * @return PointsTransaction|null
     */
    public function debitPoints(
        int $userId,
        int $points,
        string $description,
        string $referenceType = PointsTransaction::REF_REDEMPTION,
        ?int $referenceId = null,
        ?string $idempotencyKey = null
    ): ?PointsTransaction {
        try {
            return DB::transaction(function () use ($userId, $points, $description, $referenceType, $referenceId, $idempotencyKey) {
                if ($idempotencyKey) {
                    $existing = PointsTransaction::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        Log::info('Points debit replayed idempotently', [
                            'transaction_id' => $existing->id,
                            'user_id' => $userId,
                            'idempotency_key' => $idempotencyKey,
                            'reference_type' => $existing->reference_type,
                            'reference_id' => $existing->reference_id,
                        ]);
                        return $existing;
                    }
                }
                $userPoints = UserPoints::where('user_id', $userId)->lockForUpdate()->first();

                if (!$userPoints) {
                    throw new \Exception('User points record not found');
                }

                // Check if user has enough points
                if ($userPoints->balance < $points) {
                    throw new \Exception('Insufficient points balance');
                }

                // Update balance
                $oldBalance = $userPoints->balance;
                $newBalance = $oldBalance - $points;

                $userPoints->decrement('balance', $points);
                $userPoints->increment('total_redeemed', $points);

                // Create transaction record
                $transaction = PointsTransaction::create([
                    'user_id' => $userId,
                    'type' => PointsTransaction::TYPE_DEBIT,
                    'points' => $points,
                    'balance_after' => $newBalance,
                    'description' => $description,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'status' => PointsTransaction::STATUS_COMPLETED,
                    'idempotency_key' => $idempotencyKey,
                ]);

                Log::info('Points debited', [
                    'transaction_id' => $transaction->id,
                    'user_id' => $userId,
                    'points' => $points,
                    'balance_after' => $newBalance,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $transaction;
            });
        } catch (\Exception $e) {
            Log::error('Failed to debit points', [
                'user_id' => $userId,
                'points' => $points,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get user's points balance
     *
     * @param int $userId
     * @return UserPoints|null
     */
    public function getBalance(int $userId): ?UserPoints
    {
        return UserPoints::where('user_id', $userId)->first();
    }

    /**
     * Get user's available balance
     *
     * @param int $userId
     * @return int
     */
    public function getAvailableBalance(int $userId): int
    {
        $userPoints = $this->getBalance($userId);
        return $userPoints ? $userPoints->balance : 0;
    }

    /**
     * Check if user has enough points
     *
     * @param int $userId
     * @param int $points
     * @return bool
     */
    public function hasEnoughPoints(int $userId, int $points): bool
    {
        $balance = $this->getAvailableBalance($userId);
        return $balance >= $points;
    }

    /**
     * Process withdrawal request
     *
     * @param int $userId
     * @param int $points
     * @param string $description
     * @return PointsTransaction|null
     */
    public function processWithdrawal(int $userId, int $points, string $description = 'Withdrawal request'): ?PointsTransaction
    {
        // Minimum withdrawal check (100 points = ₹100)
        if ($points < 100) {
            throw new \Exception('Minimum withdrawal amount is 100 points (₹100)');
        }

        return $this->debitPoints(
            $userId,
            $points,
            $description,
            PointsTransaction::REF_REDEMPTION
        );
    }

    /**
     * Get transaction history for user
     *
     * @param int $userId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTransactionHistory(int $userId, int $limit = 50)
    {
        return PointsTransaction::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get points statistics for user
     *
     * @param int $userId
     * @return array
     */
    public function getPointsStats(int $userId): array
    {
        $userPoints = $this->getBalance($userId);

        if (!$userPoints) {
            return [
                'balance' => 0,
                'pending_balance' => 0,
                'total_earned' => 0,
                'total_redeemed' => 0,
            ];
        }

        return [
            'balance' => $userPoints->balance,
            'pending_balance' => $userPoints->pending_balance,
            'total_earned' => $userPoints->total_earned,
            'total_redeemed' => $userPoints->total_redeemed,
        ];
    }
}

