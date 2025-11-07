<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    /**
     * Determine whether the user can view any licenses.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view their licenses list
        return true;
    }

    /**
     * Determine whether the user can view the license.
     */
    public function view(User $user, License $license): bool
    {
        // User can view their own licenses, admins can view all
        return $user->id === $license->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create licenses.
     */
    public function create(User $user): bool
    {
        // Only admins can create licenses
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the license.
     */
    public function update(User $user, License $license): bool
    {
        // Only admins and license owner can update
        return $user->id === $license->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the license.
     */
    public function delete(User $user, License $license): bool
    {
        // Can delete if admin or owner and license is not active
        if ($license->status === 'active' && !$user->hasRole('admin')) {
            return false; // Regular users cannot delete active licenses
        }

        return $user->id === $license->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the license.
     */
    public function restore(User $user, License $license): bool
    {
        // Only admins can restore
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the license.
     */
    public function forceDelete(User $user, License $license): bool
    {
        // Only admins can force delete
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can renew the license.
     */
    public function renew(User $user, License $license): bool
    {
        // User can renew their own licenses if expired or active, admins can renew any
        return ($user->id === $license->user_id && in_array($license->status, ['active', 'expired']))
            || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can transfer the license.
     */
    public function transfer(User $user, License $license): bool
    {
        // Check transfer limits
        if ($license->transfer_count >= $license->max_transfers) {
            return false;
        }

        // Only license owner or admin can transfer
        return $user->id === $license->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can suspend the license.
     */
    public function suspend(User $user, License $license): bool
    {
        // Only admins can suspend
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can activate the license.
     */
    public function activate(User $user, License $license): bool
    {
        // Only admins can activate
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can reactivate the license.
     */
    public function reactivate(User $user, License $license): bool
    {
        // User can reactivate their own expired/cancelled licenses, admins can reactivate any
        return ($user->id === $license->user_id && in_array($license->status, ['expired', 'cancelled']))
            || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view license check logs.
     */
    public function viewLogs(User $user, License $license): bool
    {
        // User can view logs for their own licenses, admins can view all
        return $user->id === $license->user_id || $user->hasRole('admin');
    }
}
