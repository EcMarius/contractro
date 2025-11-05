<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractType;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ContractService
{
    /**
     * Create a new contract
     */
    public function create(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            // Generate contract number
            $contractNumber = Contract::generateContractNumber(
                $data['company_id'],
                $data['contract_type_id']
            );

            // Create contract
            $contract = Contract::create(array_merge($data, [
                'contract_number' => $contractNumber,
                'status' => $data['status'] ?? 'draft',
            ]));

            // Add parties if provided
            if (isset($data['parties']) && is_array($data['parties'])) {
                foreach ($data['parties'] as $partyData) {
                    $contract->addParty($partyData);
                }
            }

            return $contract->fresh();
        });
    }

    /**
     * Update a contract
     */
    public function update(Contract $contract, array $data): Contract
    {
        $contract->update($data);
        return $contract->fresh();
    }

    /**
     * Delete a contract (soft delete)
     */
    public function delete(Contract $contract): bool
    {
        return $contract->delete();
    }

    /**
     * Duplicate a contract
     */
    public function duplicate(Contract $contract): Contract
    {
        return $contract->duplicate();
    }

    /**
     * Send contract for signing
     */
    public function sendForSigning(Contract $contract): array
    {
        $results = [];

        foreach ($contract->contractParties as $party) {
            // Generate signing link
            $link = $party->generateSigningLink();

            // TODO: Send email notification (Phase 11)
            // For now, just collect links
            $results[] = [
                'party_id' => $party->id,
                'party_name' => $party->name,
                'signing_link' => $link,
            ];
        }

        // Update contract status
        $contract->update(['status' => 'pending']);

        return $results;
    }

    /**
     * Sign contract (if all parties have signed)
     */
    public function signContract(Contract $contract): bool
    {
        if ($contract->isFullySigned()) {
            return $contract->sign();
        }

        return false;
    }

    /**
     * Add party to contract
     */
    public function addParty(Contract $contract, array $partyData): ContractParty
    {
        return $contract->addParty($partyData);
    }

    /**
     * Remove party from contract
     */
    public function removeParty(ContractParty $party): bool
    {
        if ($party->is_signed) {
            throw new \Exception('Cannot remove a party that has already signed');
        }

        return $party->delete();
    }

    /**
     * Add amendment to contract
     */
    public function addAmendment(Contract $contract, array $amendmentData): mixed
    {
        return $contract->addAmendment($amendmentData);
    }

    /**
     * Terminate contract
     */
    public function terminate(Contract $contract, ?string $reason = null): bool
    {
        $metadata = $contract->metadata ?? [];
        $metadata['termination_reason'] = $reason;
        $metadata['terminated_at'] = now()->toDateTimeString();

        return $contract->update([
            'status' => 'terminated',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Activate contract (after signing)
     */
    public function activate(Contract $contract): bool
    {
        if (!$contract->isFullySigned()) {
            throw new \Exception('Contract must be fully signed before activation');
        }

        return $contract->update(['status' => 'active']);
    }

    /**
     * Check and mark expired contracts
     */
    public function markExpiredContracts(): int
    {
        $expired = Contract::where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->get();

        foreach ($expired as $contract) {
            $contract->update(['status' => 'expired']);
        }

        return $expired->count();
    }

    /**
     * Get expiring contracts (within specified days)
     */
    public function getExpiringContracts(int $companyId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Contract::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->get();
    }

    /**
     * Get contract statistics for a company
     */
    public function getStatistics(int $companyId): array
    {
        $company = Company::findOrFail($companyId);

        return [
            'total_contracts' => $company->contracts()->count(),
            'active_contracts' => $company->contracts()->where('status', 'active')->count(),
            'pending_signature' => $company->contracts()->where('status', 'pending')->count(),
            'expired_contracts' => $company->contracts()->where('status', 'expired')->count(),
            'draft_contracts' => $company->contracts()->where('status', 'draft')->count(),
            'total_value' => $company->contracts()->where('status', 'active')->sum('value'),
            'expiring_soon' => $this->getExpiringContracts($companyId, 30)->count(),
        ];
    }

    /**
     * Search contracts with advanced filters
     */
    public function search(int $companyId, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Contract::where('company_id', $companyId);

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Contract type filter
        if (isset($filters['contract_type_id'])) {
            $query->where('contract_type_id', $filters['contract_type_id']);
        }

        // Date range filter
        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Search by contract number or title
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Party name search
        if (isset($filters['party_name'])) {
            $query->whereHas('contractParties', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['party_name']}%");
            });
        }

        return $query;
    }
}
