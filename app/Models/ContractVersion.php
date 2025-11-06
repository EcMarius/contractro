<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'contract_id',
        'version_number',
        'content',
        'variables',
        'changed_by',
        'change_summary',
    ];

    protected $casts = [
        'variables' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Methods
     */
    public function restore()
    {
        $this->contract->update([
            'content' => $this->content,
            'variables' => $this->variables,
        ]);

        // Create a new version entry for the restoration
        $this->contract->createVersion('Restored to version ' . $this->version_number);
    }
}
