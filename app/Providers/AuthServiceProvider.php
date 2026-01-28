<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * AuthServiceProvider - Registers policies and gates.
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Team Gates
        Gate::define('viewAnyTeam', fn(User $u) => $u->hasRole('admin'));
        Gate::define('viewOwnTeam', fn(User $u) => $u->hasRole(['admin', 'supervisor']));
        Gate::define('viewTeamMember', fn(User $u, User $m) => $u->hasRole('admin') || ($u->hasRole('supervisor') && $m->supervisor_id === $u->id) || $u->id === $m->id);
        Gate::define('assignTechnician', fn(User $u) => $u->hasRole('admin'));
        Gate::define('bulkAssignTechnicians', fn(User $u) => $u->hasRole('admin'));
        Gate::define('removeFromTeam', fn(User $u, User $t) => $u->hasRole('admin') && $t->hasRole('technician'));
        Gate::define('exportTeam', fn(User $u) => $u->hasRole(['admin', 'supervisor']));

        // Super admin bypass
        Gate::before(function (User $user, string $ability) {
            if ($user->email === config('app.super_admin_email') && $user->hasRole('admin')) return true;
            return null;
        });
    }
}
