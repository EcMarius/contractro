<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractSignature;
use Illuminate\Support\Facades\Log;

class SignatureService
{
    /**
     * Initiate signing process for a party
     */
    public function initiateSigning(ContractParty $party, string $phone): ContractSignature
    {
        // Create signature record
        $signature = ContractSignature::create([
            'contract_id' => $party->contract_id,
            'party_id' => $party->id,
            'signature_method' => 'sms',
            'verification_phone' => $phone,
        ]);

        // Send SMS verification code
        $signature->sendVerificationCode($phone);

        return $signature;
    }

    /**
     * Verify SMS code
     */
    public function verifyCode(ContractSignature $signature, string $code): bool
    {
        return $signature->verifyCode($code);
    }

    /**
     * Complete signing after verification
     */
    public function completeSigning(ContractSignature $signature, string $ipAddress, string $userAgent): bool
    {
        $completed = $signature->completeSigning($ipAddress, $userAgent);

        if ($completed) {
            // Check if all parties have signed
            $contract = $signature->contract;
            if ($contract->isFullySigned()) {
                $contract->sign();

                // Notify all parties that contract is fully signed
                foreach ($contract->contractParties as $party) {
                    if ($party->email) {
                        try {
                            \Notification::route('mail', $party->email)
                                ->notify(new \App\Notifications\ContractSignedNotification($contract));
                        } catch (\Exception $e) {
                            Log::error('Failed to send signed notification', [
                                'party_id' => $party->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                // Also notify the contract owner
                if ($contract->company->email) {
                    try {
                        \Notification::route('mail', $contract->company->email)
                            ->notify(new \App\Notifications\ContractSignedNotification($contract));
                    } catch (\Exception $e) {
                        Log::error('Failed to send signed notification to company', [
                            'company_id' => $contract->company_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            Log::info('Contract signature completed', [
                'contract_id' => $signature->contract_id,
                'party_id' => $signature->party_id,
                'ip_address' => $ipAddress,
            ]);
        }

        return $completed;
    }

    /**
     * Resend verification code
     */
    public function resendCode(ContractSignature $signature): bool
    {
        // Check if last SMS was sent less than 2 minutes ago (rate limiting)
        if ($signature->code_sent_at && now()->diffInMinutes($signature->code_sent_at) < 2) {
            throw new \Exception('Vă rugăm așteptați 2 minute înainte de a trimite un nou cod');
        }

        return $signature->sendVerificationCode($signature->verification_phone);
    }

    /**
     * Get signing status for a contract
     */
    public function getSigningStatus(Contract $contract): array
    {
        $parties = $contract->contractParties;
        $status = [];

        foreach ($parties as $party) {
            $status[] = [
                'party_id' => $party->id,
                'party_name' => $party->name,
                'party_type' => $party->party_type,
                'is_signed' => $party->is_signed,
                'signed_at' => $party->signed_at?->toDateTimeString(),
                'signature_count' => $party->signatures()->count(),
            ];
        }

        return [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'status' => $contract->status,
            'is_fully_signed' => $contract->isFullySigned(),
            'parties' => $status,
            'total_parties' => $parties->count(),
            'signed_parties' => $parties->where('is_signed', true)->count(),
        ];
    }

    /**
     * Generate EU eIDAS compliance report for a contract
     */
    public function generateEidasReport(Contract $contract): array
    {
        $signatures = ContractSignature::where('contract_id', $contract->id)
            ->where('code_verified', true)
            ->get();

        $report = [
            'contract_number' => $contract->contract_number,
            'contract_title' => $contract->title,
            'signed_at' => $contract->signed_at?->toIso8601String(),
            'signatures' => [],
        ];

        foreach ($signatures as $signature) {
            $report['signatures'][] = $signature->getEidasMetadata();
        }

        return $report;
    }

    /**
     * Validate signature authenticity
     */
    public function validateSignature(ContractSignature $signature): array
    {
        $valid = true;
        $issues = [];

        // Check if code was verified
        if (!$signature->code_verified) {
            $valid = false;
            $issues[] = 'Cod de verificare neconfirmat';
        }

        // Check if signature was completed
        if (!$signature->signed_at) {
            $valid = false;
            $issues[] = 'Semnătură incompletă';
        }

        // Check if party marked as signed
        if (!$signature->party->is_signed) {
            $valid = false;
            $issues[] = 'Partea nu este marcată ca semnată';
        }

        // Check time consistency
        if ($signature->code_sent_at && $signature->signed_at) {
            $timeDiff = $signature->signed_at->diffInMinutes($signature->code_sent_at);
            if ($timeDiff > 30) {
                $issues[] = 'Timp suspect între trimiterea codului și semnare';
            }
        }

        return [
            'valid' => $valid,
            'issues' => $issues,
            'metadata' => $signature->getEidasMetadata(),
        ];
    }

    /**
     * Send SMS via configured provider
     * This will be fully implemented in Phase 7
     */
    protected function sendSMS(string $phone, string $message): bool
    {
        // TODO: Integrate with SMS provider (Twilio, ClickSend, SMS Link Romania)
        // For now, log the SMS (development mode)

        Log::info('SMS would be sent', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Get signature statistics for a company
     */
    public function getSignatureStatistics(int $companyId): array
    {
        $contracts = Contract::where('company_id', $companyId)->get();

        $stats = [
            'total_signatures' => 0,
            'pending_signatures' => 0,
            'completed_signatures' => 0,
            'average_signing_time' => 0, // in hours
        ];

        foreach ($contracts as $contract) {
            $totalParties = $contract->contractParties()->count();
            $signedParties = $contract->contractParties()->where('is_signed', true)->count();

            $stats['total_signatures'] += $totalParties;
            $stats['completed_signatures'] += $signedParties;
            $stats['pending_signatures'] += ($totalParties - $signedParties);
        }

        // Calculate average signing time for completed contracts
        $signedContracts = $contracts->where('status', 'signed');
        if ($signedContracts->count() > 0) {
            $totalHours = 0;
            foreach ($signedContracts as $contract) {
                if ($contract->created_at && $contract->signed_at) {
                    $totalHours += $contract->created_at->diffInHours($contract->signed_at);
                }
            }
            $stats['average_signing_time'] = round($totalHours / $signedContracts->count(), 2);
        }

        return $stats;
    }
}
