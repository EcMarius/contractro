<?php

namespace App\Services;

use App\Models\License;
use App\Models\LicenseCheckLog;
use Illuminate\Support\Facades\Http;

class LicenseService
{
    /**
     * Validate a license key for a specific domain
     */
    public function validateLicense(string $licenseKey, string $domain, string $ipAddress = null, string $checkType = 'api'): array
    {
        $ipAddress = $ipAddress ?? request()->ip();

        // Find the license
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            $this->logInvalidCheck($licenseKey, $domain, $ipAddress, 'License not found', $checkType);

            return [
                'valid' => false,
                'message' => 'Invalid license key',
                'code' => 'LICENSE_NOT_FOUND',
            ];
        }

        // Check if license is active
        if (!$license->isValid()) {
            $license->logCheck($domain, $ipAddress, false, [], $checkType);

            return [
                'valid' => false,
                'message' => 'License is ' . $license->status,
                'code' => 'LICENSE_INVALID_STATUS',
                'status' => $license->status,
                'license' => [
                    'key' => $license->license_key,
                    'domain' => $license->domain,
                    'status' => $license->status,
                    'expires_at' => $license->expires_at?->toDateTimeString(),
                ],
            ];
        }

        // Verify domain matches
        if (!$license->verifyDomain($domain)) {
            $license->logCheck($domain, $ipAddress, false, ['attempted_domain' => $domain], $checkType);

            return [
                'valid' => false,
                'message' => 'License is not valid for this domain',
                'code' => 'DOMAIN_MISMATCH',
                'license_domain' => $license->domain,
                'attempted_domain' => $domain,
            ];
        }

        // All checks passed
        $license->logCheck($domain, $ipAddress, true, [], $checkType);

        // Check if in grace period
        $inGracePeriod = $license->isInGracePeriod();
        $gracePeriodDaysRemaining = $license->getGracePeriodDaysRemaining();

        return [
            'valid' => true,
            'message' => $inGracePeriod
                ? "License is valid (grace period: {$gracePeriodDaysRemaining} days remaining)"
                : 'License is valid',
            'code' => $inGracePeriod ? 'LICENSE_VALID_GRACE_PERIOD' : 'LICENSE_VALID',
            'in_grace_period' => $inGracePeriod,
            'grace_period_days_remaining' => $gracePeriodDaysRemaining,
            'license' => [
                'key' => $license->license_key,
                'domain' => $license->domain,
                'product_name' => $license->product_name,
                'product_version' => $license->product_version,
                'type' => $license->type,
                'status' => $license->status,
                'issued_at' => $license->issued_at->toDateTimeString(),
                'expires_at' => $license->expires_at?->toDateTimeString(),
                'days_until_expiration' => $license->days_until_expiration,
                'is_expiring_soon' => $license->isExpiringSoon(),
            ],
        ];
    }

    /**
     * Create a new license
     */
    public function createLicense(int $userId, string $domain, string $type = 'monthly', array $options = []): License
    {
        return License::create([
            'user_id' => $userId,
            'domain' => $domain,
            'product_name' => $options['product_name'] ?? 'Contract Platform',
            'product_version' => $options['product_version'] ?? '1.0',
            'type' => $type,
            'status' => 'active',
            'metadata' => $options['metadata'] ?? [],
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Check license by domain (without license key - for public checker)
     */
    public function checkDomainLicense(string $domain, string $ipAddress = null): array
    {
        $ipAddress = $ipAddress ?? request()->ip();

        // Normalize domain
        $normalized = (new License)->normalizeDomain($domain);

        // Find active license for this domain
        $license = License::active()
            ->where(function ($query) use ($normalized) {
                $query->where('domain', $normalized)
                      ->orWhere('domain', 'like', "%{$normalized}%");
            })
            ->first();

        if (!$license) {
            $this->logInvalidCheck(null, $domain, $ipAddress, 'No license found for domain', 'domain_check');

            return [
                'has_license' => false,
                'message' => 'No active license found for this domain',
                'domain' => $domain,
            ];
        }

        if (!$license->isValid()) {
            return [
                'has_license' => true,
                'is_valid' => false,
                'message' => 'License found but is ' . $license->status,
                'status' => $license->status,
            ];
        }

        $license->logCheck($domain, $ipAddress, true, [], 'domain_check');

        return [
            'has_license' => true,
            'is_valid' => true,
            'message' => 'Active license found',
            'license_type' => $license->type,
            'expires_at' => $license->expires_at?->toDateTimeString(),
            'days_until_expiration' => $license->days_until_expiration,
            'is_expiring_soon' => $license->isExpiringSoon(),
        ];
    }

    /**
     * Get license statistics
     */
    public function getLicenseStats(int $userId = null): array
    {
        $query = License::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('status', 'active')->count(),
            'expired' => (clone $query)->expired()->count(),
            'expiring_soon' => (clone $query)->expiringSoon()->count(),
            'suspended' => (clone $query)->where('status', 'suspended')->count(),
            'trial' => (clone $query)->where('type', 'trial')->count(),
            'monthly' => (clone $query)->where('type', 'monthly')->count(),
            'yearly' => (clone $query)->where('type', 'yearly')->count(),
            'lifetime' => (clone $query)->where('type', 'lifetime')->count(),
        ];
    }

    /**
     * Get recent check activity
     */
    public function getRecentActivity(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return LicenseCheckLog::with('license')
            ->orderBy('checked_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Expire overdue licenses
     */
    public function expireOverdueLicenses(): int
    {
        $count = License::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        return $count;
    }

    /**
     * Log invalid check attempt
     */
    protected function logInvalidCheck(?string $licenseKey, string $domain, string $ipAddress, string $reason, string $checkType): void
    {
        LicenseCheckLog::create([
            'license_id' => null,
            'license_key' => $licenseKey ?? 'unknown',
            'domain' => $domain,
            'ip_address' => $ipAddress,
            'is_valid' => false,
            'check_type' => $checkType,
            'request_data' => ['reason' => $reason],
            'response_data' => ['valid' => false, 'message' => $reason],
        ]);
    }

    /**
     * Send expiration reminder notifications
     */
    public function sendExpirationReminders(): int
    {
        $licenses = License::expiringSoon(7)->get();
        $count = 0;

        foreach ($licenses as $license) {
            if ($license->user && $license->user->email) {
                // Send notification
                $license->user->notify(new \App\Notifications\LicenseExpiringSoon($license));
                $count++;
            }
        }

        return $count;
    }

    /**
     * Validate remote license (for client-side checking)
     */
    public function validateRemoteLicense(string $licenseKey, string $domain, string $serverUrl): array
    {
        try {
            $response = Http::timeout(10)
                ->post("{$serverUrl}/api/licenses/validate", [
                    'license_key' => $licenseKey,
                    'domain' => $domain,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'valid' => false,
                'message' => 'Failed to validate license',
                'code' => 'VALIDATION_ERROR',
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
                'code' => 'CONNECTION_ERROR',
            ];
        }
    }
}
