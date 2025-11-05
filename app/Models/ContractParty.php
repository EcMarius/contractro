<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContractParty extends Model
{
    protected $fillable = [
        'contract_id',
        'party_type',
        'name',
        'email',
        'phone',
        'id_series',
        'id_number',
        'address',
        'company_name',
        'company_cui',
        'is_signed',
        'signed_at',
        'ip_address',
        'signature_data',
    ];

    protected $casts = [
        'signature_data' => 'array',
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class, 'party_id');
    }

    // Generate unique signing link
    public function generateSigningLink(): string
    {
        $token = Str::random(64);

        $this->update([
            'signature_data' => array_merge($this->signature_data ?? [], [
                'signing_token' => $token,
                'token_generated_at' => now()->toDateTimeString(),
            ])
        ]);

        return route('contracts.sign', [
            'contract' => $this->contract_id,
            'party' => $this->id,
            'token' => $token,
        ]);
    }

    // Verify signing token
    public function verifySigningToken(string $token): bool
    {
        $storedToken = $this->signature_data['signing_token'] ?? null;

        if (!$storedToken || $storedToken !== $token) {
            return false;
        }

        // Check if token is expired (24 hours)
        $generatedAt = $this->signature_data['token_generated_at'] ?? null;
        if ($generatedAt && now()->diffInHours($generatedAt) > 24) {
            return false;
        }

        return true;
    }

    // Mark as signed
    public function markAsSigned(string $ipAddress, ?string $signatureImage = null): bool
    {
        return $this->update([
            'is_signed' => true,
            'signed_at' => now(),
            'ip_address' => $ipAddress,
            'signature_data' => array_merge($this->signature_data ?? [], [
                'signature_image' => $signatureImage,
                'signed_at' => now()->toDateTimeString(),
            ])
        ]);
    }

    // Get party type label
    public function getPartyTypeLabelAttribute(): string
    {
        return match($this->party_type) {
            'client' => 'Client',
            'provider' => 'Furnizor',
            'witness' => 'Martor',
            'other' => 'Altul',
            default => 'Necunoscut',
        };
    }

    // Check if party is a company
    public function isCompany(): bool
    {
        return !empty($this->company_name) || !empty($this->company_cui);
    }

    // Get display name (company name or person name)
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: $this->name;
    }
}
