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

            // Inventory - Serial Numbers
            'inventory' => ['view', 'create', 'edit', 'delete', 'adjust', 'transfer', 'view_history', 'export', 'bulk_import', 'bulk_update', 'bulk_transfer', 'print_labels',],

            // Inventory - Stock Issues
            'stock_issues' => ['view', 'create', 'edit', 'post', 'cancel'],

            // Inventory - Stock Transfers
            'stock_transfers' => ['view', 'create', 'edit', 'approve', 'dispatch', 'receive', 'cancel'],

            // Inventory - Stock Returns (NEW)
            'stock_returns' => ['view', 'create', 'edit', 'post', 'cancel', 'view_technician_inventory'],

            // Inventory - Stock Adjustments
            'stock_adjustments' => ['view', 'create', 'approve', 'reject', 'post'],

            // Inventory - Stock Ledger
            'stock_ledger' => ['view', 'create', 'reverse', 'export'],

            // Inventory - Stock Balance
            'stock_balance' => ['view', 'recalculate', 'reserve', 'release_reservation', 'view_alerts', 'export'],

            /* Inventory - Stock Valuation */
            'stock_valuation' => ['view', 'view_detailed', 'view_movement_value', 'view_aging', 'export' ],

            /* stock reports */
            'stock_reports' => ['view', 'view_movement_report', 'view_stock_card', 'view_summary_report', 'export', 'print_stock_card'],

            // Procurement - Quotations
            'quotations' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'send', 'convert_to_po', 'export'],

            // Procurement - Purchase Orders
            'purchase_orders' => ['view', 'create', 'edit', 'delete', 'approve', 'reject', 'send', 'close', 'cancel'],

            // Procurement - GRN
            'grns' => ['view', 'create', 'edit', 'post', 'cancel', 'print', 'export', 'view_reports', 'export_reports'],

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
            'claims' => ['view', 'create', 'edit', 'submit', 'approve', 'reject', 'mark_paid', 'view_all', 'view_team', 'view_own'],

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
