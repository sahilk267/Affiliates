<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsRedemption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'redemption_type',
        'points_used',
        'cash_amount',
        'gift_id',
        'status',
        'admin_notes',
        'payout_method',
        'payout_reference',
        'payout_details',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'completed_by',
        'completed_at',
        'refund_transaction_id',
        'idempotency_key',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points_used' => 'integer',
        'cash_amount' => 'decimal:2',
        'payout_details' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Redemption type constants
     */
    const TYPE_CASH = 'cash';
    const TYPE_GIFT = 'gift';
    const TYPE_DISCOUNT = 'discount';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user this redemption belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the gift (if gift redemption)
     */
    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }

    /**
     * Check if redemption is cash withdrawal
     */
    public function isCash(): bool
    {
        return $this->redemption_type === self::TYPE_CASH;
    }

    /**
     * Check if redemption is gift
     */
    public function isGift(): bool
    {
        return $this->redemption_type === self::TYPE_GIFT;
    }

    /**
     * Check if redemption is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if redemption is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if redemption is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Approve redemption
     */
    public function approve(?string $notes = null, ?int $actorId = null): void
    {
        if (!$this->isPending()) {
            throw new \DomainException('Only pending redemptions can be approved');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'approved_by' => $actorId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject redemption
     */
    public function reject(?string $notes = null, ?int $actorId = null): void
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true)) {
            throw new \DomainException('Only pending or approved redemptions can be rejected');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $notes ?? $this->admin_notes,
            'rejected_by' => $actorId,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(?int $actorId = null, ?string $reference = null, ?string $method = null, ?array $details = null): void
    {
        if (!$this->isApproved()) {
            throw new \DomainException('Only approved redemptions can be completed');
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payout_reference' => $reference,
            'payout_method' => $method,
            'payout_details' => $details,
            'completed_by' => $actorId,
            'completed_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope for pending redemptions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for redemptions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('redemption_type', $type);
    }
}

