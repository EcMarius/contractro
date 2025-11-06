<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_id',
        'old_domain',
        'new_domain',
        'initiated_by_user_id',
        'reason',
        'ip_address',
        'notes',
        'admin_approved',
        'approved_by_user_id',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'admin_approved' => 'boolean',
    ];

    /**
     * Get the license that was transferred
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Get the user who initiated the transfer
     */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    /**
     * Get the admin who approved the transfer
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
