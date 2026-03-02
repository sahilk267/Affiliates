<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserPoints extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
        'total_earned',
        'total_redeemed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'integer',
        'pending_balance' => 'integer',
        'total_earned' => 'integer',
        'total_redeemed' => 'integer',
    ];

    /**
     * Get the user this points balance belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all points transactions for this user
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PointsTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Get available balance (current balance)
     */
    public function getAvailableBalance(): int
    {
        return $this->balance;
    }

    /**
     * Get total balance (available + pending)
     */
    public function getTotalBalance(): int
    {
        return $this->balance + $this->pending_balance;
    }

    /**
     * Check if user has enough points
     */
    public function hasEnoughPoints(int $points): bool
    {
        return $this->balance >= $points;
    }
}

