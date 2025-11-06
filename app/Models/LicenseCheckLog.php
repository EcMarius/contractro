<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseCheckLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'license_id',
        'license_key',
        'domain',
        'ip_address',
        'is_valid',
        'check_type',
        'request_data',
        'response_data',
        'checked_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'request_data' => 'array',
        'response_data' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the license this check belongs to
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Scope for valid checks
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope for invalid checks
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * Scope for recent checks
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }
}
