<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Click extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'link_id',
        'user_id',
        'program_id',
        'ip_address',
        'user_agent',
        'referrer',
        'country',
        'city',
        'device_type',
        'browser',
        'os',
        'tracking_data',
        'is_unique',
        'is_converted',
        'clicked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tracking_data' => 'array',
        'is_unique' => 'boolean',
        'is_converted' => 'boolean',
        'clicked_at' => 'datetime',
    ];

    /**
     * Get the link that was clicked
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }

    /**
     * Get the user who owns the link
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the program associated with this click
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the conversion (if any) for this click
     */
    public function conversion(): HasOne
    {
        return $this->hasOne(Conversion::class);
    }

    /**
     * Scope for converted clicks
     */
    public function scopeConverted($query)
    {
        return $query->where('is_converted', true);
    }

    /**
     * Scope for non-converted clicks
     */
    public function scopeNotConverted($query)
    {
        return $query->where('is_converted', false);
    }

    /**
     * Scope for clicks by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for clicks by program
     */
    public function scopeByProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope for clicks by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for clicks by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope for clicks by device type
     */
    public function scopeByDeviceType($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get click's time to conversion (in hours)
     */
    public function getTimeToConversionAttribute(): ?float
    {
        if (!$this->is_converted || !$this->conversion) {
            return null;
        }

        return $this->clicked_at->diffInHours($this->conversion->created_at);
    }

    /**
     * Check if click has converted
     */
    public function hasConverted(): bool
    {
        return $this->is_converted;
    }

    /**
     * Mark click as converted
     */
    public function markAsConverted(): void
    {
        $this->update(['is_converted' => true]);
    }

    /**
     * Get click's geographic information
     */
    public function getGeographicInfoAttribute(): array
    {
        return [
            'country' => $this->country,
            'city' => $this->city,
        ];
    }

    /**
     * Get click's device information
     */
    public function getDeviceInfoAttribute(): array
    {
        return [
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
        ];
    }
}
