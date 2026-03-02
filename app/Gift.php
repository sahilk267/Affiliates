<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gift extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'image_url',
        'points_required',
        'stock',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points_required' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_OUT_OF_STOCK = 'out_of_stock';

    /**
     * Get all redemptions for this gift
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(PointsRedemption::class);
    }

    /**
     * Check if gift is available
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->stock > 0;
    }

    /**
     * Check if gift is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0 || $this->status === self::STATUS_OUT_OF_STOCK;
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $quantity = 1): void
    {
        $this->decrement('stock', $quantity);
        
        if ($this->stock <= 0) {
            $this->update(['status' => self::STATUS_OUT_OF_STOCK]);
        }
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $quantity = 1): void
    {
        $this->increment('stock', $quantity);
        
        if ($this->status === self::STATUS_OUT_OF_STOCK && $this->stock > 0) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Scope for active gifts
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for available gifts (active and in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('stock', '>', 0);
    }

    /**
     * Scope for gifts by points range
     */
    public function scopeByPointsRange($query, int $minPoints, ?int $maxPoints = null)
    {
        $query->where('points_required', '>=', $minPoints);
        
        if ($maxPoints !== null) {
            $query->where('points_required', '<=', $maxPoints);
        }
        
        return $query;
    }
}

