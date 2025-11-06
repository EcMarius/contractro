<?php

namespace App\Jobs;

use App\Models\ContractSignature;
use App\Notifications\SignatureReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class SendSignatureReminders implements ShouldQueue
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
        // Find all pending signatures that:
        // 1. Are still pending (not signed/declined/expired)
        // 2. Were created more than 3 days ago
        // 3. Haven't received a reminder in the last 3 days
        // 4. Haven't expired yet

        $threeDaysAgo = Carbon::now()->subDays(3);
        $now = Carbon::now();

        $pendingSignatures = ContractSignature::where('status', 'pending')
            ->where('created_at', '<=', $threeDaysAgo)
            ->where('expires_at', '>', $now)
            ->where(function ($query) use ($threeDaysAgo) {
                $query->whereNull('last_reminder_sent_at')
                      ->orWhere('last_reminder_sent_at', '<=', $threeDaysAgo);
            })
            ->with('contract.user')
            ->get();

        foreach ($pendingSignatures as $signature) {
            // Send reminder notification
            Notification::route('mail', $signature->signer_email)
                ->notify(new SignatureReminder($signature->contract, $signature));

            // Update last reminder sent timestamp
            $signature->update([
                'last_reminder_sent_at' => now(),
            ]);
        }

        \Log::info('Sent ' . $pendingSignatures->count() . ' signature reminders');
    }
}
