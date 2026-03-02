<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'program_id',
        'status',
        'first_conversion_at',
        'total_points_earned',
        'total_conversions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'first_conversion_at' => 'datetime',
        'total_points_earned' => 'integer',
        'total_conversions' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_CONVERTED = 'converted';

    /**
     * Get the user who created this referral (referrer)
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Get the program this referral is for
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Generate a unique referral code
     */
    public static function generateReferralCode(int $userId, ?int $programId = null): string
    {
        $prefix = 'REF' . $userId;
        $suffix = Str::random(6);
        $code = $prefix . '_' . $suffix;
        
        // If program-specific, add program ID
        if ($programId) {
            $code = $prefix . '_P' . $programId . '_' . $suffix;
        }
        
        // Ensure uniqueness
        while (self::where('referral_code', $code)->exists()) {
            $suffix = Str::random(6);
            $code = $prefix . '_' . $suffix;
            if ($programId) {
                $code = $prefix . '_P' . $programId . '_' . $suffix;
            }
        }
        
        return strtoupper($code);
    }

    /**
     * Check if referral is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Check if referral has converted
     */
    public function hasConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Mark referral as converted
     */
    public function markAsConverted(): void
    {
        $this->update([
            'status' => self::STATUS_CONVERTED,
            'first_conversion_at' => $this->first_conversion_at ?? now(),
        ]);
    }

    /**
     * Increment conversion count
     */
    public function incrementConversions(): void
    {
        $this->increment('total_conversions');
        if (!$this->first_conversion_at) {
            $this->update(['first_conversion_at' => now()]);
        }
    }

    /**
     * Add points earned from this referral
     */
    public function addPointsEarned(int $points): void
    {
        $this->increment('total_points_earned', $points);
    }

    /**
     * Scope for active referrals
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_CONVERTED]);
    }

    /**
     * Scope for referrals by referrer
     */
    public function scopeByReferrer($query, $referrerId)
    {
        return $query->where('referrer_id', $referrerId);
    }

    /**
     * Scope for referrals by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}

