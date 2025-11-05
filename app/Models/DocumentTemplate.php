<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'name',
        'content',
        'variables',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Render template with data
    public function render(array $data): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }
        return $content;
    }

    // Get template type label
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'contract' => 'Contract',
            'invoice' => 'Factură',
            'amendment' => 'Act Adițional',
            default => 'Necunoscut',
        };
    }
}
