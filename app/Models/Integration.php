<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'type',
        'provider',
        'name',
        'description',
        'config',
        'metadata',
        'is_active',
        'is_test_mode',
        'last_sync_at',
        'sync_count',
        'last_error',
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'config', // Don't expose API keys in JSON responses
    ];

    /**
     * Get the company that owns the integration
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get integration logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    /**
     * Common integration types
     */
    const TYPE_ANAF = 'anaf';
    const TYPE_SMS = 'sms';
    const TYPE_EMAIL = 'email';
    const TYPE_STORAGE = 'storage';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ACCOUNTING = 'accounting';
    const TYPE_CRM = 'crm';

    /**
     * Common providers
     */
    const PROVIDER_ANAF_EFACTURA = 'anaf_efactura';
    const PROVIDER_TWILIO = 'twilio';
    const PROVIDER_CLICKSEND = 'clicksend';
    const PROVIDER_SMSLINK = 'smslink';
    const PROVIDER_MAILGUN = 'mailgun';
    const PROVIDER_SENDGRID = 'sendgrid';
    const PROVIDER_AWS_S3 = 'aws_s3';
    const PROVIDER_STRIPE = 'stripe';

    /**
     * Check if integration is properly configured
     */
    public function isConfigured(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $requiredKeys = match($this->provider) {
            self::PROVIDER_ANAF_EFACTURA => ['access_token', 'cui'],
            self::PROVIDER_TWILIO => ['account_sid', 'auth_token', 'from_number'],
            self::PROVIDER_CLICKSEND => ['username', 'api_key'],
            self::PROVIDER_SMSLINK => ['connection_id', 'password'],
            self::PROVIDER_MAILGUN => ['api_key', 'domain'],
            self::PROVIDER_AWS_S3 => ['key', 'secret', 'region', 'bucket'],
            default => [],
        };

        foreach ($requiredKeys as $key) {
            if (empty($this->config[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get safe config (without sensitive data)
     */
    public function getSafeConfig(): array
    {
        $config = $this->config;

        // Mask sensitive fields
        $sensitiveKeys = ['password', 'secret', 'api_key', 'auth_token', 'access_token'];

        foreach ($sensitiveKeys as $key) {
            if (isset($config[$key])) {
                $config[$key] = '****' . substr($config[$key], -4);
            }
        }

        return $config;
    }
}
