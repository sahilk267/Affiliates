<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCommission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'program_id',
        'commission_rate',
        'commission_type',
        'fixed_amount',
        'category',
        'min_purchase',
        'max_commission',
        'status',
        'source',
        'last_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Commission type constants
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Source constants
     */
    const SOURCE_MANUAL = 'manual';
    const SOURCE_API = 'api';
    const SOURCE_IMPORT = 'import';

    /**
     * Get the product this commission belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the program (platform) this commission belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get effective commission for a purchase value
     */
    public function getEffectiveCommission(float $purchaseValue): float
    {
        // Check minimum purchase requirement
        if ($this->min_purchase > 0 && $purchaseValue < $this->min_purchase) {
            return 0.0;
        }

        // Calculate commission based on type
        if ($this->commission_type === self::TYPE_FIXED) {
            $commission = (float) $this->fixed_amount;
        } else {
            $commission = $purchaseValue * ((float) $this->commission_rate / 100);
        }

        // Apply maximum commission limit if set
        if ($this->max_commission && $commission > $this->max_commission) {
            return (float) $this->max_commission;
        }

        return $commission;
    }

    /**
     * Check if commission is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope for active commissions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for commissions by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for commissions by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}

