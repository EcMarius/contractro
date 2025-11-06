<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ContractSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'signer_name',
        'signer_email',
        'signer_role',
        'signature_data',
        'signature_type',
        'status',
        'signing_order',
        'requested_at',
        'signed_at',
        'declined_at',
        'decline_reason',
        'ip_address',
        'user_agent',
        'verification_token',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'requested_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($signature) {
            if (empty($signature->verification_token)) {
                $signature->verification_token = Str::random(64);
            }
            if (empty($signature->requested_at)) {
                $signature->requested_at = now();
            }
            if (empty($signature->expires_at)) {
                $signature->expires_at = now()->addDays(14);
            }
        });

        static::updated(function ($signature) {
            if ($signature->isDirty('status')) {
                $signature->contract->checkSignatureStatus();
            }
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function sign(string $signatureData, string $signatureType = 'drawn')
    {
        $this->update([
            'signature_data' => $signatureData,
            'signature_type' => $signatureType,
            'status' => 'signed',
            'signed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function decline(string $reason = null)
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }
}
