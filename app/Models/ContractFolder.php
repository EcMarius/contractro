<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_id',
        'name',
        'description',
        'color',
        'order',
    ];

    /**
     * Get the user that owns the folder
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent folder
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ContractFolder::class, 'parent_id');
    }

    /**
     * Get child folders
     */
    public function children(): HasMany
    {
        return $this->hasMany(ContractFolder::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all contracts in this folder
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'contract_folder')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    /**
     * Get contracts where this is the primary folder
     */
    public function primaryContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'folder_id');
    }

    /**
     * Get full path of folder (including parents)
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    /**
     * Get total contract count including subfolders
     */
    public function getTotalContractsCountAttribute(): int
    {
        $count = $this->contracts()->count();

        foreach ($this->children as $child) {
            $count += $child->total_contracts_count;
        }

        return $count;
    }

    /**
     * Scope to get root folders (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get folders for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
