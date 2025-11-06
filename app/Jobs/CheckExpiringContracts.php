<?php

namespace App\Jobs;

use App\Models\Contract;
use App\Notifications\ContractExpiringSoon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CheckExpiringContracts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send notifications for contracts expiring in 7, 14, and 30 days
        $this->checkExpiringIn(30);
        $this->checkExpiringIn(14);
        $this->checkExpiringIn(7);

        \Log::info('Checked expiring contracts');
    }

    /**
     * Check and notify for contracts expiring in specific days
     */
    private function checkExpiringIn(int $days): void
    {
        $targetDate = Carbon::now()->addDays($days)->startOfDay();
        $nextDay = $targetDate->copy()->addDay();

        $expiringContracts = Contract::whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [$targetDate, $nextDay])
            ->whereIn('status', ['signed', 'completed'])
            ->with('user')
            ->get();

        foreach ($expiringContracts as $contract) {
            // Send notification to contract owner
            $contract->user->notify(new ContractExpiringSoon($contract, $days));

            \Log::info("Sent expiration notice for contract {$contract->id} expiring in {$days} days");
        }
    }
}
