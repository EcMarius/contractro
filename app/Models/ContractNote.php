<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractNote extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'note',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope: Internal notes only
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    // Scope: Public notes (visible to clients)
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }
}
