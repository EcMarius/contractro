<?php

namespace App\Console\Commands;

use App\Models\LicenseCheckLog;
use App\Models\ScheduledJobRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupLicenseCheckLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:cleanup-logs
                          {--days=90 : Number of days of logs to keep}
                          {--archive : Archive old logs instead of deleting}
                          {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old license check logs to prevent database bloat';

    protected ScheduledJobRun $jobRun;
    protected array $output = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Create job run record
        $this->jobRun = ScheduledJobRun::create([
            'job_name' => 'licenses:cleanup-logs',
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

            Log::error('License log cleanup failed', [
                'job_run_id' => $this->jobRun->id,
                'error' => $e->getMessage(),
            ]);

            $this->error("✗ Job failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function executeJob(): array
    {
        $daysToKeep = (int) $this->option('days');
        $archive = $this->option('archive');
        $dryRun = $this->option('dry-run');

        $cutoffDate = now()->subDays($daysToKeep);

        $message = "License Check Log Cleanup";
        $this->info($message);
        $this->output[] = $message;

        $message = "Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')} (keeping {$daysToKeep} days)";
        $this->line($message);
        $this->output[] = $message;

        if ($dryRun) {
            $message = "DRY RUN MODE - No changes will be made";
            $this->warn($message);
            $this->output[] = $message;
        }

        // Count logs to be deleted/archived
        $oldLogsCount = LicenseCheckLog::where('checked_at', '<', $cutoffDate)->count();

        $message = "Found {$oldLogsCount} log(s) older than {$daysToKeep} days";
        $this->info($message);
        $this->output[] = $message;

        if ($oldLogsCount === 0) {
            $message = "No logs to clean up";
            $this->line($message);
            $this->output[] = $message;

            return [
                'logs_processed' => 0,
                'action' => 'none',
            ];
        }

        // Get statistics before deletion
        $stats = $this->getLogStatistics($cutoffDate);

        if ($archive) {
            $archivedCount = $this->archiveLogs($cutoffDate, $dryRun);
            $message = $dryRun
                ? "Would archive {$archivedCount} log(s)"
                : "Archived {$archivedCount} log(s)";
            $this->info($message);
            $this->output[] = $message;

            return [
                'logs_processed' => $archivedCount,
                'action' => 'archive',
                'dry_run' => $dryRun,
                'statistics' => $stats,
            ];
        }

        // Delete old logs
        if (!$dryRun) {
            $deletedCount = LicenseCheckLog::where('checked_at', '<', $cutoffDate)->delete();
            $message = "Deleted {$deletedCount} log(s)";
            $this->info($message);
            $this->output[] = $message;

            // Optimize table after deletion
            if ($deletedCount > 1000) {
                $this->line("Optimizing table...");
                DB::statement('OPTIMIZE TABLE license_check_logs');
                $message = "Table optimized";
                $this->info($message);
                $this->output[] = $message;
            }
        } else {
            $message = "Would delete {$oldLogsCount} log(s)";
            $this->warn($message);
            $this->output[] = $message;
        }

        return [
            'logs_processed' => $dryRun ? 0 : $oldLogsCount,
            'action' => 'delete',
            'dry_run' => $dryRun,
            'statistics' => $stats,
        ];
    }

    protected function getLogStatistics($cutoffDate): array
    {
        $stats = [
            'total_logs' => LicenseCheckLog::count(),
            'old_logs' => LicenseCheckLog::where('checked_at', '<', $cutoffDate)->count(),
            'valid_checks' => LicenseCheckLog::where('checked_at', '<', $cutoffDate)->where('is_valid', true)->count(),
            'invalid_checks' => LicenseCheckLog::where('checked_at', '<', $cutoffDate)->where('is_valid', false)->count(),
            'unique_licenses' => LicenseCheckLog::where('checked_at', '<', $cutoffDate)->distinct('license_id')->count('license_id'),
            'unique_domains' => LicenseCheckLog::where('checked_at', '<', $cutoffDate)->distinct('domain')->count('domain'),
        ];

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total logs in database', number_format($stats['total_logs'])],
                ['Old logs to process', number_format($stats['old_logs'])],
                ['Valid checks', number_format($stats['valid_checks'])],
                ['Invalid checks', number_format($stats['invalid_checks'])],
                ['Unique licenses', number_format($stats['unique_licenses'])],
                ['Unique domains', number_format($stats['unique_domains'])],
            ]
        );

        return $stats;
    }

    protected function archiveLogs($cutoffDate, bool $dryRun): int
    {
        // In a real implementation, you would:
        // 1. Export old logs to a file (JSON, CSV, or compressed format)
        // 2. Store in cold storage (S3, local archive directory, etc.)
        // 3. Then delete from database

        if ($dryRun) {
            return LicenseCheckLog::where('checked_at', '<', $cutoffDate)->count();
        }

        $logs = LicenseCheckLog::where('checked_at', '<', $cutoffDate)->get();

        // Create archive directory
        $archiveDir = storage_path('app/archives/license_logs');
        if (!file_exists($archiveDir)) {
            mkdir($archiveDir, 0755, true);
        }

        // Export to JSON file (could also be CSV, or compressed)
        $archiveFile = $archiveDir . '/license_logs_' . date('Y-m-d_His') . '.json';
        file_put_contents($archiveFile, $logs->toJson(JSON_PRETTY_PRINT));

        $this->line("Archived to: {$archiveFile}");

        // Delete archived logs
        $deletedCount = LicenseCheckLog::where('checked_at', '<', $cutoffDate)->delete();

        return $deletedCount;
    }
}
