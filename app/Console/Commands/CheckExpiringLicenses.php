<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Notifications\LicenseExpiringNotification;
use Illuminate\Console\Command;

class CheckExpiringLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:check-expiring
                          {--days=* : Days before expiration to send notifications (e.g., 30,7,1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring licenses and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Default notification days: 30, 7, and 1 day before expiration
        $notificationDays = $this->option('days') ?: [30, 7, 1];
        $notificationDays = is_array($notificationDays) ? $notificationDays : [$notificationDays];

        $this->info('Checking for expiring licenses...');

        $totalNotifications = 0;

        foreach ($notificationDays as $days) {
            $days = (int) $days;

            // Find licenses expiring in exactly X days
            $expiringLicenses = License::active()
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [
                    now()->addDays($days)->startOfDay(),
                    now()->addDays($days)->endOfDay(),
                ])
                ->with('user')
                ->get();

            if ($expiringLicenses->isEmpty()) {
                $this->line("  No licenses expiring in {$days} days");
                continue;
            }

            $this->info("  Found {$expiringLicenses->count()} license(s) expiring in {$days} days");

            foreach ($expiringLicenses as $license) {
                try {
                    // Send notification to license owner
                    $license->user->notify(new LicenseExpiringNotification($license, $days));

                    $this->line("    ✓ Notified {$license->user->name} about license {$license->license_key}");
                    $totalNotifications++;
                } catch (\Exception $e) {
                    $this->error("    ✗ Failed to notify {$license->user->name}: {$e->getMessage()}");
                }
            }
        }

        // Check for already expired licenses that are still marked as active
        $expiredLicenses = License::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('user')
            ->get();

        if ($expiredLicenses->count() > 0) {
            $this->warn("  Found {$expiredLicenses->count()} expired license(s) still marked as active");

            foreach ($expiredLicenses as $license) {
                try {
                    // Update status to expired
                    $license->update(['status' => 'expired']);

                    // Notify user
                    $license->user->notify(new LicenseExpiringNotification($license, 0));

                    $this->line("    ✓ Updated and notified {$license->user->name} about expired license {$license->license_key}");
                    $totalNotifications++;
                } catch (\Exception $e) {
                    $this->error("    ✗ Failed to process expired license {$license->license_key}: {$e->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info("✓ License expiration check complete. Sent {$totalNotifications} notification(s).");

        return Command::SUCCESS;
    }
}
