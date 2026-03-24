<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all roles
        $adminRole = Role::where('name', 'admin')->first();
        $supervisorRole = Role::where('name', 'supervisor')->first();
        $technicianRole = Role::where('name', 'technician')->first();

        if (!$adminRole || !$supervisorRole || !$technicianRole) {
            $this->command->error('Roles not found! Please run RoleSeeder first.');
            return;
        }

        // ==============================================
        // ADMIN ROLE - Full Access to Everything
        // ==============================================
        $adminPermissions = Permission::all();
        $adminRole->syncPermissions($adminPermissions);
        $this->command->info('Admin role: ' . $adminPermissions->count() . ' permissions assigned (ALL)');

        // ==============================================
        // SUPERVISOR ROLE - Team Management + Operations
        // ==============================================
        $supervisorPermissions = [
            // Users - Can view and manage their team
            'view_users', 'edit_users', 'change_password_users',

            // Master Data - View only
            'view_partners', 'view_clients', 'view_vendors', 'view_sites', 'view_depots',
            'view_models', 'view_categories', 'view_charges', 'view_rate_cards',

            // Inventory - Full access via inventory-management module
            // Old inventory/stock permissions removed — now managed by inventory_management permissions

            // Procurement - View and create
            'view_quotations', 'create_quotations', 'edit_quotations', 'export_quotations',

            // Job Orders - Full team access
            'view_jobs', 'create_jobs', 'edit_jobs', 'assign_jobs', 'reassign_jobs',
            'start_jobs', 'complete_jobs', 'fail_jobs', 'cancel_jobs',
            'view_team_jobs', // Can see team jobs

            // Delivery Orders
            'view_delivery_orders', 'create_delivery_orders', 'edit_delivery_orders',
            'post_delivery_orders', 'print_delivery_orders',

            // Site Assets
            'view_site_assets', 'view_history_site_assets', 'export_site_assets',

            // Invoicing - Can create and view
            'view_invoices_ar', 'create_invoices_ar', 'edit_invoices_ar', 'export_invoices_ar',
            'view_invoices_ap',

            // Payments - View and create
            'view_payments', 'create_payments', 'export_payments',

            // Aging
            'view_aging', 'export_aging', 'send_reminders_aging',

            // Payouts - View team payouts
            'view_payouts', 'export_payouts',

            // Claims - Approve team claims
            'view_claims', 'create_claims', 'edit_claims', 'approve_claims', 'reject_claims', 'view_team_claims',

            // Reports
            'view_reports', 'export_reports',

            // Dashboards
            'view_supervisor_dashboards',

            // Activity Logs
            'view_activity_logs',

            // Notifications
            'view_notifications', 'create_notifications', 'send_notifications',

            // Old stock ledger/balance/valuation/reports permissions removed

            // Tickets - Create, edit, assign team, change status, comment
            'view_tickets', 'create_tickets', 'edit_tickets', 'assign_tickets',
            'change_status_tickets', 'add_comment_tickets', 'view_team_tickets',
        ];

        $supervisorRole->syncPermissions($supervisorPermissions);
        $this->command->info('Supervisor role: ' . count($supervisorPermissions) . ' permissions assigned');

        // ==============================================
        // TECHNICIAN ROLE - Field Operations Only
        // ==============================================
        $technicianPermissions = [
            // Users - View own profile only
            'view_users', // Limited to self in controller

            // Master Data - View only (needed for job execution)
            'view_clients', 'view_sites', 'view_models',

            // Inventory - View own stock via inventory-management module
            // Old inventory/stock permissions removed — now managed by inventory_management permissions

            // Job Orders - Own jobs only
            'view_jobs', 'start_jobs', 'complete_jobs', 'fail_jobs',
            'view_own_jobs', // Can only see assigned jobs

            // Delivery Orders - View related to own jobs
            'view_delivery_orders',

            // Site Assets - View only
            'view_site_assets',

            // Claims - Create and manage own claims
            'view_claims', 'create_claims', 'edit_claims', 'submit_claims',
            'view_own_claims', // Can only see own claims

            // Reports - View own performance
            'view_reports',

            // Dashboards
            'view_technician_dashboards',

            // Notifications
            'view_notifications',

            // Old stock ledger/balance/reports/returns permissions removed

            // Tickets - View own, change status (start/complete), comment
            'view_tickets', 'change_status_tickets', 'add_comment_tickets', 'view_own_tickets',
        ];

        $technicianRole->syncPermissions($technicianPermissions);
        $this->command->info('Technician role: ' . count($technicianPermissions) . ' permissions assigned');

        $this->command->info('✅ Role permissions assigned successfully!');
    }
}
