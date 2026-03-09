<?php

namespace App\Policies;

use App\Models\JobType;
use App\Models\User;

class JobTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_job_types');
    }

    public function view(User $user, ?JobType $jobType = null): bool
    {
        return $user->hasPermissionTo('view_job_types');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_job_types');
    }

    public function update(User $user, ?JobType $jobType = null): bool
    {
        return $user->hasPermissionTo('edit_job_types');
    }

    public function delete(User $user, JobType $jobType): bool
    {
        return $user->hasPermissionTo('delete_job_types');
    }

    public function restore(User $user, JobType $jobType): bool
    {
        return $user->hasPermissionTo('delete_job_types');
    }

    public function forceDelete(User $user, JobType $jobType): bool
    {
        return $user->hasRole('admin');
    }
}
