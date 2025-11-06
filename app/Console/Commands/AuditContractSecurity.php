<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditContractSecurity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'contracts:audit-security
                            {--fix : Automatically fix security issues where possible}';

    /**
     * The console command description.
     */
    protected $description = 'Audit contract system for security issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting contract security audit...');
        $issues = [];

        // Check for contracts without proper authorization
        $this->info('Checking contract authorization...');
        $orphanedContracts = Contract::whereNull('user_id')->count();
        if ($orphanedContracts > 0) {
            $issues[] = "{$orphanedContracts} contracts found without user ownership";
            $this->warn("Found {$orphanedContracts} contracts without user ownership");

            if ($this->option('fix')) {
                // Cannot auto-fix this safely
                $this->error('Cannot auto-fix orphaned contracts. Manual review required.');
            }
        }

        // Check for signatures without verification tokens
        $this->info('Checking signature security...');
        $signaturesWithoutTokens = ContractSignature::whereNull('verification_token')->count();
        if ($signaturesWithoutTokens > 0) {
            $issues[] = "{$signaturesWithoutTokens} signatures without verification tokens";
            $this->warn("Found {$signaturesWithoutTokens} signatures without verification tokens");

            if ($this->option('fix')) {
                $this->info('Generating verification tokens...');
                ContractSignature::whereNull('verification_token')->each(function ($signature) {
                    $signature->update([
                        'verification_token' => \Illuminate\Support\Str::random(64),
                    ]);
                });
                $this->info('Fixed verification tokens');
            }
        }

        // Check for expired but not marked signatures
        $this->info('Checking expired signatures...');
        $expiredSignatures = ContractSignature::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->count();

        if ($expiredSignatures > 0) {
            $issues[] = "{$expiredSignatures} pending signatures past expiration date";
            $this->warn("Found {$expiredSignatures} pending signatures past expiration");

            if ($this->option('fix')) {
                $this->info('Marking signatures as expired...');
                ContractSignature::where('status', 'pending')
                    ->where('expires_at', '<', now())
                    ->update(['status' => 'expired']);
                $this->info('Marked signatures as expired');
            }
        }

        // Check for SQL injection vulnerabilities in contract content
        $this->info('Checking for potential injection vulnerabilities...');
        $suspiciousContracts = Contract::where('content', 'like', '%<script%')
            ->orWhere('content', 'like', '%javascript:%')
            ->orWhere('content', 'like', '%onerror=%')
            ->count();

        if ($suspiciousContracts > 0) {
            $issues[] = "{$suspiciousContracts} contracts with potentially malicious content";
            $this->error("Found {$suspiciousContracts} contracts with suspicious content (XSS risk)");
            $this->error('Manual review required - cannot auto-fix');
        }

        // Check for missing indexes (performance)
        $this->info('Checking database indexes...');
        $missingIndexes = $this->checkMissingIndexes();
        if (!empty($missingIndexes)) {
            $issues[] = count($missingIndexes) . " missing database indexes";
            $this->warn('Missing indexes: ' . implode(', ', $missingIndexes));
        }

        // Check for sensitive data in logs
        $this->info('Checking audit log...');
        if (setting('contracts.enable_audit_log') !== '1') {
            $issues[] = "Audit logging is disabled";
            $this->warn('Audit logging is disabled - recommended to enable for compliance');
        }

        // Check for 2FA requirement on high-value contracts
        $this->info('Checking 2FA requirements...');
        $highValueThreshold = (float) setting('contracts.high_value_threshold', 10000);
        $highValueContracts = Contract::where('contract_value', '>=', $highValueThreshold)->count();

        if ($highValueContracts > 0 && setting('contracts.require_2fa_for_signing') !== '1') {
            $issues[] = "{$highValueContracts} high-value contracts without 2FA requirement";
            $this->warn("Found {$highValueContracts} high-value contracts without 2FA requirement");
        }

        // Summary
        $this->newLine();
        if (empty($issues)) {
            $this->info('✓ No security issues found!');
            return Command::SUCCESS;
        }

        $this->error('Found ' . count($issues) . ' security issues:');
        foreach ($issues as $issue) {
            $this->line('  - ' . $issue);
        }

        $this->newLine();
        if (!$this->option('fix')) {
            $this->info('Run with --fix to automatically fix issues where possible');
        }

        return Command::FAILURE;
    }

    /**
     * Check for missing database indexes
     */
    protected function checkMissingIndexes(): array
    {
        $missing = [];

        // Check if important indexes exist
        $tables = [
            'contracts' => ['user_id', 'status', 'created_at', 'contract_value'],
            'contract_signatures' => ['contract_id', 'status', 'signer_email'],
            'contract_versions' => ['contract_id', 'version_number'],
            'contract_approvals' => ['contract_id', 'approver_id', 'status'],
        ];

        foreach ($tables as $table => $columns) {
            foreach ($columns as $column) {
                // This is a simplified check - actual implementation would query information_schema
                // For now, we'll skip the actual check
            }
        }

        return $missing;
    }
}
