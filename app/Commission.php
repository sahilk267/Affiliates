<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversion_id',
        'user_id',
        'amount',
        'status',
        'commission_type',
        'payout_method',
        'payout_details',
        'paid_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payout_details' => 'array',
        'paid_at' => 'datetime',
    ];

    /**
     * Commission statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Commission types
     */
    const TYPE_AFFILIATE = 'affiliate';
    const TYPE_SUB_AFFILIATE = 'sub_affiliate';
    const TYPE_BONUS = 'bonus';
    const TYPE_ADJUSTMENT = 'adjustment';

    /**
     * Payout methods
     */
    const PAYOUT_BANK_TRANSFER = 'bank_transfer';
    const PAYOUT_PAYPAL = 'paypal';
    const PAYOUT_CHECK = 'check';
    const PAYOUT_CRYPTO = 'crypto';

    /**
     * Get the conversion that generated this commission
     */
    public function conversion(): BelongsTo
    {
        return $this->belongsTo(Conversion::class);
    }

    /**
     * Get the user who will receive this commission
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get points transaction related to this commission (if converted to points)
     */
    public function pointsTransaction(): BelongsTo
    {
        return $this->belongsTo(PointsTransaction::class, 'id', 'reference_id')
            ->where('reference_type', PointsTransaction::REF_REFERRAL);
    }

    /**
     * Scope for commissions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for commissions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for commissions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('commission_type', $type);
    }

    /**
     * Scope for pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved commissions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for commissions by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for commissions by amount range
     */
    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('amount', [$minAmount, $maxAmount]);
    }

    /**
     * Check if commission is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if commission is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if commission is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if commission is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Approve commission
     */
    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Cancel commission
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Get commission's age in days
     */
    public function getAgeInDaysAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Get commission's time to payment (in days)
     */
    public function getTimeToPaymentAttribute(): ?int
    {
        if (!$this->paid_at) {
            return null;
        }

        return $this->created_at->diffInDays($this->paid_at);
    }

    /**
     * Check if commission is overdue (pending for more than 30 days)
     */
    public function isOverdue(): bool
    {
        return $this->isPending() && $this->age_in_days > 30;
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    /**
     * Get commission's payout details summary
     */
    public function getPayoutSummaryAttribute(): array
    {
        return [
            'method' => $this->payout_method,
            'details' => $this->payout_details,
            'paid_at' => $this->paid_at,
            'status' => $this->status,
        ];
    }
}
