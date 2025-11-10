<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserDeletionGuard;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    protected UserDeletionGuard $deletionGuard;

    public function __construct(UserDeletionGuard $deletionGuard)
    {
        $this->deletionGuard = $deletionGuard;
    }

    /**
     * Handle the User "deleting" event.
     *
     * This runs BEFORE the user is deleted, allowing us to prevent deletion.
     */
    public function deleting(User $user): bool
    {
        // Check if user can be safely deleted
        $canDelete = $this->deletionGuard->canDeleteUser($user);

        if (!$canDelete['can_delete']) {
            // Log the prevention
            Log::warning('User deletion prevented due to active licenses', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'active_licenses_count' => $canDelete['active_licenses_count'],
                'licenses' => $canDelete['licenses'],
            ]);

            // Store error message in session if available
            if (session()) {
                session()->flash('error', $canDelete['message'] . '. Please cancel or transfer licenses before deleting the user.');
            }

            // Return false to prevent deletion
            return false;
        }

        return true;
    }

    /**
     * Handle the User "deleted" event.
     *
     * This runs AFTER the user is successfully deleted.
     */
    public function deleted(User $user): void
    {
        Log::info('User deleted successfully', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);
    }
}
