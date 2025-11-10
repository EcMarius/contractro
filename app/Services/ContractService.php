<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractSignature;
use App\Models\User;
use App\Notifications\ContractSignatureRequested;
use App\Notifications\ContractSigned;
use App\Notifications\ContractFullySigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ContractService
{
    /**
     * Create a contract from a template
     */
    public function createFromTemplate(ContractTemplate $template, array $data): Contract
    {
        DB::beginTransaction();
        try {
            // Increment template usage
            $template->incrementUsage();

            // Process variables from template
            $variables = $data['variables'] ?? [];
            $content = $this->processVariables($template->content, $variables);

            // Create contract
            $contract = Contract::create([
                'user_id' => $data['user_id'] ?? auth()->id(),
                'organization_id' => $data['organization_id'] ?? null,
                'template_id' => $template->id,
                'lead_id' => $data['lead_id'] ?? null,
                'title' => $data['title'] ?? $template->name,
                'description' => $data['description'] ?? $template->description,
                'content' => $content,
                'variables' => $variables,
                'status' => 'draft',
                'contract_value' => $data['contract_value'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'created_by' => auth()->id(),
                'effective_date' => $data['effective_date'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Create initial version
            $contract->createVersion('Initial contract created from template');

            DB::commit();
            return $contract;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process variables in contract content
     */
    public function processVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            // Handle both {{variable}} and {{ variable }} formats
            $content = preg_replace('/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/', $value, $content);
        }
        return $content;
    }

    /**
     * Update contract content
     */
    public function updateContract(Contract $contract, array $data): Contract
    {
        DB::beginTransaction();
        try {
            // Save current state as version before updating
            if ($contract->isDirty('content') || $contract->isDirty('variables')) {
                $contract->createVersion($data['change_summary'] ?? 'Contract updated');
            }

            $contract->update($data);

            DB::commit();
            return $contract->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send contract for signature
     */
    public function sendForSignature(Contract $contract, array $signers): Contract
    {
        DB::beginTransaction();
        try {
            $order = 0;
            foreach ($signers as $signer) {
                $signature = ContractSignature::create([
                    'contract_id' => $contract->id,
                    'user_id' => $signer['user_id'] ?? null,
                    'signer_name' => $signer['name'],
                    'signer_email' => $signer['email'],
                    'signer_role' => $signer['role'] ?? 'Signer',
                    'signing_order' => $signer['order'] ?? $order++,
                    'status' => 'pending',
                    'expires_at' => $signer['expires_at'] ?? now()->addDays(14),
                ]);

                // Send notification to signer
                if ($signature->user_id) {
                    // Signer is a registered user
                    $signature->user->notify(new ContractSignatureRequested($contract, $signature));
                } else {
                    // Signer is external (email only)
                    Notification::route('mail', $signature->signer_email)
                        ->notify(new ContractSignatureRequested($contract, $signature));
                }
            }

            $contract->update(['status' => 'pending_signature']);

            DB::commit();
            return $contract->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Sign a contract
     */
    public function signContract(ContractSignature $signature, string $signatureData, string $signatureType = 'drawn'): void
    {
        DB::beginTransaction();
        try {
            $signature->sign($signatureData, $signatureType);

            // Notify contract owner about the signature
            $contract = $signature->contract;
            $contract->user->notify(new ContractSigned($contract, $signature));

            // Check if all signatures are complete
            $contract->checkSignatureStatus();

            // If contract is now fully signed, notify all signers and owner
            if ($contract->status === 'signed') {
                // Notify contract owner
                $contract->user->notify(new ContractFullySigned($contract));

                // Notify all signers
                foreach ($contract->signatures as $sig) {
                    if ($sig->user_id) {
                        $sig->user->notify(new ContractFullySigned($contract));
                    } else {
                        Notification::route('mail', $sig->signer_email)
                            ->notify(new ContractFullySigned($contract));
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Duplicate a contract
     */
    public function duplicate(Contract $contract, array $overrides = []): Contract
    {
        DB::beginTransaction();
        try {
            $newContract = $contract->replicate();
            $newContract->status = 'draft';
            $newContract->signed_at = null;
            $newContract->signed_by = null;
            $newContract->contract_number = null; // Will be auto-generated
            $newContract->created_by = auth()->id();

            // Apply overrides
            foreach ($overrides as $key => $value) {
                $newContract->$key = $value;
            }

            $newContract->save();

            // Create initial version for new contract
            $newContract->createVersion('Duplicated from contract #' . $contract->contract_number);

            DB::commit();
            return $newContract;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get contract statistics for a user
     */
    public function getUserStatistics(User $user): array
    {
        $contracts = Contract::where('user_id', $user->id);

        return [
            'total' => $contracts->count(),
            'draft' => (clone $contracts)->draft()->count(),
            'pending_signature' => (clone $contracts)->pendingSignature()->count(),
            'signed' => (clone $contracts)->signed()->count(),
            'expiring_soon' => (clone $contracts)->expiringSoon(30)->count(),
            'expired' => (clone $contracts)->expired()->count(),
            'total_value' => (clone $contracts)->signed()->sum('contract_value'),
        ];
    }

    /**
     * Search contracts
     */
    public function search(string $query, array $filters = [])
    {
        $builder = Contract::query()
            ->with(['user', 'template', 'signatures', 'creator'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('contract_number', 'like', "%{$query}%");
            });

        // Apply filters
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['organization_id'])) {
            $builder->where('organization_id', $filters['organization_id']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('created_at', '<=', $filters['date_to']);
        }

        return $builder->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Archive expired contracts
     */
    public function archiveExpiredContracts(): int
    {
        $count = Contract::expired()
            ->whereIn('status', ['signed', 'completed'])
            ->update(['status' => 'expired']);

        return $count;
    }
}
