<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'cui',
        'reg_com',
        'address',
        'phone',
        'email',
        'bank_account',
        'bank_name',
        'logo',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner of the company
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all contracts for this company
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get active contracts
     */
    public function activeContracts(): HasMany
    {
        return $this->contracts()->where('status', 'active');
    }

    /**
     * Get all invoices for this company
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all contract types for this company
     */
    public function contractTypes(): HasMany
    {
        return $this->hasMany(ContractType::class);
    }

    /**
     * Get user permissions for this company
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Get all integrations for this company
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * Get active integrations
     */
    public function activeIntegrations(): HasMany
    {
        return $this->integrations()->where('is_active', true);
    }

    /**
     * Get integration by type
     */
    public function getIntegration(string $type): ?Integration
    {
        return $this->integrations()
            ->where('type', $type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get financial stats for the company
     */
    public function getFinancialStatsAttribute(): array
    {
        return [
            'total_contracts' => $this->contracts()->count(),
            'active_contracts' => $this->activeContracts()->count(),
            'total_invoices' => $this->invoices()->count(),
            'paid_invoices' => $this->invoices()->where('status', 'paid')->count(),
            'total_revenue' => $this->invoices()->where('status', 'paid')->sum('total_amount'),
            'pending_revenue' => $this->invoices()->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
        ];
    }

    /**
     * Get invoice settings for this company
     */
    public function getInvoiceSettings(): array
    {
        return $this->settings['invoice'] ?? [
            'series' => 'FACT',
            'vat_rate' => 19,
            'currency' => 'RON',
            'default_due_days' => 30,
        ];
    }

    /**
     * Validate Romanian CUI (fiscal code)
     */
    public static function validateCUI(string $cui): bool
    {
        // Remove any non-numeric characters except RO prefix
        $cui = strtoupper(trim($cui));
        $cui = str_replace(['RO', ' ', '-'], '', $cui);

        if (!is_numeric($cui) || strlen($cui) < 2 || strlen($cui) > 10) {
            return false;
        }

        // CUI validation algorithm for Romania
        $weights = [7, 5, 3, 2, 1, 7, 5, 3, 2];
        $cui = str_pad($cui, 10, '0', STR_PAD_LEFT);
        $checkDigit = (int)substr($cui, -1);
        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$cui[$i] * $weights[$i];
        }

        $remainder = $sum % 11;
        $expectedCheck = $remainder == 10 ? 0 : $remainder;

        return $checkDigit === $expectedCheck;
    }
}
