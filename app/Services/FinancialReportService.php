<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\FinancialReport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Generate revenue report
     */
    public function generateRevenueReport(int $companyId, Carbon $startDate, Carbon $endDate): FinancialReport
    {
        $company = Company::findOrFail($companyId);

        $data = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'invoices' => [
                'total' => $company->invoices()
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->count(),
                'paid' => $company->invoices()
                    ->where('status', 'paid')
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->count(),
                'total_revenue' => $company->invoices()
                    ->where('status', 'paid')
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->sum('total_amount'),
                'pending_revenue' => $company->invoices()
                    ->whereIn('status', ['issued', 'overdue'])
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->sum('total_amount'),
            ],
            'by_month' => $this->getMonthlyRevenue($companyId, $startDate, $endDate),
        ];

        return FinancialReport::create([
            'company_id' => $companyId,
            'report_type' => 'revenue',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate profitability report
     */
    public function generateProfitabilityReport(int $companyId, Carbon $startDate, Carbon $endDate): FinancialReport
    {
        $company = Company::findOrFail($companyId);

        $revenue = $company->invoices()
            ->where('status', 'paid')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('total_amount');

        $contracts = $company->contracts()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $data = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'revenue' => $revenue,
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'average_contract_value' => $contracts->avg('value'),
            'total_contract_value' => $contracts->sum('value'),
            'by_contract_type' => $this->getRevenueByContractType($companyId, $startDate, $endDate),
        ];

        return FinancialReport::create([
            'company_id' => $companyId,
            'report_type' => 'profitability',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate contract statistics report
     */
    public function generateContractStatsReport(int $companyId, Carbon $startDate, Carbon $endDate): FinancialReport
    {
        $company = Company::findOrFail($companyId);

        $contracts = $company->contracts()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $data = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_contracts' => $contracts->count(),
            'by_status' => [
                'draft' => $contracts->where('status', 'draft')->count(),
                'pending' => $contracts->where('status', 'pending')->count(),
                'signed' => $contracts->where('status', 'signed')->count(),
                'active' => $contracts->where('status', 'active')->count(),
                'expired' => $contracts->where('status', 'expired')->count(),
                'terminated' => $contracts->where('status', 'terminated')->count(),
            ],
            'by_type' => $this->getContractsByType($companyId, $startDate, $endDate),
            'signing_stats' => [
                'average_signing_time' => $this->getAverageSigningTime($contracts),
                'fully_signed' => $contracts->filter(fn($c) => $c->isFullySigned())->count(),
            ],
            'expiring_soon' => $company->contracts()
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now(), now()->addDays(30)])
                ->count(),
        ];

        return FinancialReport::create([
            'company_id' => $companyId,
            'report_type' => 'contract_stats',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Generate client analysis report
     */
    public function generateClientAnalysisReport(int $companyId, Carbon $startDate, Carbon $endDate): FinancialReport
    {
        $company = Company::findOrFail($companyId);

        // Get all clients from invoices
        $clients = $company->invoices()
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->select('client_name', 'client_cui')
            ->groupBy('client_name', 'client_cui')
            ->get();

        $clientStats = [];
        foreach ($clients as $client) {
            $clientInvoices = $company->invoices()
                ->where('client_name', $client->client_name)
                ->whereBetween('issue_date', [$startDate, $endDate]);

            $clientStats[] = [
                'name' => $client->client_name,
                'cui' => $client->client_cui,
                'total_invoices' => $clientInvoices->count(),
                'total_revenue' => $clientInvoices->where('status', 'paid')->sum('total_amount'),
                'pending_amount' => $clientInvoices->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
                'contracts_count' => $company->contracts()
                    ->whereHas('contractParties', function($q) use ($client) {
                        $q->where('name', $client->client_name);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
            ];
        }

        // Sort by revenue
        usort($clientStats, fn($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

        $data = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'total_clients' => count($clientStats),
            'top_clients' => array_slice($clientStats, 0, 10),
            'all_clients' => $clientStats,
        ];

        return FinancialReport::create([
            'company_id' => $companyId,
            'report_type' => 'client_analysis',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Get or generate report (with caching)
     */
    public function getReport(int $companyId, string $reportType, Carbon $startDate, Carbon $endDate, bool $forceRefresh = false): FinancialReport
    {
        if (!$forceRefresh) {
            // Check for existing report
            $existing = FinancialReport::where('company_id', $companyId)
                ->where('report_type', $reportType)
                ->where('period_start', $startDate)
                ->where('period_end', $endDate)
                ->first();

            if ($existing && !$existing->isStale()) {
                return $existing;
            }
        }

        // Generate new report based on type
        return match($reportType) {
            'revenue' => $this->generateRevenueReport($companyId, $startDate, $endDate),
            'profitability' => $this->generateProfitabilityReport($companyId, $startDate, $endDate),
            'contract_stats' => $this->generateContractStatsReport($companyId, $startDate, $endDate),
            'client_analysis' => $this->generateClientAnalysisReport($companyId, $startDate, $endDate),
            default => throw new \Exception('Invalid report type'),
        };
    }

    /**
     * Get monthly revenue breakdown
     */
    protected function getMonthlyRevenue(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->selectRaw('YEAR(payment_date) as year, MONTH(payment_date) as month, SUM(total_amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $result = [];
        foreach ($invoices as $invoice) {
            $result[] = [
                'year' => $invoice->year,
                'month' => $invoice->month,
                'month_name' => Carbon::create($invoice->year, $invoice->month, 1)->format('F Y'),
                'total' => $invoice->total,
            ];
        }

        return $result;
    }

    /**
     * Get revenue by contract type
     */
    protected function getRevenueByContractType(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $contracts = Contract::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('contractType')
            ->get()
            ->groupBy('contract_type_id');

        $result = [];
        foreach ($contracts as $typeId => $typeContracts) {
            $type = $typeContracts->first()->contractType;
            $result[] = [
                'type_id' => $typeId,
                'type_name' => $type->name,
                'count' => $typeContracts->count(),
                'total_value' => $typeContracts->sum('value'),
                'average_value' => $typeContracts->avg('value'),
            ];
        }

        return $result;
    }

    /**
     * Get contracts grouped by type
     */
    protected function getContractsByType(int $companyId, Carbon $startDate, Carbon $endDate): array
    {
        $contracts = Contract::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('contractType')
            ->get()
            ->groupBy('contract_type_id');

        $result = [];
        foreach ($contracts as $typeId => $typeContracts) {
            $type = $typeContracts->first()->contractType;
            $result[] = [
                'type_name' => $type->name,
                'count' => $typeContracts->count(),
                'percentage' => 0, // Will calculate after
            ];
        }

        // Calculate percentages
        $total = array_sum(array_column($result, 'count'));
        foreach ($result as &$item) {
            $item['percentage'] = $total > 0 ? round(($item['count'] / $total) * 100, 2) : 0;
        }

        return $result;
    }

    /**
     * Calculate average signing time
     */
    protected function getAverageSigningTime($contracts): float
    {
        $signed = $contracts->where('status', 'signed');

        if ($signed->count() === 0) {
            return 0;
        }

        $totalHours = 0;
        foreach ($signed as $contract) {
            if ($contract->created_at && $contract->signed_at) {
                $totalHours += $contract->created_at->diffInHours($contract->signed_at);
            }
        }

        return round($totalHours / $signed->count(), 2);
    }

    /**
     * Delete old reports (cleanup)
     */
    public function cleanupOldReports(int $daysOld = 30): int
    {
        return FinancialReport::where('generated_at', '<', now()->subDays($daysOld))->delete();
    }

    /**
     * Get dashboard summary for a company
     */
    public function getDashboardSummary(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        return [
            'contracts' => [
                'total' => $company->contracts()->count(),
                'active' => $company->contracts()->where('status', 'active')->count(),
                'pending' => $company->contracts()->where('status', 'pending')->count(),
                'expiring_30_days' => $company->contracts()
                    ->where('status', 'active')
                    ->whereNotNull('end_date')
                    ->whereBetween('end_date', [now(), now()->addDays(30)])
                    ->count(),
            ],
            'invoices' => [
                'total' => $company->invoices()->count(),
                'paid' => $company->invoices()->where('status', 'paid')->count(),
                'overdue' => $company->invoices()->where('status', 'overdue')->count(),
                'total_revenue' => $company->invoices()->where('status', 'paid')->sum('total_amount'),
                'pending_revenue' => $company->invoices()->whereIn('status', ['issued', 'overdue'])->sum('total_amount'),
            ],
            'this_month' => [
                'contracts_created' => $company->contracts()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'contracts_signed' => $company->contracts()
                    ->where('status', 'signed')
                    ->whereMonth('signed_at', now()->month)
                    ->whereYear('signed_at', now()->year)
                    ->count(),
                'revenue' => $company->invoices()
                    ->where('status', 'paid')
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('total_amount'),
            ],
        ];
    }
}
