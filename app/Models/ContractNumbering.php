<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractNumbering extends Model
{
    protected $fillable = [
        'company_id',
        'contract_type_id',
        'year',
        'prefix',
        'last_number',
        'format',
        'reserved_numbers',
    ];

    protected $casts = [
        'reserved_numbers' => 'array',
        'year' => 'integer',
        'last_number' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    // Get next available number
    public function getNextNumber(): int
    {
        return $this->last_number + 1;
    }

    // Reserve a number (for skipping)
    public function reserveNumber(int $number): void
    {
        $reserved = $this->reserved_numbers ?? [];
        $reserved[] = $number;
        $this->update(['reserved_numbers' => array_unique($reserved)]);
    }

    // Format number according to format string
    public function formatNumber(int $number): string
    {
        return str_replace(
            ['{prefix}', '{number}', '{year}'],
            [$this->prefix, str_pad($number, 4, '0', STR_PAD_LEFT), $this->year],
            $this->format
        );
    }
}
