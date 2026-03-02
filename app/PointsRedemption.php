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
    public function approve(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);
    }

    /**
     * Reject redemption
     */
    public function reject(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $notes ?? $this->admin_notes,
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
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

