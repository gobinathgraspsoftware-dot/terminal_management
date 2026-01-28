<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    /**
     * Permission groups/modules for TMS.
     * Matches the existing database structure.
     */
    protected array $permissionGroups = [
        // User & Access Management
        'users' => 'User Management',
        
        // Master Data
        'partners' => 'Partner Management',
        'clients' => 'Client Management',
        'vendors' => 'Vendor Management',
        'sites' => 'Site Management',
        'depots' => 'Depot Management',
        'models' => 'Terminal Models',
        'categories' => 'Terminal Categories',
        'charges' => 'Charge Catalog',
        'rate_cards' => 'Rate Cards',
        
        // Inventory Management
        'inventory' => 'Inventory/Serial Numbers',
        'stock_issues' => 'Stock Issues',
        'stock_transfers' => 'Stock Transfers',
        'stock_adjustments' => 'Stock Adjustments',
        
        // Procurement
        'quotations' => 'Quotations',
        'purchase_orders' => 'Purchase Orders',
        'grns' => 'Goods Receipt Notes',
        
        // Operations
        'jobs' => 'Job Orders',
        'delivery_orders' => 'Delivery Orders',
        'site_assets' => 'Site Assets',
        
        // Financial - AR
        'invoices_ar' => 'Invoices (AR)',
        'invoices_ap' => 'Invoices (AP)',
        'payments' => 'Payments',
        'credit_notes' => 'Credit Notes',
        'aging' => 'Aging Analysis',
        
        // Payroll & Claims
        'payouts' => 'Payouts',
        'claims' => 'Expense Claims',
        
        // Reports & Analytics
        'reports' => 'Reports',
        'dashboards' => 'Dashboards',
        
        // System
        'settings' => 'System Settings',
        'activity_logs' => 'Activity Logs',
        'notifications' => 'Notifications',
    ];

    /**
     * Known action prefixes for parsing permission names.
     * Order matters - longer prefixes first to avoid partial matches.
     */
    protected array $knownActions = [
        'manage_contacts',
        'manage_number_series',
        'manage_workflows',
        'manage_system',
        'change_password',
        'assign_roles',
        'view_history',
        'view_assets',
        'convert_to_po',
        'send_reminders',
        'view_executive',
        'view_supervisor',
        'view_technician',
        'view_admin',
        'view_team',
        'view_all',
        'view_own',
        'mark_paid',
        'restore',
        'approve',
        'reject',
        'cancel',
        'delete',
        'create',
        'import',
        'export',
        'assign',
        'reassign',
        'receive',
        'dispatch',
        'adjust',
        'transfer',
        'complete',
        'convert',
        'submit',
        'start',
        'close',
        'print',
        'apply',
        'send',
        'post',
        'edit',
        'view',
        'void',
        'fail',
        'schedule',
        'manage',
    ];

    /**
     * Get DataTables data for permissions listing.
     */
    public function getDatatableData(Request $request): JsonResponse
    {
        $query = Permission::withCount('roles');

        // Search filter
        if ($search = $request->input('search.value')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%");
            });
        }

        // Group/Module filter
        if ($group = $request->input('group')) {
            $query->where('module', $group);
        }

        // Get total count before pagination
        $totalRecords = Permission::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        
        $columns = ['id', 'name', 'module', 'roles_count', 'description', 'created_at'];
        $sortColumn = $columns[$orderColumn] ?? 'name';
        
        $query->orderBy($sortColumn, $orderDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $permissions = $query->skip($start)->take($length)->get();

        $data = $permissions->map(function ($permission) {
            $group = $permission->module ?? $this->getPermissionGroup($permission->name);
            
            return [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->description ?? $this->formatDisplayName($permission->name),
                'group' => $group,
                'group_label' => $this->permissionGroups[$group] ?? ucfirst(str_replace('_', ' ', $group)),
                'action' => $this->getPermissionAction($permission->name),
                'roles_count' => $permission->roles_count,
                'guard_name' => $permission->guard_name,
                'created_at' => $permission->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Get permissions grouped by module/feature.
     * Uses the 'module' column from database if available.
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $grouped = [];

        foreach ($permissions as $permission) {
            // Use the module column from database, fallback to parsing
            $group = $permission->module ?? $this->getPermissionGroup($permission->name);
            $groupLabel = $this->permissionGroups[$group] ?? ucfirst(str_replace('_', ' ', $group));

            if (!isset($grouped[$group])) {
                $grouped[$group] = [
                    'label' => $groupLabel,
                    'permissions' => []
                ];
            }

            $grouped[$group]['permissions'][] = [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->description ?? $this->formatDisplayName($permission->name),
                'action' => $this->getPermissionAction($permission->name),
            ];
        }

        // Sort groups alphabetically by label
        uasort($grouped, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return $grouped;
    }

    /**
     * Get the group/module name from a permission name.
     * Handles underscore notation: action_module (e.g., "view_users" -> "users")
     */
    public function getPermissionGroup(string $permissionName): string
    {
        // First, try to find a matching action prefix
        foreach ($this->knownActions as $action) {
            $prefix = $action . '_';
            if (strpos($permissionName, $prefix) === 0) {
                // Remove the action prefix to get the module
                return substr($permissionName, strlen($prefix));
            }
        }

        // Fallback: take everything after the first underscore
        $parts = explode('_', $permissionName, 2);
        return $parts[1] ?? $permissionName;
    }

    /**
     * Get the action from a permission name.
     * Handles underscore notation: action_module (e.g., "view_users" -> "view")
     */
    public function getPermissionAction(string $permissionName): string
    {
        // Try to find a matching action prefix
        foreach ($this->knownActions as $action) {
            $prefix = $action . '_';
            if (strpos($permissionName, $prefix) === 0) {
                return $action;
            }
        }

        // Fallback: take the first word
        $parts = explode('_', $permissionName);
        return $parts[0] ?? 'access';
    }

    /**
     * Format permission name for display.
     */
    protected function formatDisplayName(string $name): string
    {
        // Replace underscores with spaces, then title case
        $formatted = str_replace('_', ' ', $name);
        return ucwords($formatted);
    }

    /**
     * Get permission statistics.
     */
    public function getStatistics(): array
    {
        $totalPermissions = Permission::count();
        $totalRoles = Role::count();
        
        $permissionsInUse = Permission::has('roles')->count();
        $unusedPermissions = $totalPermissions - $permissionsInUse;
        
        // Count unique modules
        $moduleCount = Permission::distinct('module')->count('module');

        return [
            'total_permissions' => $totalPermissions,
            'total_roles' => $totalRoles,
            'permissions_in_use' => $permissionsInUse,
            'unused_permissions' => $unusedPermissions,
            'permission_groups' => $moduleCount ?: count($this->getGroupedPermissions()),
        ];
    }

    /**
     * Get detailed permission statistics.
     */
    public function getDetailedStatistics(): array
    {
        $stats = $this->getStatistics();

        // Add per-group statistics
        $groupStats = [];
        foreach ($this->getGroupedPermissions() as $group => $data) {
            $groupStats[$group] = [
                'label' => $data['label'],
                'count' => count($data['permissions']),
            ];
        }

        $stats['groups'] = $groupStats;

        // Most assigned permissions
        $stats['most_assigned'] = Permission::withCount('roles')
            ->orderByDesc('roles_count')
            ->limit(5)
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'roles_count' => $permission->roles_count
                ];
            });

        // Least assigned permissions
        $stats['least_assigned'] = Permission::withCount('roles')
            ->orderBy('roles_count')
            ->limit(5)
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'roles_count' => $permission->roles_count
                ];
            });

        return $stats;
    }

    /**
     * Get all available permission groups.
     */
    public function getPermissionGroupLabels(): array
    {
        return $this->permissionGroups;
    }

    /**
     * Get unique modules from database.
     */
    public function getModulesFromDatabase(): array
    {
        return Permission::distinct()
            ->whereNotNull('module')
            ->pluck('module')
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Search permissions by name.
     */
    public function searchPermissions(string $query, int $limit = 20): Collection
    {
        return Permission::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('module')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get permissions by group/module.
     */
    public function getPermissionsByGroup(string $group): Collection
    {
        return Permission::where('module', $group)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get action badge color based on action type.
     */
    public function getActionBadgeClass(string $action): string
    {
        $colors = [
            'view' => 'info',
            'view_all' => 'info',
            'view_team' => 'info',
            'view_own' => 'info',
            'view_history' => 'info',
            'view_assets' => 'info',
            'view_admin' => 'info',
            'view_supervisor' => 'info',
            'view_technician' => 'info',
            'view_executive' => 'info',
            'create' => 'success',
            'edit' => 'warning',
            'delete' => 'danger',
            'restore' => 'secondary',
            'approve' => 'primary',
            'reject' => 'danger',
            'submit' => 'primary',
            'cancel' => 'danger',
            'void' => 'danger',
            'export' => 'secondary',
            'import' => 'secondary',
            'print' => 'secondary',
            'send' => 'primary',
            'assign' => 'primary',
            'reassign' => 'warning',
            'transfer' => 'warning',
            'adjust' => 'warning',
            'receive' => 'success',
            'dispatch' => 'info',
            'post' => 'success',
            'complete' => 'success',
            'start' => 'info',
            'fail' => 'danger',
            'close' => 'secondary',
            'convert' => 'primary',
            'convert_to_po' => 'primary',
            'apply' => 'success',
            'mark_paid' => 'success',
            'manage' => 'dark',
            'manage_system' => 'dark',
            'manage_workflows' => 'dark',
            'manage_number_series' => 'dark',
            'manage_contacts' => 'primary',
            'assign_roles' => 'primary',
            'change_password' => 'warning',
            'send_reminders' => 'info',
            'schedule' => 'info',
        ];

        return $colors[$action] ?? 'secondary';
    }
}
