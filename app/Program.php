<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'merchant_name',
        'merchant_url',
        'logo_url',
        'status',
        'commission_structure',
        'supports_sub_affiliate',
        'api_endpoint',
        'api_credentials',
        'tracking_parameters',
        'cookie_duration',
        'min_payout',
        'payout_frequency',
        'restrictions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_structure' => 'array',
        'supports_sub_affiliate' => 'boolean',
        'api_credentials' => 'array',
        'tracking_parameters' => 'array',
        'restrictions' => 'array',
        'min_payout' => 'decimal:2',
    ];

    /**
     * Program types constants
     */
    const TYPE_ECOMMERCE = 'ecommerce';
    const TYPE_FINANCE = 'finance';
    const TYPE_REFERRAL = 'referral';
    const TYPE_APP_DOWNLOAD = 'app_download';
    const TYPE_OTHER = 'other';

    /**
     * Program status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Payout frequency constants
     */
    const PAYOUT_WEEKLY = 'weekly';
    const PAYOUT_MONTHLY = 'monthly';
    const PAYOUT_QUARTERLY = 'quarterly';

    /**
     * Check if program supports sub-affiliates
     */
    public function supportsSubAffiliates(): bool
    {
        return $this->supports_sub_affiliate;
    }

    /**
     * Check if program is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get all links for this program
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Get all clicks for this program
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Get all conversions for this program
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * Scope for active programs
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for programs by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for programs that support sub-affiliates
     */
    public function scopeSupportsSubAffiliates($query)
    {
        return $query->where('supports_sub_affiliate', true);
    }

    /**
     * Get program's total clicks
     */
    public function getTotalClicksAttribute(): int
    {
        return $this->clicks()->count();
    }

    /**
     * Get program's total conversions
     */
    public function getTotalConversionsAttribute(): int
    {
        return $this->conversions()->count();
    }

    /**
     * Get program's conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        $clicks = $this->total_clicks;
        if ($clicks === 0) {
            return 0;
        }
        return ($this->total_conversions / $clicks) * 100;
    }

    /**
     * Get program's total commission generated
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->conversions()->sum('commission_amount');
    }
}
