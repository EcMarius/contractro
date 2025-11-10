<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ContractAnalyticsService
{
    /**
     * Get contract overview statistics
     */
    public function getOverviewStats(array $dateRange = null): array
    {
        $query = Contract::query();

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $totalContracts = $query->count();
        $totalValue = $query->sum('contract_value') ?? 0;
        $averageValue = $query->avg('contract_value') ?? 0;

        // Status breakdown
        $statusBreakdown = Contract::select('status', DB::raw('count(*) as count'))
            ->when($dateRange, fn($q) => $q->whereBetween('created_at', $dateRange))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Signature rate
        $signatureStats = $this->getSignatureRate($dateRange);

        return [
            'total_contracts' => $totalContracts,
            'total_value' => $totalValue,
            'average_value' => $averageValue,
            'status_breakdown' => $statusBreakdown,
            'signature_rate' => $signatureStats['rate'],
            'total_signatures' => $signatureStats['total'],
            'signed_signatures' => $signatureStats['signed'],
        ];
    }

    /**
     * Get signature rate statistics
     */
    public function getSignatureRate(array $dateRange = null): array
    {
        $query = ContractSignature::query();

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $total = $query->count();
        $signed = $query->where('status', 'signed')->count();

        return [
            'total' => $total,
            'signed' => $signed,
            'pending' => $total - $signed,
            'rate' => $total > 0 ? round(($signed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get average time to signature
     */
    public function getAverageTimeToSignature(array $dateRange = null): float
    {
        $query = ContractSignature::where('status', 'signed')
            ->whereNotNull('signed_at');

        if ($dateRange) {
            $query->whereBetween('signed_at', $dateRange);
        }

        $avgHours = $query->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requested_at, signed_at)) as avg_hours')
            ->value('avg_hours');

        return round($avgHours ?? 0, 2);
    }

    /**
     * Get contract creation trend
     */
    public function getCreationTrend(int $days = 30): array
    {
        $data = [];
        $startDate = now()->subDays($days - 1)->startOfDay();

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = Contract::whereDate('created_at', $date)->count();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get contracts by value ranges
     */
    public function getValueDistribution(): array
    {
        $ranges = [
            '0-1000' => [0, 1000],
            '1000-5000' => [1000, 5000],
            '5000-10000' => [5000, 10000],
            '10000-50000' => [10000, 50000],
            '50000+' => [50000, PHP_INT_MAX],
        ];

        $distribution = [];

        foreach ($ranges as $label => $range) {
            $count = Contract::whereBetween('contract_value', $range)->count();
            $distribution[$label] = $count;
        }

        return $distribution;
    }

    /**
     * Get top contracts by value
     */
    public function getTopContractsByValue(int $limit = 10): array
    {
        return Contract::select('id', 'title', 'contract_number', 'contract_value', 'status', 'created_at')
            ->whereNotNull('contract_value')
            ->orderBy('contract_value', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get template usage statistics
     */
    public function getTemplateStats(): array
    {
        $templates = ContractTemplate::withCount('contracts')
            ->orderBy('usage_count', 'desc')
            ->get();

        $totalTemplates = $templates->count();
        $totalUsage = $templates->sum('usage_count');
        $avgUsage = $totalTemplates > 0 ? $totalUsage / $totalTemplates : 0;

        $mostUsed = $templates->take(5)->map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'usage_count' => $template->usage_count,
                'contracts_count' => $template->contracts_count,
            ];
        })->toArray();

        // Calculate conversion rate (contracts created from templates that got signed)
        $conversionRates = [];
        foreach ($templates->take(10) as $template) {
            $totalCreated = $template->contracts()->count();
            $signed = $template->contracts()->whereIn('status', ['signed', 'completed'])->count();
            $rate = $totalCreated > 0 ? round(($signed / $totalCreated) * 100, 2) : 0;

            $conversionRates[] = [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'total_created' => $totalCreated,
                'signed' => $signed,
                'conversion_rate' => $rate,
            ];
        }

        return [
            'total_templates' => $totalTemplates,
            'total_usage' => $totalUsage,
            'average_usage' => round($avgUsage, 2),
            'most_used' => $mostUsed,
            'conversion_rates' => $conversionRates,
        ];
    }

    /**
     * Get user activity statistics
     */
    public function getUserActivityStats(int $userId, array $dateRange = null): array
    {
        $query = Contract::where('user_id', $userId);

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $totalContracts = $query->count();
        $signedContracts = $query->whereIn('status', ['signed', 'completed'])->count();
        $draftContracts = $query->where('status', 'draft')->count();
        $totalValue = $query->sum('contract_value') ?? 0;

        // Get signature performance
        $signatures = ContractSignature::whereHas('contract', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });

        if ($dateRange) {
            $signatures->whereBetween('created_at', $dateRange);
        }

        $totalSignatureRequests = $signatures->count();
        $completedSignatures = $signatures->where('status', 'signed')->count();

        return [
            'total_contracts' => $totalContracts,
            'signed_contracts' => $signedContracts,
            'draft_contracts' => $draftContracts,
            'total_value' => $totalValue,
            'signature_requests' => $totalSignatureRequests,
            'completed_signatures' => $completedSignatures,
            'signature_completion_rate' => $totalSignatureRequests > 0
                ? round(($completedSignatures / $totalSignatureRequests) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get expiring contracts report
     */
    public function getExpiringContracts(int $days = 30): array
    {
        $contracts = Contract::whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->whereIn('status', ['signed', 'completed'])
            ->orderBy('expires_at')
            ->get(['id', 'title', 'contract_number', 'expires_at', 'contract_value', 'user_id'])
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'title' => $contract->title,
                    'contract_number' => $contract->contract_number,
                    'expires_at' => $contract->expires_at->format('Y-m-d'),
                    'days_until_expiration' => $contract->expires_at->diffInDays(now()),
                    'contract_value' => $contract->contract_value,
                ];
            })
            ->toArray();

        return [
            'count' => count($contracts),
            'total_value_at_risk' => array_sum(array_column($contracts, 'contract_value')),
            'contracts' => $contracts,
        ];
    }

    /**
     * Get monthly revenue from contracts
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $data = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $endDate = $date->copy()->endOfMonth();

            $revenue = Contract::whereIn('status', ['signed', 'completed'])
                ->whereBetween('signed_at', [$date, $endDate])
                ->sum('contract_value') ?? 0;

            $data[] = [
                'month' => $date->format('Y-m'),
                'revenue' => $revenue,
            ];
        }

        return $data;
    }

    /**
     * Generate comprehensive analytics report
     */
    public function generateReport(array $options = []): array
    {
        $dateRange = $options['date_range'] ?? null;
        $userId = $options['user_id'] ?? null;

        $report = [
            'generated_at' => now()->toIso8601String(),
            'period' => $dateRange ? [
                'start' => $dateRange[0],
                'end' => $dateRange[1],
            ] : 'All time',
            'overview' => $this->getOverviewStats($dateRange),
            'creation_trend' => $this->getCreationTrend($options['trend_days'] ?? 30),
            'value_distribution' => $this->getValueDistribution(),
            'top_contracts' => $this->getTopContractsByValue($options['top_limit'] ?? 10),
            'template_stats' => $this->getTemplateStats(),
            'expiring_contracts' => $this->getExpiringContracts($options['expiring_days'] ?? 30),
            'monthly_revenue' => $this->getMonthlyRevenue($options['revenue_months'] ?? 12),
        ];

        if ($userId) {
            $report['user_activity'] = $this->getUserActivityStats($userId, $dateRange);
        }

        return $report;
    }
}
