<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Invoice;
use Carbon\Carbon;

class UserStatsOverviewWidget extends BaseWidget
{
    public ?\Illuminate\Database\Eloquent\Model $record = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $subscription = $this->record->subscriptions()->with('plan')->first();

        // Get user's companies
        $companies = Company::where('user_id', $this->record->id);
        $companyIds = $companies->pluck('id');

        // Get contracts for user's companies
        $contracts = Contract::whereIn('company_id', $companyIds);

        // Get invoices for user's companies
        $invoices = Invoice::whereIn('company_id', $companyIds);

        $stats = [];

        // Subscription Information
        if ($subscription) {
            $planName = $subscription->plan->name ?? 'Unknown Plan';

            // Get price based on billing cycle
            $cycle = $subscription->cycle ?? 'month';
            if ($cycle == 'month' || $cycle == 'monthly') {
                $price = $subscription->plan->monthly_price ?? 0;
                $cycleText = 'month';
            } else {
                $price = $subscription->plan->yearly_price ?? 0;
                $cycleText = 'year';
            }

            $planPrice = $price > 0 ? number_format($price, 0) : 'N/A';

            $stats[] = Stat::make('Current Plan', $planName)
                ->description($planPrice . ' / ' . $cycleText)
                ->icon('heroicon-o-currency-dollar')
                ->color('success');

            // Trial Status - Always show
            if ($subscription->trial_ends_at) {
                $trialEnds = Carbon::parse($subscription->trial_ends_at);
                $isOnTrial = $trialEnds->isFuture();

                $stats[] = Stat::make('Trial Status', $isOnTrial ? 'Active Trial' : 'Trial Ended')
                    ->description(
                        ($isOnTrial ? 'Ends: ' : 'Ended: ') .
                        $trialEnds->format('M d, Y') . ' (' . $trialEnds->diffForHumans() . ')'
                    )
                    ->icon('heroicon-o-clock')
                    ->color($isOnTrial ? 'warning' : 'gray');
            } else {
                // Check if user has trial_ends_at field directly (from old system)
                if ($this->record->trial_ends_at) {
                    $trialEnds = Carbon::parse($this->record->trial_ends_at);
                    $isOnTrial = $trialEnds->isFuture();

                    $stats[] = Stat::make('Trial Status', $isOnTrial ? 'Active Trial' : 'Trial Ended')
                        ->description(
                            ($isOnTrial ? 'Ends: ' : 'Ended: ') .
                            $trialEnds->format('M d, Y') . ' (' . $trialEnds->diffForHumans() . ')'
                        )
                        ->icon('heroicon-o-clock')
                        ->color($isOnTrial ? 'warning' : 'gray');
                } else {
                    $stats[] = Stat::make('Trial Status', 'No Trial')
                        ->description('User has not used trial')
                        ->icon('heroicon-o-clock')
                        ->color('gray');
                }
            }

            // Next Billing
            if ($subscription->next_payment_at) {
                $nextBilling = Carbon::parse($subscription->next_payment_at);
                $stats[] = Stat::make('Next Billing', $nextBilling->format('M d, Y'))
                    ->description($nextBilling->diffForHumans())
                    ->icon('heroicon-o-calendar')
                    ->color('info');
            }

            // Subscription Status
            $statusColors = [
                'active' => 'success',
                'trialing' => 'warning',
                'cancelled' => 'danger',
                'past_due' => 'danger',
                'unpaid' => 'danger',
            ];

            $stats[] = Stat::make('Subscription Status', ucfirst($subscription->status))
                ->description($subscription->last_payment_at ? 'Last payment: ' . Carbon::parse($subscription->last_payment_at)->format('M d, Y') : 'No payments yet')
                ->icon('heroicon-o-check-circle')
                ->color($statusColors[$subscription->status] ?? 'gray');
        } else {
            $stats[] = Stat::make('Subscription', 'No Active Subscription')
                ->description('User has not subscribed yet')
                ->icon('heroicon-o-x-circle')
                ->color('danger');
        }

        // Account Information
        $accountAge = floor($this->record->created_at->diffInDays(now()));
        if ($accountAge == 0) {
            $accountAgeText = 'Today';
        } elseif ($accountAge == 1) {
            $accountAgeText = '1 day';
        } else {
            $accountAgeText = $accountAge . ' days';
        }

        $stats[] = Stat::make('Account Age', $accountAgeText)
            ->description('Created ' . $this->record->created_at->format('M d, Y'))
            ->icon('heroicon-o-user')
            ->color('gray');

        $stats[] = Stat::make('Email Status', $this->record->verified ? 'Verified' : 'Not Verified')
            ->description($this->record->email)
            ->icon('heroicon-o-envelope')
            ->color($this->record->verified ? 'success' : 'warning');

        // User Role
        $role = $this->record->roles()->first();
        $stats[] = Stat::make('User Role', $role ? ucfirst($role->name) : 'No Role')
            ->icon('heroicon-o-shield-check')
            ->color('info');

        // ContractRO Usage
        $totalCompanies = $companies->count();

        $stats[] = Stat::make('Companies', $totalCompanies)
            ->description($totalCompanies == 1 ? 'company' : 'companies')
            ->icon('heroicon-o-building-office')
            ->color('primary');

        $totalContracts = $contracts->count();
        $activeContracts = $contracts->where('status', 'active')->count();
        $pendingContracts = $contracts->where('status', 'pending')->count();

        $stats[] = Stat::make('Total Contracts', $totalContracts)
            ->description($activeContracts . ' active, ' . $pendingContracts . ' pending')
            ->icon('heroicon-o-document-text')
            ->color('success');

        $totalInvoices = $invoices->count();
        $paidInvoices = $invoices->where('status', 'paid')->count();
        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');

        $stats[] = Stat::make('Total Invoices', $totalInvoices)
            ->description($paidInvoices . ' paid')
            ->icon('heroicon-o-receipt-percent')
            ->color('info');

        $stats[] = Stat::make('Total Revenue', number_format($totalRevenue, 2) . ' RON')
            ->description('From paid invoices')
            ->icon('heroicon-o-currency-dollar')
            ->color('success');

        // Last Activity
        $lastContract = $contracts->orderBy('created_at', 'desc')->first();
        if ($lastContract) {
            $stats[] = Stat::make('Last Activity', $lastContract->created_at->diffForHumans())
                ->description('Last contract created')
                ->icon('heroicon-o-clock')
                ->color('gray');
        }

        return $stats;
    }
}
