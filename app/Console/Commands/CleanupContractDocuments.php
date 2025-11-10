<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\ContractPDFService;
use Illuminate\Console\Command;

class CleanupContractDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:cleanup
                            {--archive : Archive old PDF versions}
                            {--delete-expired : Delete expired archived PDFs}
                            {--keep=3 : Number of latest versions to keep when archiving}
                            {--retention-days= : Retention period in days (overrides setting)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup contract documents (archive old versions, delete expired archives)';

    /**
     * Execute the console command.
     */
    public function handle(ContractPDFService $pdfService): int
    {
        $this->info('Starting contract document cleanup...');

        $archive = $this->option('archive');
        $deleteExpired = $this->option('delete-expired');

        if (!$archive && !$deleteExpired) {
            $this->warn('No action specified. Use --archive or --delete-expired');
            return Command::FAILURE;
        }

        // Archive old versions
        if ($archive) {
            $this->info('Archiving old PDF versions...');
            $keepN = (int) $this->option('keep');
            $totalArchived = 0;

            $contracts = Contract::has('versions', '>', $keepN)->get();

            $this->output->progressStart($contracts->count());

            foreach ($contracts as $contract) {
                $archived = $pdfService->archiveOldVersions($contract, $keepN);
                $totalArchived += $archived;
                $this->output->progressAdvance();
            }

            $this->output->progressFinish();
            $this->info("Archived {$totalArchived} old PDF versions (keeping latest {$keepN})");
        }

        // Delete expired archives
        if ($deleteExpired) {
            $this->info('Deleting expired archived PDFs...');
            $retentionDays = $this->option('retention-days')
                ? (int) $this->option('retention-days')
                : null;

            $deleted = $pdfService->deleteExpiredArchives($retentionDays);

            $daysText = $retentionDays ?? (int) setting('contracts.data_retention_days', 2555);
            $this->info("Deleted {$deleted} expired PDFs (retention: {$daysText} days)");
        }

        $this->info('Contract document cleanup completed successfully!');

        return Command::SUCCESS;
    }
}
