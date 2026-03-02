<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PointsTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
    ];

    /**
     * Transaction type constants
     */
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    /**
     * Reference type constants
     */
    const REF_PURCHASE_CASHBACK = 'purchase_cashback';
    const REF_REFERRAL = 'referral';
    const REF_REDEMPTION = 'redemption';
    const REF_GIFT = 'gift';
    const REF_BONUS = 'bonus';
    const REF_ADJUSTMENT = 'adjustment';

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Get the user this transaction belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model based on reference type
     */
    public function reference()
    {
        switch ($this->reference_type) {
            case self::REF_PURCHASE_CASHBACK:
                return $this->belongsTo(Conversion::class, 'reference_id');
            case self::REF_REFERRAL:
                return $this->belongsTo(Referral::class, 'reference_id');
            case self::REF_REDEMPTION:
                return $this->belongsTo(PointsRedemption::class, 'reference_id');
            case self::REF_GIFT:
                return $this->belongsTo(Gift::class, 'reference_id');
            default:
                return null;
        }
    }

    /**
     * Check if transaction is credit
     */
    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    /**
     * Check if transaction is debit
     */
    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Scope for credit transactions
     */
    public function scopeCredits($query)
    {
        return $query->where('type', self::TYPE_CREDIT);
    }

    /**
     * Scope for debit transactions
     */
    public function scopeDebits($query)
    {
        return $query->where('type', self::TYPE_DEBIT);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for transactions by reference type
     */
    public function scopeByReferenceType($query, $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }
}

