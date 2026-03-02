<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
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
        'category',
        'brand',
        'sku',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Product status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Get all product links (platforms where product is available)
     */
    public function productLinks(): HasMany
    {
        return $this->hasMany(ProductLink::class);
    }

    /**
     * Get all active product links
     */
    public function activeProductLinks(): HasMany
    {
        return $this->hasMany(ProductLink::class)->whereHas('link', function($query) {
            $query->where('is_active', true);
        });
    }

    /**
     * Get all commission rates for this product
     */
    public function productCommissions(): HasMany
    {
        return $this->hasMany(ProductCommission::class);
    }

    /**
     * Get active commission rates for this product
     */
    public function activeCommissions(): HasMany
    {
        return $this->hasMany(ProductCommission::class)->where('status', 'active');
    }

    /**
     * Get the highest commission rate for this product
     */
    public function getMaxCommissionRateAttribute(): float
    {
        $maxCommission = $this->activeCommissions()
            ->orderBy('commission_rate', 'desc')
            ->first();
        
        return $maxCommission ? (float) $maxCommission->commission_rate : 0.0;
    }

    /**
     * Get the platform with the highest commission for this product
     */
    public function getBestCommissionPlatformAttribute()
    {
        return $this->activeCommissions()
            ->with('program')
            ->orderBy('commission_rate', 'desc')
            ->first();
    }

    /**
     * Get the minimum price for this product across all platforms
     */
    public function getMinPriceAttribute(): float
    {
        $minPrice = $this->productLinks()
            ->min('price');
        
        return $minPrice ? (float) $minPrice : 0.0;
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for products by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for products by brand
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }
}

