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
        'transfer_count',
        'max_transfers',
        'last_transferred_at',
        'notified_30_days_at',
        'notified_7_days_at',
        'notified_1_day_at',
        'notified_expired_at',
        'ip_address',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'last_transferred_at' => 'datetime',
        'notified_30_days_at' => 'datetime',
        'notified_7_days_at' => 'datetime',
        'notified_1_day_at' => 'datetime',
        'notified_expired_at' => 'datetime',
        'metadata' => 'array',
        'check_count' => 'integer',
        'transfer_count' => 'integer',
        'max_transfers' => 'integer',
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

    public function transfers(): HasMany
    {
        return $this->hasMany(LicenseTransfer::class)->orderBy('transferred_at', 'desc');
    }

    /**
     * Grace period in days after expiration
     */
    const GRACE_PERIOD_DAYS = 7;

    /**
     * Check if license is valid (includes grace period)
     */
    public function isValid(bool $includeGracePeriod = true): bool
    {
        // Suspended and cancelled licenses are never valid
        if (in_array($this->status, ['suspended', 'cancelled'])) {
            return false;
        }

        // Active licenses that haven't expired are valid
        if ($this->status === 'active' && (!$this->expires_at || $this->expires_at->isFuture())) {
            return true;
        }

        // Check if expired but within grace period
        if ($includeGracePeriod && $this->isInGracePeriod()) {
            return true;
        }

        // If expired and no grace period, update status
        if ($this->expires_at && $this->expires_at->isPast() && !$this->isInGracePeriod()) {
            if ($this->status === 'active') {
                $this->update(['status' => 'expired']);
            }
            return false;
        }

        return false;
    }

    /**
     * Check if license is strictly valid (no grace period)
     */
    public function isStrictlyValid(): bool
    {
        return $this->isValid(false);
    }

    /**
     * Check if license is expired (excluding grace period)
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast() && !$this->isInGracePeriod();
    }

    /**
     * Check if license is in grace period
     */
    public function isInGracePeriod(): bool
    {
        if (!$this->expires_at || $this->expires_at->isFuture()) {
            return false;
        }

        // Only active licenses can be in grace period
        if ($this->status !== 'active' && $this->status !== 'expired') {
            return false;
        }

        $daysSinceExpiration = $this->expires_at->diffInDays(now());

        return $daysSinceExpiration <= self::GRACE_PERIOD_DAYS;
    }

    /**
     * Get days remaining in grace period (0 if not in grace period)
     */
    public function getGracePeriodDaysRemaining(): int
    {
        if (!$this->isInGracePeriod()) {
            return 0;
        }

        $daysSinceExpiration = $this->expires_at->diffInDays(now());

        return max(0, self::GRACE_PERIOD_DAYS - $daysSinceExpiration);
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
     * Normalize domain for comparison (handles edge cases)
     */
    protected function normalizeDomain(string $domain): string
    {
        // Trim whitespace
        $domain = trim($domain);

        // Convert to lowercase for case-insensitive comparison
        $domain = strtolower($domain);

        // Remove protocol (http://, https://, ftp://, etc.)
        $domain = preg_replace('#^[a-z]+://#i', '', $domain);

        // Remove www prefix (www., www2., www3., etc.)
        $domain = preg_replace('#^www\d*\.#i', '', $domain);

        // Remove trailing slash and path
        $domain = rtrim($domain, '/');

        // Parse URL to extract host (handles complex URLs)
        $parts = parse_url('http://' . $domain);
        $host = $parts['host'] ?? $domain;

        // Remove port number (e.g., example.com:8080 → example.com)
        $host = preg_replace('#:\d+$#', '', $host);

        // Remove trailing dot (valid in DNS but unnecessary)
        $host = rtrim($host, '.');

        // Handle IPv6 addresses (remove brackets)
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = trim($host, '[]');
        }

        // Validate the domain format (basic check)
        // Allow: domain.com, subdomain.domain.com, localhost, IP addresses
        if (empty($host) || strlen($host) > 253) {
            throw new \InvalidArgumentException('Invalid domain format: ' . $domain);
        }

        return $host;
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
     * Renew the license (extends expiration based on type)
     */
    public function renew(): void
    {
        // Use database transaction for atomic operation
        \DB::transaction(function () {
            // Lock the row to prevent concurrent renewals
            $this->lockForUpdate();

            $this->update([
                'status' => 'active',
                'expires_at' => static::calculateExpiration($this->type),
            ]);
        });
    }

    /**
     * Reactivate a cancelled or expired license
     */
    public function reactivate(string $reactivationType = 'full', string $reason = null): array
    {
        // Only cancelled or expired licenses can be reactivated
        if (!in_array($this->status, ['cancelled', 'expired'])) {
            return [
                'success' => false,
                'message' => 'Only cancelled or expired licenses can be reactivated',
                'code' => 'INVALID_STATUS',
                'current_status' => $this->status,
            ];
        }

        try {
            \DB::transaction(function () use ($reactivationType, $reason) {
                // Lock the row
                $this->lockForUpdate();

                $updates = [
                    'status' => 'active',
                ];

                // Determine expiration based on reactivation type
                switch ($reactivationType) {
                    case 'full':
                        // Full reactivation - fresh expiration period
                        $updates['expires_at'] = static::calculateExpiration($this->type);
                        break;

                    case 'extend':
                        // Extend from old expiration (if they had time remaining)
                        if ($this->expires_at && $this->expires_at->isFuture()) {
                            // Still had time, extend from that date
                            $updates['expires_at'] = $this->expires_at->copy()->add(
                                static::calculateExpiration($this->type)->diffAsCarbonInterval(now())
                            );
                        } else {
                            // Already expired, give fresh period
                            $updates['expires_at'] = static::calculateExpiration($this->type);
                        }
                        break;

                    case 'resume':
                        // Resume with remaining time (if any)
                        if ($this->expires_at && $this->expires_at->isFuture()) {
                            // Keep existing expiration
                            // No change to expires_at
                        } else {
                            // Already expired, need fresh period
                            $updates['expires_at'] = static::calculateExpiration($this->type);
                        }
                        break;

                    default:
                        throw new \InvalidArgumentException('Invalid reactivation type: ' . $reactivationType);
                }

                // Add note about reactivation
                if ($reason) {
                    $updates['notes'] = ($this->notes ?? '') . "\n\nReactivated (" . now()->toDateTimeString() . "): {$reason}";
                }

                $this->update($updates);
            });

            return [
                'success' => true,
                'message' => 'License reactivated successfully',
                'code' => 'REACTIVATION_SUCCESS',
                'reactivation_type' => $reactivationType,
                'new_expiration' => $this->fresh()->expires_at?->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            \Log::error('License reactivation failed', [
                'license_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Reactivation failed: ' . $e->getMessage(),
                'code' => 'REACTIVATION_FAILED',
            ];
        }
    }

    /**
     * Check if license can be reactivated
     */
    public function canBeReactivated(): bool
    {
        return in_array($this->status, ['cancelled', 'expired']);
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
     * Transfer license to a new domain
     */
    public function transferToDomain(string $newDomain, int $initiatedByUserId, string $reason = null, string $ipAddress = null, bool $requireAdminApproval = false): array
    {
        // Normalize domains
        $oldDomain = $this->domain;
        $newDomain = $this->normalizeDomain($newDomain);

        // Check if already at max transfers
        if ($this->transfer_count >= $this->max_transfers) {
            return [
                'success' => false,
                'message' => "License has reached maximum transfer limit ({$this->max_transfers})",
                'code' => 'MAX_TRANSFERS_REACHED',
            ];
        }

        // Check if trying to transfer to same domain
        if ($this->normalizeDomain($oldDomain) === $newDomain) {
            return [
                'success' => false,
                'message' => 'New domain is the same as current domain',
                'code' => 'SAME_DOMAIN',
            ];
        }

        // Check if license is active or expired (allow transfers for both)
        if (!in_array($this->status, ['active', 'expired'])) {
            return [
                'success' => false,
                'message' => 'Cannot transfer license with status: ' . $this->status,
                'code' => 'INVALID_STATUS',
            ];
        }

        try {
            \DB::beginTransaction();

            // Create transfer record
            $transfer = LicenseTransfer::create([
                'license_id' => $this->id,
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'initiated_by_user_id' => $initiatedByUserId,
                'reason' => $reason,
                'ip_address' => $ipAddress,
                'admin_approved' => !$requireAdminApproval,
                'transferred_at' => now(),
            ]);

            // Update license domain and transfer count
            $this->update([
                'domain' => $newDomain,
                'transfer_count' => $this->transfer_count + 1,
                'last_transferred_at' => now(),
            ]);

            \DB::commit();

            return [
                'success' => true,
                'message' => 'License transferred successfully',
                'code' => 'TRANSFER_SUCCESS',
                'transfer' => $transfer,
                'transfers_remaining' => $this->max_transfers - $this->transfer_count,
            ];

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('License transfer failed', [
                'license_id' => $this->id,
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage(),
                'code' => 'TRANSFER_FAILED',
            ];
        }
    }

    /**
     * Check if license can be transferred
     */
    public function canBeTransferred(): bool
    {
        return $this->transfer_count < $this->max_transfers &&
               in_array($this->status, ['active', 'expired']);
    }

    /**
     * Get remaining transfers
     */
    public function getRemainingTransfers(): int
    {
        return max(0, $this->max_transfers - $this->transfer_count);
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
