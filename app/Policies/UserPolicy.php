<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * UserPolicy - Authorization for user management.
 */
class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'supervisor']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('supervisor')) return $model->supervisor_id === $user->id || $user->id === $model->id;
        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('admin')) return true;
        if ($user->hasRole('supervisor')) return $model->supervisor_id === $user->id;
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('admin') && $user->id !== $model->id;
    }

    public function assignRole(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function assignSupervisor(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->email === config('app.super_admin_email') && $user->hasRole('admin')) return true;
        return null;
    }
}
