<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductLink extends Model
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
        'link_id',
        'price',
        'currency',
        'availability',
        'is_best_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_best_price' => 'boolean',
    ];

    /**
     * Get the product this link belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the program (platform) this link belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the tracking link
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Historical source observations for this merchant offer.
     */
    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(ProductPriceSnapshot::class);
    }

    /**
     * Most recent source observation for this merchant offer.
     */
    public function latestPriceSnapshot(): HasOne
    {
        return $this->hasOne(ProductPriceSnapshot::class)->latestOfMany('observed_at');
    }

    /**
     * Get the commission rate for this product+platform combination
     */
    public function getCommissionRateAttribute(): float
    {
        $commission = ProductCommission::where('product_id', $this->product_id)
            ->where('program_id', $this->program_id)
            ->where('status', 'active')
            ->first();
        
        return $commission ? (float) $commission->commission_rate : 0.0;
    }

    /**
     * Check if this is the best price
     */
    public function isBestPrice(): bool
    {
        return $this->is_best_price;
    }

    /**
     * Scope for best price links
     */
    public function scopeBestPrice($query)
    {
        return $query->where('is_best_price', true);
    }

    /**
     * Scope for links by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}

