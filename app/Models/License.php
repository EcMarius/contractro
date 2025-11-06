<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'license_key',
        'user_id',
        'domain',
        'product_name',
        'product_version',
        'status',
        'type',
        'issued_at',
        'expires_at',
        'last_checked_at',
        'check_count',
        'ip_address',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'metadata' => 'array',
        'check_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($license) {
            if (empty($license->license_key)) {
                $license->license_key = static::generateLicenseKey();
            }
            if (empty($license->issued_at)) {
                $license->issued_at = now();
            }
            // Set expiration based on type
            if (empty($license->expires_at) && $license->type !== 'lifetime') {
                $license->expires_at = static::calculateExpiration($license->type);
            }
        });
    }

    /**
     * Generate a unique license key
     */
    public static function generateLicenseKey(): string
    {
        do {
            // Format: XXXX-XXXX-XXXX-XXXX-XXXX
            $key = strtoupper(Str::random(4) . '-' . Str::random(4) . '-' .
                   Str::random(4) . '-' . Str::random(4) . '-' . Str::random(4));
        } while (static::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Calculate expiration date based on license type
     */
    public static function calculateExpiration(string $type): ?\Carbon\Carbon
    {
        return match ($type) {
            'trial' => now()->addDays(14),
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth(),
        };
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkLogs(): HasMany
    {
        return $this->hasMany(LicenseCheckLog::class)->orderBy('checked_at', 'desc');
    }

    /**
     * Check if license is valid
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->update(['status' => 'expired']);
            return false;
        }

        return true;
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if license is expiring soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isFuture() &&
               $this->expires_at->diffInDays(now()) <= 7;
    }

    /**
     * Verify license against domain
     */
    public function verifyDomain(string $domain): bool
    {
        // Normalize domains (remove www, protocol, etc.)
        $licenseDomain = $this->normalizeDomain($this->domain);
        $checkDomain = $this->normalizeDomain($domain);

        return $licenseDomain === $checkDomain;
    }

    /**
     * Normalize domain for comparison
     */
    protected function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);

        // Remove www
        $domain = preg_replace('#^www\.#', '', $domain);

        // Remove trailing slash
        $domain = rtrim($domain, '/');

        // Get just the domain part (remove path)
        $parts = parse_url('http://' . $domain);

        return $parts['host'] ?? $domain;
    }

    /**
     * Log a check attempt
     */
    public function logCheck(string $checkDomain, string $ipAddress, bool $isValid, array $requestData = [], string $checkType = 'api'): void
    {
        $this->increment('check_count');
        $this->update(['last_checked_at' => now()]);

        LicenseCheckLog::create([
            'license_id' => $this->id,
            'license_key' => $this->license_key,
            'domain' => $checkDomain,
            'ip_address' => $ipAddress,
            'is_valid' => $isValid,
            'check_type' => $checkType,
            'request_data' => $requestData,
            'response_data' => [
                'valid' => $isValid,
                'status' => $this->status,
                'expires_at' => $this->expires_at?->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Renew the license
     */
    public function renew(): void
    {
        $this->update([
            'status' => 'active',
            'expires_at' => static::calculateExpiration($this->type),
        ]);
    }

    /**
     * Suspend the license
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason ? "Suspended: {$reason}" : $this->notes,
        ]);
    }

    /**
     * Activate the license
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Cancel the license
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->orWhere('status', 'expired');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                    ->whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeForDomain($query, string $domain)
    {
        $normalized = (new static)->normalizeDomain($domain);
        return $query->where('domain', 'like', "%{$normalized}%");
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Accessors
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return now()->diffInDays($this->expires_at, false);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }
}
