<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions by module
        $permissions = [
            // User Management
            'users' => ['view', 'create', 'edit', 'delete', 'restore', 'assign_roles', 'change_password'],

            /* Roles & Permissions Management */
            'roles' => ['view', 'create', 'edit', 'delete', 'assign_permissions'],

            'permissions' => ['view', 'create', 'edit', 'delete', 'manage', 'export'],

            // Master Data - Partners
            'partners' => ['view', 'create', 'edit', 'delete', 'restore', 'import', 'export'],

            // Master Data - Clients
            'clients' => ['view', 'create', 'edit', 'delete', 'restore', 'import', 'export', 'manage_contacts'],

            // Master Data - Vendors
            'vendors' => ['view', 'create', 'edit', 'delete', 'restore', 'import', 'export'],

            // Master Data - Sites
            'sites' => ['view', 'create', 'edit', 'delete', 'restore', 'view_assets'],

            // Master Data - Depots
            'depots' => ['view', 'create', 'edit', 'delete'],

            // Master Data - Terminal Models
            'models' => ['view', 'create', 'edit', 'delete'],

            // Master Data - Categories
            'categories' => ['view', 'create', 'edit', 'delete'],

            // Master Data - Charge Catalog
            'charges' => ['view', 'create', 'edit', 'delete'],

            // Master Data - Rate Cards
            'rate_cards' => ['view', 'create', 'edit', 'delete'],

            // Inventory permissions removed — replaced by inventory_management module permissions
            // Old modules removed: inventory, stock_issues, stock_transfers, stock_returns,
            // stock_adjustments, stock_ledger, stock_balance, stock_valuation, stock_reports

            // Procurement - Quotations
            'quotations' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'send', 'convert_to_po', 'export'],

            // Job Orders
            'jobs' => ['view', 'create', 'edit', 'delete', 'assign', 'reassign', 'start', 'complete', 'fail', 'cancel', 'view_all', 'view_team', 'view_own'],

            // Delivery Orders
            'delivery_orders' => ['view', 'create', 'edit', 'post', 'cancel', 'print'],

            // Site Assets
            'site_assets' => ['view', 'create', 'edit', 'view_history', 'export'],

            // Invoicing - AR
            'invoices_ar' => ['view', 'create', 'edit', 'delete', 'approve', 'send', 'void', 'export'],

            // Invoicing - AP
            'invoices_ap' => ['view', 'create', 'edit', 'delete', 'approve', 'post'],

            // Payments
            'payments' => ['view', 'create', 'edit', 'delete', 'approve', 'export'],

            // Credit Notes
            'credit_notes' => ['view', 'create', 'approve', 'apply'],

            // Aging
            'aging' => ['view', 'export', 'send_reminders'],

            // Payouts
            'payouts' => ['view', 'create', 'approve', 'reject', 'mark_paid', 'export'],

            // Claims
            'claims' => ['view', 'create', 'edit', 'submit', 'verify', 'approve', 'reject', 'mark_paid', 'bulk_pay', 'export', 'view_all', 'view_team', 'view_own'],

            // Reports
            'reports' => ['view', 'export', 'schedule'],

            // Dashboards
            'dashboards' => ['view_admin', 'view_supervisor', 'view_technician', 'view_executive'],

            // Settings
            'settings' => ['view', 'edit', 'manage_system', 'manage_number_series', 'manage_workflows'],

            // Activity Logs
            'activity_logs' => ['view', 'export'],

            // Notifications
            'notifications' => ['view', 'create', 'send'],

            // Master Data - Job Types
            'job_types' => ['view', 'create', 'edit', 'delete'],

            // Job Categories
            'job_categories' => ['view', 'create', 'edit', 'delete'],

            // Tickets Management
            'tickets' => ['view', 'create', 'edit', 'delete', 'assign', 'change_status', 'add_comment', 'view_all', 'view_team', 'view_own'],

            // Master Data - Vendor Types
            'vendor_types' => ['view', 'create', 'edit', 'delete'],
        ];

        // Create permissions
        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::create([
                    'name' => "{$action}_{$module}",
                    'guard_name' => 'web',
                    'module' => $module,
                    'description' => ucfirst(str_replace('_', ' ', $action)) . ' ' . ucfirst(str_replace('_', ' ', $module)),
                ]);
            }
        }

        $this->command->info('Permissions created successfully!');
        $this->command->info('Total permissions created: ' . Permission::count());
    }
}
