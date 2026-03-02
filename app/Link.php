<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Link extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'program_id',
        'user_id',
        'original_url',
        'affiliate_url',
        'short_code',
        'sub_id',
        'campaign_name',
        'description',
        'tracking_parameters',
        'is_active',
        'click_count',
        'conversion_count',
        'total_commission',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tracking_parameters' => 'array',
        'is_active' => 'boolean',
        'click_count' => 'integer',
        'conversion_count' => 'integer',
        'total_commission' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the program that owns this link
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the user that owns this link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all clicks for this link
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Get all conversions for this link
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * Get product link (if this link is associated with a product)
     */
    public function productLink(): BelongsTo
    {
        return $this->belongsTo(ProductLink::class, 'id', 'link_id');
    }

    /**
     * Scope for active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for links by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for links by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope for non-expired links
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if link is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if link is valid (active and not expired)
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get link's conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->click_count === 0) {
            return 0;
        }
        return ($this->conversion_count / $this->click_count) * 100;
    }

    /**
     * Get link's average commission per conversion
     */
    public function getAverageCommissionAttribute(): float
    {
        if ($this->conversion_count === 0) {
            return 0;
        }
        return $this->total_commission / $this->conversion_count;
    }

    /**
     * Generate unique short code
     */
    public static function generateShortCode(): string
    {
        do {
            $code = Str::random(8);
        } while (self::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Generate affiliate URL with tracking parameters
     */
    public function generateAffiliateUrl(): string
    {
        $url = $this->original_url;
        
        // Add tracking parameters if they exist
        if ($this->tracking_parameters) {
            $params = http_build_query($this->tracking_parameters);
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . $params;
        }

        // Add sub_id if exists
        if ($this->sub_id) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'sub_id=' . $this->sub_id;
        }

        return $url;
    }
}
