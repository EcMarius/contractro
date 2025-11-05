<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAmendment extends Model
{
    protected $fillable = [
        'contract_id',
        'amendment_number',
        'title',
        'content',
        'status',
        'created_by',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get status label
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Ciornă',
            'pending' => 'Așteptare semnătură',
            'signed' => 'Semnat',
            default => 'Necunoscut',
        };
    }

    // Check if amendment is signed
    public function isSigned(): bool
    {
        return $this->status === 'signed' && !is_null($this->signed_at);
    }
}
