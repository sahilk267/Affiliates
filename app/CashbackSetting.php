<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'cashback_rate',
        'referral_rate',
        'min_purchase_amount',
        'max_cashback_amount',
        'points_per_rupee',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cashback_rate' => 'decimal:2',
        'referral_rate' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_cashback_amount' => 'decimal:2',
        'points_per_rupee' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get the program this setting belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Calculate cashback amount for a purchase value
     */
    public function calculateCashback(float $purchaseValue): float
    {
        // Check minimum purchase requirement
        if ($this->min_purchase_amount > 0 && $purchaseValue < $this->min_purchase_amount) {
            return 0.0;
        }

        // Calculate cashback
        $cashback = $purchaseValue * ((float) $this->cashback_rate / 100);

        // Apply maximum cashback limit if set
        if ($this->max_cashback_amount && $cashback > $this->max_cashback_amount) {
            return (float) $this->max_cashback_amount;
        }

        return $cashback;
    }

    /**
     * Calculate points for a cashback amount
     */
    public function calculatePoints(float $cashbackAmount): int
    {
        return (int) ($cashbackAmount * $this->points_per_rupee);
    }

    /**
     * Calculate referral commission for a purchase value
     */
    public function calculateReferralCommission(float $purchaseValue): float
    {
        if ($this->referral_rate <= 0) {
            return 0.0;
        }

        return $purchaseValue * ((float) $this->referral_rate / 100);
    }

    /**
     * Check if setting is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope for active settings
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}

