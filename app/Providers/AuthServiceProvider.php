<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\VendorType;
use App\Models\Claim;
use App\Models\JobCategory;
use App\Models\JobType;
use App\Models\InventoryItem;

// Policies
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use App\Policies\VendorTypePolicy;
use App\Policies\ClaimPolicy;
use App\Policies\JobCategoryPolicy;
use App\Policies\JobTypePolicy;
use App\Policies\InventoryItemPolicy;

// Removed: Partner, Client, Site, Depot, RateCard models & policies

/**
 * AuthServiceProvider - Registers policies, observers, and authorization gates.
 *
 * CHANGES: Removed view_team_inventory gate — Inventory is now Admin-only.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // ==========================================
        // USER MANAGEMENT
        // ==========================================
        User::class => UserPolicy::class,

        // ==========================================
        // REMOVED: Partner, Client, Site, Depot, RateCard policies
        // ==========================================

        Permission::class => PermissionPolicy::class,
        Role::class => RolePolicy::class,
        VendorType::class => VendorTypePolicy::class,
        Vendor::class => VendorPolicy::class,
        Claim::class => ClaimPolicy::class,
        JobType::class => JobTypePolicy::class,
        JobCategory::class => JobCategoryPolicy::class,

        // ==========================================
        // INVENTORY MANAGEMENT (Admin-Only)
        // ==========================================
        InventoryItem::class => InventoryItemPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerTeamGates();
        $this->registerInventoryGates();
        $this->registerSuperAdminBypass();
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        // Old InventorySerial observer removed — handled by inventory-management module
    }

    /**
     * Register team management authorization gates.
     *
     * KEY RULES:
     * - Internal supervisors: can view team, view team members, team-scoped data
     * - External supervisors: NO team, cannot view team members, own-scoped data
     * - Admin: can view everything
     * - Technicians can only assign to INTERNAL supervisors
     *
     * CHANGE: Removed view_team_inventory gate — Inventory is Admin-only now
     */
    protected function registerTeamGates(): void
    {
        // View all teams (admin only)
        Gate::define('viewAnyTeam', function (User $user) {
            return $user->hasRole('admin');
        });

        // View own team
        Gate::define('viewOwnTeam', function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }
            return $user->hasRole('supervisor');
        });

        // View specific team member
        Gate::define('viewTeamMember', function (User $user, User $member) {
            if ($user->hasRole('admin')) {
                return true;
            }

            if ($user->hasRole('supervisor')) {
                if ($user->isExternalSupervisor()) {
                    return false;
                }
                return $member->supervisor_id === $user->id;
            }

            return $user->id === $member->id;
        });

        // Assign technician to supervisor (admin only)
        Gate::define('assignTechnician', function (User $user) {
            return $user->hasRole('admin');
        });

        // Bulk assign technicians (admin only)
        Gate::define('bulkAssignTechnicians', function (User $user) {
            return $user->hasRole('admin');
        });

        // Remove technician from team (admin only)
        Gate::define('removeFromTeam', function (User $user, User $technician) {
            return $user->hasRole('admin') && $technician->hasRole('technician');
        });

        // Export team data
        Gate::define('exportTeam', function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }

            if ($user->hasRole('supervisor')) {
                return $user->isInternalSupervisor();
            }

            return false;
        });

        // REMOVED: view_team_inventory gate — Inventory is Admin-only now
    }

    /**
     * Register inventory-specific authorization gates.
     */
    protected function registerInventoryGates(): void
    {
        // Handled by InventoryItemPolicy (Admin-only) — no additional gates needed
    }

    /**
     * Register super admin bypass with security logging.
     */
    protected function registerSuperAdminBypass(): void
    {
        Gate::before(function (User $user, string $ability) {
            $superAdminEmail = config('app.super_admin_email');

            if (!$superAdminEmail) {
                return null;
            }

            if ($user->hasRole('admin') && $user->email === $superAdminEmail) {
                if (config('app.log_super_admin_access', true)) {
                    \Log::info('Super Admin Access', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'ability' => $ability,
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'url' => request()->fullUrl(),
                        'timestamp' => now()->toDateTimeString(),
                    ]);
                }
                return true;
            }

            return null;
        });
    }
}
