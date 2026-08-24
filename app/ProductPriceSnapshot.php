<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_link_id',
        'source',
        'external_offer_id',
        'observed_at',
        'price',
        'currency',
        'availability',
        'rating',
        'rating_count',
        'original_price',
        'discount_percent',
        'metadata',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'rating_count' => 'integer',
        'original_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function productLink(): BelongsTo
    {
        return $this->belongsTo(ProductLink::class);
    }

    public function scopeForSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeObservedBetween($query, $from, $to)
    {
        return $query->whereBetween('observed_at', [$from, $to]);
    }
}
