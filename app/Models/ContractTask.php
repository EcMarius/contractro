<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTask extends Model
{
    protected $fillable = [
        'contract_id',
        'assigned_to',
        'title',
        'description',
        'due_date',
        'status',
        'priority',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Complete task
    public function complete(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    // Check if overdue
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    // Get status label
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'În așteptare',
            'in_progress' => 'În lucru',
            'completed' => 'Finalizat',
            default => 'Necunoscut',
        };
    }

    // Get priority label
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Scăzută',
            'medium' => 'Medie',
            'high' => 'Ridicată',
            default => 'Medie',
        };
    }
}
