<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSignature extends Model
{
    protected $fillable = [
        'contract_id',
        'party_id',
        'signature_method',
        'verification_code',
        'verification_phone',
        'code_verified',
        'code_sent_at',
        'signed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'code_verified' => 'boolean',
        'code_sent_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(ContractParty::class, 'party_id');
    }

    // Generate SMS verification code (6 digits)
    public static function generateVerificationCode(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    // Send SMS verification code
    public function sendVerificationCode(string $phone): bool
    {
        $code = self::generateVerificationCode();

        $this->update([
            'verification_code' => $code,
            'verification_phone' => $phone,
            'code_sent_at' => now(),
        ]);

        // TODO: Integrate with SMS provider (Twilio, ClickSend, SMS Link Romania)
        // For now, just return true (will implement in Phase 7)
        // SMS message: "Codul dvs. de verificare pentru semnarea contractului: {$code}"

        return true;
    }

    // Verify SMS code
    public function verifyCode(string $code): bool
    {
        // Check if code matches
        if ($this->verification_code !== $code) {
            return false;
        }

        // Check if code is not expired (15 minutes)
        if ($this->code_sent_at && now()->diffInMinutes($this->code_sent_at) > 15) {
            return false;
        }

        // Mark as verified
        $this->update(['code_verified' => true]);

        return true;
    }

    // Complete signature after verification
    public function completeSigning(string $ipAddress, string $userAgent): bool
    {
        if (!$this->code_verified) {
            return false;
        }

        $this->update([
            'signed_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Mark party as signed
        $this->party->markAsSigned($ipAddress);

        return true;
    }

    // Check if signature is complete
    public function isComplete(): bool
    {
        return !is_null($this->signed_at) && $this->code_verified;
    }

    // EU eIDAS compliance metadata
    public function getEidasMetadata(): array
    {
        return [
            'signature_method' => $this->signature_method,
            'verification_phone' => $this->verification_phone,
            'code_verified' => $this->code_verified,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'party_name' => $this->party->name,
            'party_id_number' => $this->party->id_number,
            'contract_number' => $this->contract->contract_number,
        ];
    }
}
