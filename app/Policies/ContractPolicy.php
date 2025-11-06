<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * Determine whether the user can view any contracts.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view contracts list (filtered by ownership in controller)
        return true;
    }

    /**
     * Determine whether the user can view the contract.
     */
    public function view(User $user, Contract $contract): bool
    {
        // Use the model's canBeViewedBy method
        return $contract->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can create contracts.
     */
    public function create(User $user): bool
    {
        // Check subscription plan limits
        $plan = $user->subscription('default')->plan ?? null;

        if (!$plan) {
            return false; // No active subscription
        }

        // Check if plan has contract feature
        $maxContracts = $plan->max_contracts ?? 0;

        if ($maxContracts === -1) {
            return true; // Unlimited
        }

        if ($maxContracts === 0) {
            return false; // No contracts allowed
        }

        // Check current usage
        $currentCount = $user->contracts()->count();

        return $currentCount < $maxContracts;
    }

    /**
     * Determine whether the user can update the contract.
     */
    public function update(User $user, Contract $contract): bool
    {
        // Use the model's canBeEditedBy method (only draft contracts by owner)
        return $contract->canBeEditedBy($user);
    }

    /**
     * Determine whether the user can delete the contract.
     */
    public function delete(User $user, Contract $contract): bool
    {
        // Can delete if user is owner and contract is not signed/completed
        if (!in_array($contract->status, ['draft', 'cancelled'])) {
            return false; // Cannot delete signed or completed contracts
        }

        return $contract->user_id === $user->id || $contract->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the contract.
     */
    public function restore(User $user, Contract $contract): bool
    {
        // Can restore if user is owner
        return $contract->user_id === $user->id || $contract->created_by === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the contract.
     */
    public function forceDelete(User $user, Contract $contract): bool
    {
        // Only admins can force delete
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can duplicate the contract.
     */
    public function duplicate(User $user, Contract $contract): bool
    {
        // Can duplicate if can view the contract
        return $contract->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can send contract for signature.
     */
    public function sendForSignature(User $user, Contract $contract): bool
    {
        // Can send if can edit and contract is in draft status
        if ($contract->status !== 'draft') {
            return false;
        }

        return $contract->canBeEditedBy($user);
    }

    /**
     * Determine whether the user can download PDF.
     */
    public function downloadPdf(User $user, Contract $contract): bool
    {
        // Can download if can view
        return $contract->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can use AI features on the contract.
     */
    public function useAI(User $user, Contract $contract): bool
    {
        // Check if plan has AI features enabled
        $plan = $user->subscription('default')->plan ?? null;

        if (!$plan || !($plan->enable_ai_contract_generation ?? false)) {
            return false;
        }

        // Must be able to view the contract
        return $contract->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can manage versions.
     */
    public function manageVersions(User $user, Contract $contract): bool
    {
        // Check if plan has version control enabled
        $plan = $user->subscription('default')->plan ?? null;

        if (!$plan || !($plan->enable_contract_version_control ?? true)) {
            return false;
        }

        // Must be able to edit the contract
        return $contract->canBeEditedBy($user);
    }
}
