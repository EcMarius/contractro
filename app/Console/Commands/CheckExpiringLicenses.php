<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\ScheduledJobRun;
use App\Models\User;
use App\Notifications\LicenseExpiringNotification;
use App\Notifications\ScheduledJobFailedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

    protected ScheduledJobRun $jobRun;
    protected array $output = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Create job run record
        $this->jobRun = ScheduledJobRun::create([
            'job_name' => 'licenses:check-expiring',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $this->executeJob();

            // Mark as successful
            $this->jobRun->markSuccess(
                implode("\n", $this->output),
                $result
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Mark as failed
            $this->jobRun->markFailed(
                $e->getMessage(),
                implode("\n", $this->output)
            );

            // Log the error
            Log::error('License expiration check failed', [
                'job_run_id' => $this->jobRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Check for consecutive failures and alert admins
            $this->alertAdminsIfNeeded();

            // Re-throw to ensure command fails
            $this->error("✗ Job failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function executeJob(): array
    {
        // Default notification days: 30, 7, and 1 day before expiration
        $notificationDays = $this->option('days') ?: [30, 7, 1];
        $notificationDays = is_array($notificationDays) ? $notificationDays : [$notificationDays];

        $message = 'Checking for expiring licenses...';
        $this->info($message);
        $this->output[] = $message;

        $totalNotifications = 0;
        $licensesByDay = [];
        $expiredCount = 0;
        $failedCount = 0;

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

            $licensesByDay[$days] = $expiringLicenses->count();

            if ($expiringLicenses->isEmpty()) {
                $message = "  No licenses expiring in {$days} days";
                $this->line($message);
                $this->output[] = $message;
                continue;
            }

            $message = "  Found {$expiringLicenses->count()} license(s) expiring in {$days} days";
            $this->info($message);
            $this->output[] = $message;

            foreach ($expiringLicenses as $license) {
                try {
                    // Send notification to license owner
                    $license->user->notify(new LicenseExpiringNotification($license, $days));

                    $message = "    ✓ Notified {$license->user->name} about license {$license->license_key}";
                    $this->line($message);
                    $this->output[] = $message;
                    $totalNotifications++;
                } catch (\Exception $e) {
                    $message = "    ✗ Failed to notify {$license->user->name}: {$e->getMessage()}";
                    $this->error($message);
                    $this->output[] = $message;
                    $failedCount++;
                }
            }
        }

        // Check for already expired licenses that are still marked as active
        $expiredLicenses = License::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->with('user')
            ->get();

        $expiredCount = $expiredLicenses->count();

        if ($expiredCount > 0) {
            $message = "  Found {$expiredCount} expired license(s) still marked as active";
            $this->warn($message);
            $this->output[] = $message;

            foreach ($expiredLicenses as $license) {
                try {
                    // Update status to expired
                    $license->update(['status' => 'expired']);

                    // Notify user
                    $license->user->notify(new LicenseExpiringNotification($license, 0));

                    $message = "    ✓ Updated and notified {$license->user->name} about expired license {$license->license_key}";
                    $this->line($message);
                    $this->output[] = $message;
                    $totalNotifications++;
                } catch (\Exception $e) {
                    $message = "    ✗ Failed to process expired license {$license->license_key}: {$e->getMessage()}";
                    $this->error($message);
                    $this->output[] = $message;
                    $failedCount++;
                }
            }
        }

        $this->newLine();
        $message = "✓ License expiration check complete. Sent {$totalNotifications} notification(s).";
        $this->info($message);
        $this->output[] = $message;

        return [
            'total_notifications_sent' => $totalNotifications,
            'expired_licenses_updated' => $expiredCount,
            'failed_notifications' => $failedCount,
            'licenses_by_expiration_day' => $licensesByDay,
        ];
    }

    /**
     * Alert admins if there are consecutive failures
     */
    protected function alertAdminsIfNeeded(): void
    {
        $recentFailures = ScheduledJobRun::getRecentFailures('licenses:check-expiring', 24);

        // Alert admins if 3+ failures in 24 hours
        if ($recentFailures >= 3) {
            // Get all admin users
            $admins = User::whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })->get();

            foreach ($admins as $admin) {
                try {
                    $admin->notify(new ScheduledJobFailedNotification(
                        $this->jobRun,
                        $recentFailures
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send job failure notification to admin', [
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
