<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'category',
        'content',
        'variables',
        'metadata',
        'is_public',
        'is_system',
        'user_id',
        'organization_id',
        'usage_count',
        'price',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_system' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /**
     * Scopes
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }

    /**
     * Methods
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function extractVariables(): array
    {
        preg_match_all('/\{\{(\w+)\}\}/', $this->content, $matches);
        return array_unique($matches[1]);
    }

    public function createContract(array $data): Contract
    {
        $this->incrementUsage();

        return Contract::create([
            'template_id' => $this->id,
            'user_id' => auth()->id(),
            'title' => $data['title'] ?? $this->name,
            'content' => $this->content,
            'variables' => $data['variables'] ?? [],
            'status' => 'draft',
        ]);
    }
}
