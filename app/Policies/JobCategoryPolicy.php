<?php

namespace App\Policies;

use App\Models\JobCategory;
use App\Models\User;

class JobCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_job_categories');
    }

    public function view(User $user, ?JobCategory $jobCategory = null): bool
    {
        return $user->hasPermissionTo('view_job_categories');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_job_categories');
    }

    public function update(User $user, ?JobCategory $jobCategory = null): bool
    {
        return $user->hasPermissionTo('edit_job_categories');
    }

    public function delete(User $user, JobCategory $jobCategory): bool
    {
        return $user->hasPermissionTo('delete_job_categories');
    }

    public function restore(User $user, JobCategory $jobCategory): bool
    {
        return $user->hasPermissionTo('delete_job_categories');
    }

    public function forceDelete(User $user, JobCategory $jobCategory): bool
    {
        return $user->hasRole('admin');
    }
}
