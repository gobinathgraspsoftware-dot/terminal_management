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
 * @package App\Providers
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * These policies control access to models throughout the application.
     * Each model is mapped to its corresponding policy class.
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
    ];

    /**
     * Register any authentication / authorization services.
     *
     * This method is called during the application boot process.
     * It registers all policies, observers, and custom gates.
     *
     * @return void
     */
    public function boot(): void
    {
        // Register all model policies
        $this->registerPolicies();

        // Register model observers
        $this->registerObservers();

        // Register team management gates
        $this->registerTeamGates();

        // Register inventory-specific gates
        $this->registerInventoryGates();

        // Register super admin bypass (with security logging)
        $this->registerSuperAdminBypass();
    }

    /**
     * Register model observers.
     *
     * Observers listen to model events and perform actions automatically.
     * Currently registered: InventorySerialObserver (creates stock ledger entries)
     *
     * @return void
     */
    protected function registerObservers(): void
    {
        // Inventory Serial Observer - Automatically creates stock ledger entries
        // when serials are created, updated, or deleted
        InventorySerial::observe(InventorySerialObserver::class);
    }

    /**
     * Register team management authorization gates.
     *
     * These gates control access to team-related functionality:
     * - Viewing teams and team members
     * - Assigning technicians to supervisors
     * - Managing team structure
     * - Exporting team data
     *
     * @return void
     */
    protected function registerTeamGates(): void
    {
        // View all teams (admin only)
        Gate::define('viewAnyTeam', function (User $user) {
            return $user->hasRole('admin');
        });

        // View own team (admin and supervisor)
        Gate::define('viewOwnTeam', function (User $user) {
            return $user->hasRole(['admin', 'supervisor']);
        });

        // View specific team member
        Gate::define('viewTeamMember', function (User $user, User $member) {
            // Admin can view anyone
            if ($user->hasRole('admin')) {
                return true;
            }

            // Supervisor can view their team members
            if ($user->hasRole('supervisor') && $member->supervisor_id === $user->id) {
                return true;
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

        // Export team data (admin and supervisor)
        Gate::define('exportTeam', function (User $user) {
            return $user->hasRole(['admin', 'supervisor']);
        });
    }

    /**
     * Register inventory-specific authorization gates.
     *
     * These gates provide shortcuts for common inventory operations:
     * - Serial number lookups
     * - Bulk operations (import, update, transfer)
     *
     * @return void
     */
    protected function registerInventoryGates(): void
    {
        // Serial lookup - Quick access for inventory searches
        Gate::define('serial-lookup', function (User $user) {
            return $user->can('view_inventory');
        });

        // Bulk import serials
        Gate::define('bulk-import-serials', function (User $user) {
            return $user->can('bulk_import_inventory');
        });

        // Bulk update serials
        Gate::define('bulk-update-serials', function (User $user) {
            return $user->can('bulk_update_inventory');
        });

        // Bulk transfer serials
        Gate::define('bulk-transfer-serials', function (User $user) {
            return $user->can('bulk_transfer_inventory');
        });
    }

    /**
     * Register super admin bypass with security logging.
     *
     * SECURITY WARNING:
     * - Super admin bypasses ALL authorization checks
     * - Use ONLY for emergency access and system maintenance
     * - All super admin access is logged for security audit
     *
     * Configuration:
     * - Set APP_SUPER_ADMIN_EMAIL in .env
     * - Set LOG_SUPER_ADMIN_ACCESS=true to enable logging
     *
     * Requirements:
     * - User must have 'admin' role
     * - User email must match APP_SUPER_ADMIN_EMAIL exactly
     *
     * @return void
     */
    protected function registerSuperAdminBypass(): void
    {
        Gate::before(function (User $user, string $ability) {
            // Get super admin email from config
            $superAdminEmail = config('app.super_admin_email');

            // If not configured, no bypass
            if (!$superAdminEmail) {
                return null;
            }

            // Check if user is super admin (must be admin role + matching email)
            if ($user->hasRole('admin') && $user->email === $superAdminEmail) {

                // Log super admin access for security audit
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

                // Grant access (bypass all other checks)
                return true;
            }

            // Continue normal authorization
            return null;
        });
    }
}
