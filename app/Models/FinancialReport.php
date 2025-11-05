<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    protected $fillable = [
        'company_id',
        'report_type',
        'period_start',
        'period_end',
        'data',
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Check if report is stale (older than 24 hours)
    public function isStale(): bool
    {
        return $this->generated_at->diffInHours(now()) > 24;
    }

    // Get report type label
    public function getReportTypeLabelAttribute(): string
    {
        return match($this->report_type) {
            'revenue' => 'Raport Venituri',
            'profitability' => 'Raport Profitabilitate',
            'contract_stats' => 'Statistici Contracte',
            'client_analysis' => 'Analiză Clienți',
            default => 'Raport General',
        };
    }

    // Regenerate report
    public function regenerate(): void
    {
        // This will be implemented in FinancialReportService
        $this->update(['generated_at' => now()]);
    }
}
