<?php

namespace App\Services;

use App\Models\License;
use App\Models\User;

class UserDeletionGuard
{
    /**
     * Check if user can be safely deleted
     *
     * @param User $user
     * @return array
     */
    public function canDeleteUser(User $user): array
    {
        // Check for active licenses
        $activeLicenses = License::where('user_id', $user->id)
            ->whereIn('status', ['active', 'expired']) // Include expired licenses within grace period
            ->get();

        if ($activeLicenses->count() > 0) {
            return [
                'can_delete' => false,
                'reason' => 'user_has_active_licenses',
                'message' => "Cannot delete user: {$activeLicenses->count()} active license(s) found",
                'active_licenses_count' => $activeLicenses->count(),
                'licenses' => $activeLicenses->map(fn($l) => [
                    'license_key' => $l->license_key,
                    'domain' => $l->domain,
                    'status' => $l->status,
                    'product_name' => $l->product_name,
                    'expires_at' => $l->expires_at?->toDateTimeString(),
                ]),
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null,
            'message' => 'User can be safely deleted',
        ];
    }

    /**
     * Cancel all user licenses before deletion
     *
     * @param User $user
     * @param string|null $reason
     * @return array
     */
    public function cancelAllUserLicenses(User $user, string $reason = null): array
    {
        $licenses = License::where('user_id', $user->id)
            ->whereIn('status', ['active', 'expired', 'suspended'])
            ->get();

        $canceledCount = 0;
        $errors = [];

        foreach ($licenses as $license) {
            try {
                $license->update([
                    'status' => 'cancelled',
                    'notes' => $reason ?? 'Cancelled due to user account deletion',
                ]);
                $canceledCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'license_key' => $license->license_key,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($errors) === 0,
            'canceled_count' => $canceledCount,
            'total_licenses' => $licenses->count(),
            'errors' => $errors,
        ];
    }

    /**
     * Transfer all user licenses to another user
     *
     * @param User $fromUser
     * @param User $toUser
     * @param string|null $reason
     * @return array
     */
    public function transferAllLicenses(User $fromUser, User $toUser, string $reason = null): array
    {
        $licenses = License::where('user_id', $fromUser->id)->get();

        $transferredCount = 0;
        $errors = [];

        foreach ($licenses as $license) {
            try {
                $license->update([
                    'user_id' => $toUser->id,
                    'notes' => ($license->notes ?? '') . "\n\nTransferred from user #{$fromUser->id} to user #{$toUser->id}: " . ($reason ?? 'User account deletion'),
                ]);
                $transferredCount++;
            } catch (\Exception $e) {
                $errors[] = [
                    'license_key' => $license->license_key,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => count($errors) === 0,
            'transferred_count' => $transferredCount,
            'total_licenses' => $licenses->count(),
            'errors' => $errors,
        ];
    }

    /**
     * Prepare user for deletion by handling licenses
     *
     * @param User $user
     * @param string $action 'cancel' or 'transfer'
     * @param User|null $transferToUser Required if action is 'transfer'
     * @param string|null $reason
     * @return array
     */
    public function prepareUserForDeletion(
        User $user,
        string $action = 'cancel',
        ?User $transferToUser = null,
        ?string $reason = null
    ): array {
        // Check if user has licenses
        $checkResult = $this->canDeleteUser($user);

        if ($checkResult['can_delete']) {
            return [
                'success' => true,
                'message' => 'User has no active licenses, can be deleted safely',
                'action_taken' => 'none',
            ];
        }

        // Handle licenses based on action
        if ($action === 'transfer') {
            if (!$transferToUser) {
                return [
                    'success' => false,
                    'message' => 'Transfer user is required when action is "transfer"',
                ];
            }

            $result = $this->transferAllLicenses($user, $transferToUser, $reason);

            return [
                'success' => $result['success'],
                'message' => $result['success']
                    ? "Successfully transferred {$result['transferred_count']} license(s) to {$transferToUser->name}"
                    : "Failed to transfer some licenses",
                'action_taken' => 'transfer',
                'details' => $result,
            ];
        }

        // Default: cancel all licenses
        $result = $this->cancelAllUserLicenses($user, $reason);

        return [
            'success' => $result['success'],
            'message' => $result['success']
                ? "Successfully cancelled {$result['canceled_count']} license(s)"
                : "Failed to cancel some licenses",
            'action_taken' => 'cancel',
            'details' => $result,
        ];
    }
}
