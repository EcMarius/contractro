<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractType extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'category',
        'template',
        'fields_schema',
        'numbering_format',
        'is_system_default',
        'is_active',
    ];

    protected $casts = [
        'fields_schema' => 'array',
        'is_system_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function numbering(): HasMany
    {
        return $this->hasMany(ContractNumbering::class);
    }

    // System default contract types (Romanian)
    public static function getSystemTypes(): array
    {
        return [
            'service_contract' => 'Contract de Prestări Servicii',
            'rental_contract' => 'Contract de Închiriere',
            'collaboration_contract' => 'Contract de Colaborare',
            'employment_contract' => 'Contract Individual de Muncă',
            'loan_contract' => 'Contract de Împrumut',
            'purchase_sale_contract' => 'Contract de Vânzare-Cumpărare',
            'mandate_contract' => 'Contract de Mandat',
            'donation_contract' => 'Contract de Donație',
        ];
    }

    // Get human-readable category name
    public function getCategoryNameAttribute(): string
    {
        $types = self::getSystemTypes();
        return $types[$this->category] ?? $this->name;
    }
}
