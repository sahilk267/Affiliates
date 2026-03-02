<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'click_id',
        'link_id',
        'user_id',
        'program_id',
        'event_type',
        'event_data',
        'commission_amount',
        'status',
        'conversion_value',
        'currency',
        'order_id',
        'customer_id',
        'product_id',
        'product_name',
        'quantity',
        'sub_affiliate_id',
        'sub_affiliate_commission',
        'processed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_data' => 'array',
        'commission_amount' => 'decimal:2',
        'conversion_value' => 'decimal:2',
        'sub_affiliate_commission' => 'decimal:2',
        'quantity' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * Conversion event types
     */
    const EVENT_PURCHASE = 'purchase';
    const EVENT_SIGNUP = 'signup';
    const EVENT_DOWNLOAD = 'download';
    const EVENT_INSTALL = 'install';
    const EVENT_LEAD = 'lead';
    const EVENT_CLICK = 'click';
    const EVENT_OTHER = 'other';

    /**
     * Conversion statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';

    /**
     * Get the click that led to this conversion
     */
    public function click(): BelongsTo
    {
        return $this->belongsTo(Click::class);
    }

    /**
     * Get the link associated with this conversion
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the user who owns this conversion
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the program associated with this conversion
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the sub-affiliate (if any) for this conversion
     */
    public function subAffiliate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sub_affiliate_id');
    }

    /**
     * Get all commissions for this conversion
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get points transaction related to this conversion (if cashback was credited)
     */
    public function pointsTransaction(): BelongsTo
    {
        return $this->belongsTo(PointsTransaction::class, 'id', 'reference_id')
            ->where('reference_type', PointsTransaction::REF_PURCHASE_CASHBACK);
    }

    /**
     * Scope for conversions by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for conversions by event type
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for conversions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for conversions by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope for conversions by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for approved conversions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending conversions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid conversions
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Check if conversion is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if conversion is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if conversion is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Approve conversion
     */
    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Reject conversion
     */
    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Mark conversion as paid
     */
    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'processed_at' => now(),
        ]);
    }

    /**
     * Get total commission amount (including sub-affiliate)
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->commission_amount + ($this->sub_affiliate_commission ?? 0);
    }

    /**
     * Get conversion's ROI percentage
     */
    public function getRoiAttribute(): float
    {
        if ($this->conversion_value === 0) {
            return 0;
        }
        return ($this->commission_amount / $this->conversion_value) * 100;
    }

    /**
     * Check if conversion has sub-affiliate
     */
    public function hasSubAffiliate(): bool
    {
        return !is_null($this->sub_affiliate_id);
    }

    /**
     * Get conversion's time to process (in hours)
     */
    public function getTimeToProcessAttribute(): ?float
    {
        if (!$this->processed_at) {
            return null;
        }

        return $this->created_at->diffInHours($this->processed_at);
    }
}
