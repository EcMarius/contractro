<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'approver_id',
        'step_number',
        'step_name',
        'status',
        'comments',
        'requested_at',
        'responded_at',
        'due_at',
        'is_required',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'due_at' => 'datetime',
        'is_required' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($approval) {
            if ($approval->isDirty('status')) {
                $approval->contract->checkApprovalStatus();

                // Send notification if approved or rejected
                if ($approval->status === 'approved') {
                    $approval->contract->user->notify(
                        new \App\Notifications\ContractApproved($approval->contract, $approval)
                    );
                } elseif ($approval->status === 'rejected') {
                    $approval->contract->user->notify(
                        new \App\Notifications\ContractRejected($approval->contract, $approval)
                    );
                }
            }
        });
    }

    /**
     * Get the contract
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get the approver
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Approve the contract at this step
     */
    public function approve(?string $comments = null): void
    {
        $this->update([
            'status' => 'approved',
            'comments' => $comments,
            'responded_at' => now(),
        ]);
    }

    /**
     * Reject the contract at this step
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'comments' => $reason,
            'responded_at' => now(),
        ]);
    }

    /**
     * Skip this approval step
     */
    public function skip(): void
    {
        if (!$this->is_required) {
            $this->update([
                'status' => 'skipped',
                'responded_at' => now(),
            ]);
        }
    }

    /**
     * Check if approval is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->status === 'pending';
    }

    /**
     * Scope to get pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get overdue approvals
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    /**
     * Scope to get approvals for a specific user
     */
    public function scopeForApprover($query, $approverId)
    {
        return $query->where('approver_id', $approverId);
    }
}
