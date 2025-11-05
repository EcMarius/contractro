<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContractStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalContracts = Contract::count();
        $activeContracts = Contract::where('status', 'active')->count();
        $pendingContracts = Contract::where('status', 'pending')->count();
        $expiringContracts = Contract::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->count();

        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();

        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $pendingRevenue = Invoice::whereIn('status', ['issued', 'overdue'])->sum('total_amount');

        $totalCompanies = Company::count();

        return [
            Stat::make('Total Contracts', $totalContracts)
                ->description($activeContracts . ' active, ' . $pendingContracts . ' pending signatures')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary')
                ->chart([7, 12, 15, 18, 22, 25, $totalContracts]),

            Stat::make('Expiring Soon', $expiringContracts)
                ->description('Contracts expiring in 30 days')
                ->descriptionIcon('heroicon-m-clock')
                ->color($expiringContracts > 0 ? 'warning' : 'success'),

            Stat::make('Total Revenue', number_format($totalRevenue, 2) . ' RON')
                ->description(number_format($pendingRevenue, 2) . ' RON pending')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([1000, 2500, 3200, 4100, 5500, 6200, $totalRevenue / 1000]),

            Stat::make('Invoices', $totalInvoices)
                ->description($paidInvoices . ' paid' . ($overdueInvoices > 0 ? ', ' . $overdueInvoices . ' overdue' : ''))
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color($overdueInvoices > 0 ? 'warning' : 'info'),

            Stat::make('Companies', $totalCompanies)
                ->description('Registered companies')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }
}
