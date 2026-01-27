<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function creating(User $user): void
    {
        // Auto-generate employee ID if not set
        if (empty($user->employee_id)) {
            $user->employee_id = User::generateEmployeeId();
        }

        // Set default status if not provided
        if (empty($user->status)) {
            $user->status = 'active';
        }

        // Log the creation attempt
        Log::info('User being created', [
            'employee_id' => $user->employee_id,
            'email' => $user->email,
            'name' => $user->name,
        ]);
    }

    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user): void
    {
        // Log successful creation
        activity()
            ->performedOn($user)
            ->withProperties([
                'employee_id' => $user->employee_id,
                'email' => $user->email,
                'name' => $user->name,
                'supervisor_id' => $user->supervisor_id,
            ])
            ->log('User account created');

        // Send welcome notification (if needed)
        // You can implement this based on your notification system
        Log::info('User created successfully', [
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'email' => $user->email,
        ]);
    }

    /**
     * Handle the User "updating" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updating(User $user): void
    {
        // Get changed attributes
        $changes = $user->getDirty();

        // Log important changes
        if (isset($changes['status'])) {
            Log::warning('User status being changed', [
                'user_id' => $user->id,
                'old_status' => $user->getOriginal('status'),
                'new_status' => $changes['status'],
            ]);
        }

        if (isset($changes['supervisor_id'])) {
            Log::info('User supervisor being changed', [
                'user_id' => $user->id,
                'old_supervisor_id' => $user->getOriginal('supervisor_id'),
                'new_supervisor_id' => $changes['supervisor_id'],
            ]);
        }

        if (isset($changes['email'])) {
            Log::warning('User email being changed', [
                'user_id' => $user->id,
                'old_email' => $user->getOriginal('email'),
                'new_email' => $changes['email'],
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Get what changed
        $changes = $user->getChanges();
        
        // Skip if only timestamps changed
        unset($changes['updated_at']);
        
        if (empty($changes)) {
            return;
        }

        // Log the update with changes
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'attributes' => $changes,
                'old' => $user->getOriginal(),
            ])
            ->log('User account updated');

        // Handle specific change actions
        if (isset($changes['status'])) {
            $this->handleStatusChange($user, $user->getOriginal('status'), $changes['status']);
        }

        if (isset($changes['supervisor_id'])) {
            $this->handleSupervisorChange($user, $user->getOriginal('supervisor_id'), $changes['supervisor_id']);
        }

        Log::info('User updated', [
            'user_id' => $user->id,
            'changes' => array_keys($changes),
        ]);
    }

    /**
     * Handle the User "deleting" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleting(User $user): void
    {
        Log::warning('User being deleted', [
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'email' => $user->email,
            'is_soft_delete' => $user->isForceDeleting() ? 'no' : 'yes',
        ]);

        // Check for dependencies before soft delete
        if (!$user->isForceDeleting()) {
            // Check if user has pending jobs
            $pendingJobs = $user->assignedJobs()
                               ->whereIn('status', ['pending_assignment', 'assigned', 'in_progress'])
                               ->count();

            if ($pendingJobs > 0) {
                Log::error('Cannot delete user with pending jobs', [
                    'user_id' => $user->id,
                    'pending_jobs' => $pendingJobs,
                ]);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user): void
    {
        // Log the deletion
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'employee_id' => $user->employee_id,
                'email' => $user->email,
                'name' => $user->name,
                'deleted_at' => $user->deleted_at,
            ])
            ->log('User account deleted');

        Log::info('User deleted', [
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
        ]);
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user): void
    {
        // Log the restoration
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'employee_id' => $user->employee_id,
                'email' => $user->email,
            ])
            ->log('User account restored');

        Log::info('User restored', [
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user): void
    {
        Log::critical('User permanently deleted', [
            'user_id' => $user->id,
            'employee_id' => $user->employee_id,
            'email' => $user->email,
        ]);
    }

    // ==========================================
    // CUSTOM EVENT HANDLERS
    // ==========================================

    /**
     * Handle status change.
     */
    protected function handleStatusChange(User $user, $oldStatus, $newStatus): void
    {
        // Log status change
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ])
            ->log("User status changed from {$oldStatus} to {$newStatus}");

        // Handle specific status changes
        if ($newStatus === 'suspended') {
            // Revoke all tokens when suspended
            $user->tokens()->delete();
            
            Log::warning('User suspended - tokens revoked', [
                'user_id' => $user->id,
            ]);
        }

        if ($newStatus === 'inactive') {
            // Revoke all tokens when made inactive
            $user->tokens()->delete();
            
            Log::info('User deactivated - tokens revoked', [
                'user_id' => $user->id,
            ]);
        }

        if ($newStatus === 'active' && $oldStatus !== 'active') {
            Log::info('User activated', [
                'user_id' => $user->id,
            ]);
        }
    }

    /**
     * Handle supervisor change.
     */
    protected function handleSupervisorChange(User $user, $oldSupervisorId, $newSupervisorId): void
    {
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_supervisor_id' => $oldSupervisorId,
                'new_supervisor_id' => $newSupervisorId,
            ])
            ->log('User supervisor changed');

        if ($newSupervisorId) {
            $supervisor = User::find($newSupervisorId);
            if ($supervisor) {
                Log::info('User assigned to supervisor', [
                    'user_id' => $user->id,
                    'supervisor_id' => $supervisor->id,
                    'supervisor_name' => $supervisor->name,
                ]);
            }
        } else {
            Log::info('User made independent (supervisor removed)', [
                'user_id' => $user->id,
            ]);
        }
    }
}