<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Models
use App\Models\User;
use App\Models\ChargeCatalog;
use App\Models\Depot;
use App\Models\InventorySerial;
use App\Models\Partner;
use App\Models\RateCard;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockIssue;
use App\Models\StockLedger;
use App\Models\TerminalCategory;
use App\Models\TerminalModel;
use App\Models\Vendor;
use App\Models\Quotation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Policies
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use App\Policies\ChargeCatalogPolicy;
use App\Policies\DepotPolicy;
use App\Policies\InventorySerialPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\RateCardPolicy;
use App\Policies\SitePolicy;
use App\Policies\StockBalancePolicy;
use App\Policies\StockIssuePolicy;
use App\Policies\StockLedgerPolicy;
use App\Policies\TerminalCategoryPolicy;
use App\Policies\TerminalModelPolicy;
use App\Policies\VendorPolicy;
use App\Policies\QuotationPolicy;
use App\Models\VendorType;
use App\Policies\VendorTypePolicy;
use App\Models\Claim;
use App\Policies\ClaimPolicy;
use App\Models\JobCategory;
use App\Models\JobType;
use App\Policies\JobCategoryPolicy;
use App\Policies\JobTypePolicy;

// Observers
use App\Observers\InventorySerialObserver;

/**
 * AuthServiceProvider - Registers policies, observers, and authorization gates.
 *
 * This provider is responsible for:
 * - Registering model-policy mappings for authorization
 * - Registering model observers for business logic
 * - Defining custom authorization gates
 * - Implementing super admin bypass with security logging
 *
 * SUPERVISOR TYPES:
 * - Internal: Has technician team, can view team members, team-scoped data
 * - External: No technician team, independent operator, own-scoped data
 *
 * @package App\Providers
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
        // MASTER DATA
        // ==========================================
        Partner::class => PartnerPolicy::class,
        Vendor::class => VendorPolicy::class,
        Site::class => SitePolicy::class,
        Depot::class => DepotPolicy::class,

        // ==========================================
        // TERMINAL MODELS & CATEGORIES
        // ==========================================
        TerminalModel::class => TerminalModelPolicy::class,
        TerminalCategory::class => TerminalCategoryPolicy::class,

        // ==========================================
        // PRICING & CHARGES
        // ==========================================
        ChargeCatalog::class => ChargeCatalogPolicy::class,
        RateCard::class => RateCardPolicy::class,

        // ==========================================
        // INVENTORY MANAGEMENT
        // ==========================================
        InventorySerial::class => InventorySerialPolicy::class,
        StockBalance::class => StockBalancePolicy::class,
        StockLedger::class => StockLedgerPolicy::class,
        StockIssue::class => StockIssuePolicy::class,

        Permission::class => PermissionPolicy::class,
        Role::class => RolePolicy::class,
        Quotation::class => QuotationPolicy::class,
        VendorType::class => VendorTypePolicy::class,
        Claim::class => ClaimPolicy::class,
        JobType::class => JobTypePolicy::class,
        JobCategory::class => JobCategoryPolicy::class,
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
        InventorySerial::observe(InventorySerialObserver::class);
    }

    /**
     * Register team management authorization gates.
     *
     * KEY RULES:
     * - Internal supervisors: can view team, view team members, team-scoped data
     * - External supervisors: NO team, cannot view team members, own-scoped data
     * - Admin: can view everything
     * - Technicians can only assign to INTERNAL supervisors
     */
    protected function registerTeamGates(): void
    {
        // View all teams (admin only)
        Gate::define('viewAnyTeam', function (User $user) {
            return $user->hasRole('admin');
        });

        // View own team
        // Admin: always
        // Internal Supervisor: yes (has team)
        // External Supervisor: can see the teams index (shows own stats), but no team members
        Gate::define('viewOwnTeam', function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }

            // Both internal and external can access the teams index page,
            // but the controller/view will differentiate what they see
            return $user->hasRole('supervisor');
        });

        // View specific team member
        // Admin: any member
        // Internal Supervisor: own team members only
        // External Supervisor: BLOCKED — they have no team members
        // Technician: self only
        Gate::define('viewTeamMember', function (User $user, User $member) {
            if ($user->hasRole('admin')) {
                return true;
            }

            if ($user->hasRole('supervisor')) {
                // External supervisors cannot view team members (they have none)
                if ($user->isExternalSupervisor()) {
                    return false;
                }
                // Internal supervisor can view their own team members
                return $member->supervisor_id === $user->id;
            }

            // Users can view themselves
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

        // Remove technician from team (admin only, target must be technician)
        Gate::define('removeFromTeam', function (User $user, User $technician) {
            return $user->hasRole('admin') && $technician->hasRole('technician');
        });

        // Export team data
        // Admin: always
        // Internal Supervisor: yes (has team data to export)
        // External Supervisor: no (no team data)
        Gate::define('exportTeam', function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }

            if ($user->hasRole('supervisor')) {
                return $user->isInternalSupervisor();
            }

            return false;
        });

        // View team inventory (supervisor stock balance/reports)
        // Internal Supervisor: can see team technicians' stock
        // External Supervisor: no team, no team inventory
        Gate::define('view_team_inventory', function (User $user) {
            if ($user->hasRole('admin')) {
                return true;
            }

            if ($user->hasRole('supervisor')) {
                return $user->isInternalSupervisor();
            }

            return false;
        });
    }

    /**
     * Register inventory-specific authorization gates.
     */
    protected function registerInventoryGates(): void
    {
        Gate::define('serial-lookup', function (User $user) {
            return $user->can('view_inventory');
        });

        Gate::define('bulk-import-serials', function (User $user) {
            return $user->can('bulk_import_inventory');
        });

        Gate::define('bulk-update-serials', function (User $user) {
            return $user->can('bulk_update_inventory');
        });

        Gate::define('bulk-transfer-serials', function (User $user) {
            return $user->can('bulk_transfer_inventory');
        });
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
