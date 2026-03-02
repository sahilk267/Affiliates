<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'parent_id',
        'phone',
        'address',
        'bank_account',
        'ifsc_code',
        'pan_number',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * User roles constants
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_AFFILIATE = 'affiliate';
    const ROLE_SUB_AFFILIATE = 'sub_affiliate';

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is affiliate
     */
    public function isAffiliate(): bool
    {
        return $this->role === self::ROLE_AFFILIATE;
    }

    /**
     * Check if user is sub-affiliate
     */
    public function isSubAffiliate(): bool
    {
        return $this->role === self::ROLE_SUB_AFFILIATE;
    }

    /**
     * Get the parent user (for sub-affiliates)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get sub-affiliates (for affiliates)
     */
    public function subAffiliates(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    /**
     * Get all links created by this user
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Get all clicks for this user's links
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Get all conversions for this user
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    /**
     * Get all commissions for this user
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get user's points balance
     */
    public function points(): HasOne
    {
        return $this->hasOne(UserPoints::class);
    }

    /**
     * Get all points transactions for this user
     */
    public function pointsTransactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class);
    }

    /**
     * Get all referrals created by this user (as referrer)
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get referral where this user was referred (as referred)
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'id', 'referred_id');
    }

    /**
     * Get all redemption requests for this user
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(PointsRedemption::class);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users by role
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Get user's total commission earned
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->commissions()->where('status', 'paid')->sum('amount');
    }

    /**
     * Get user's pending commission
     */
    public function getPendingCommissionAttribute(): float
    {
        return $this->commissions()->where('status', 'pending')->sum('amount');
    }
}
