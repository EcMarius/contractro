<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'contract_type_id',
        'name',
        'content',
        'variables',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    // Render template with variables
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    // Get available variables from content
    public function getAvailableVariables(): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $this->content, $matches);
        return array_unique($matches[1] ?? []);
    }

    // Validate if all required variables are provided
    public function validateVariables(array $data): array
    {
        $required = $this->getAvailableVariables();
        $missing = [];

        foreach ($required as $var) {
            if (!isset($data[$var])) {
                $missing[] = $var;
            }
        }

        return $missing;
    }
}
