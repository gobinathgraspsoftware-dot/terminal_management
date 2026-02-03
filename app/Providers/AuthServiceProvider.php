<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use App\Models\InventorySerial;
use App\Policies\InventorySerialPolicy;
use App\Observers\InventorySerialObserver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\RateCard;
use App\Policies\RateCardPolicy;

/**
 * AuthServiceProvider - Registers policies and gates.
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        RateCard::class => RateCardPolicy::class,
        InventorySerial::class => InventorySerialPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        /* Register Observers */
        InventorySerial::observe(InventorySerialObserver::class);

        // Team Gates
        Gate::define('viewAnyTeam', fn(User $u) => $u->hasRole('admin'));
        Gate::define('viewOwnTeam', fn(User $u) => $u->hasRole(['admin', 'supervisor']));
        Gate::define('viewTeamMember', fn(User $u, User $m) => $u->hasRole('admin') || ($u->hasRole('supervisor') && $m->supervisor_id === $u->id) || $u->id === $m->id);
        Gate::define('assignTechnician', fn(User $u) => $u->hasRole('admin'));
        Gate::define('bulkAssignTechnicians', fn(User $u) => $u->hasRole('admin'));
        Gate::define('removeFromTeam', fn(User $u, User $t) => $u->hasRole('admin') && $t->hasRole('technician'));
        Gate::define('exportTeam', fn(User $u) => $u->hasRole(['admin', 'supervisor']));

        /* Inventory Serial Gates (optional shortcuts) */
        Gate::define('serial-lookup', fn(User $u) => $u->can('view_inventory'));

        // Super admin bypass
        Gate::before(function (User $user, string $ability) {
            if ($user->email === config('app.super_admin_email') && $user->hasRole('admin')) return true;
            return null;
        });
    }
}
