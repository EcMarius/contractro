<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\ContractTemplate;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContractAnalyticsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Total contracts
        $totalContracts = Contract::count();
        $newContractsThisMonth = Contract::where('created_at', '>=', $thirtyDaysAgo)->count();

        // Contracts by status
        $draftContracts = Contract::where('status', 'draft')->count();
        $pendingContracts = Contract::where('status', 'pending_signature')->count();
        $signedContracts = Contract::where('status', 'signed')->count();
        $completedContracts = Contract::where('status', 'completed')->count();

        // Contract value
        $totalValue = Contract::whereIn('status', ['signed', 'completed'])
            ->sum('contract_value') ?? 0;
        $averageValue = Contract::whereIn('status', ['signed', 'completed'])
            ->avg('contract_value') ?? 0;

        // Signatures
        $totalSignatures = ContractSignature::count();
        $pendingSignatures = ContractSignature::where('status', 'pending')->count();
        $signedSignatures = ContractSignature::where('status', 'signed')->count();
        $signatureRate = $totalSignatures > 0 ? round(($signedSignatures / $totalSignatures) * 100, 1) : 0;

        // Templates
        $totalTemplates = ContractTemplate::count();
        $publicTemplates = ContractTemplate::where('is_public', true)->count();
        $mostUsedTemplate = ContractTemplate::orderBy('usage_count', 'desc')->first();

        // Expiring contracts
        $expiringIn30Days = Contract::whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$now, $now->copy()->addDays(30)])
            ->whereIn('status', ['signed', 'completed'])
            ->count();

        // Average time to signature (for recently signed contracts)
        $avgTimeToSign = ContractSignature::where('status', 'signed')
            ->where('signed_at', '>=', $thirtyDaysAgo)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requested_at, signed_at)) as avg_hours')
            ->value('avg_hours');

        $stats = [];

        // Row 1: Overview stats
        $stats[] = Stat::make('Total Contracts', number_format($totalContracts))
            ->description("+{$newContractsThisMonth} this month")
            ->icon('heroicon-o-document-text')
            ->color('primary')
            ->chart($this->getContractTrendData());

        $stats[] = Stat::make('Signed Contracts', number_format($signedContracts + $completedContracts))
            ->description($pendingContracts . ' pending signature')
            ->icon('heroicon-o-check-circle')
            ->color('success');

        $stats[] = Stat::make('Contract Value', '$' . number_format($totalValue, 2))
            ->description('Avg: $' . number_format($averageValue, 2))
            ->icon('heroicon-o-currency-dollar')
            ->color('success');

        $stats[] = Stat::make('Signature Rate', $signatureRate . '%')
            ->description("{$signedSignatures} of {$totalSignatures} signatures")
            ->icon('heroicon-o-pencil-square')
            ->color($signatureRate >= 70 ? 'success' : ($signatureRate >= 50 ? 'warning' : 'danger'));

        // Row 2: Actionable stats
        $stats[] = Stat::make('Pending Signatures', number_format($pendingSignatures))
            ->description('Awaiting signer action')
            ->icon('heroicon-o-clock')
            ->color('warning');

        $stats[] = Stat::make('Draft Contracts', number_format($draftContracts))
            ->description('Not yet sent')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray');

        $stats[] = Stat::make('Expiring Soon', number_format($expiringIn30Days))
            ->description('Next 30 days')
            ->icon('heroicon-o-exclamation-triangle')
            ->color($expiringIn30Days > 0 ? 'warning' : 'success');

        if ($avgTimeToSign !== null) {
            $avgDays = round($avgTimeToSign / 24, 1);
            $stats[] = Stat::make('Avg. Time to Sign', $avgDays . ' days')
                ->description('Last 30 days')
                ->icon('heroicon-o-clock')
                ->color('info');
        }

        // Row 3: Template stats
        $stats[] = Stat::make('Templates', number_format($totalTemplates))
            ->description($publicTemplates . ' public')
            ->icon('heroicon-o-document-chart-bar')
            ->color('info');

        if ($mostUsedTemplate) {
            $stats[] = Stat::make('Most Popular Template', $mostUsedTemplate->name)
                ->description('Used ' . $mostUsedTemplate->usage_count . ' times')
                ->icon('heroicon-o-star')
                ->color('warning');
        }

        return $stats;
    }

    /**
     * Get contract trend data for the last 7 days
     */
    protected function getContractTrendData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Contract::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Determine if widget should be displayed
     */
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }
}
