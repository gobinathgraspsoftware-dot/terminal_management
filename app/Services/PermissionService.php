<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    /**
     * Get all permissions data for DataTables (returns array, not response).
     */
    public function getPermissionsData(): array
    {
        try {
            $permissions = Permission::with('roles')
                ->select(['id', 'name', 'guard_name', 'description', 'module', 'created_at', 'updated_at'])
                ->get()
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $this->getDisplayName($permission->name),
                        'group' => $this->getPermissionGroup($permission->name),
                        'group_label' => $this->getGroupLabel($permission->name),
                        'action' => $this->getPermissionAction($permission->name),
                        'guard_name' => $permission->guard_name,
                        'description' => $permission->description,
                        'module' => $permission->module,
                        'roles_count' => $permission->roles->count(),
                        'roles' => $permission->roles->pluck('name')->toArray(),
                        'created_at' => $permission->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $permission->updated_at->format('Y-m-d H:i:s'),
                    ];
                })
                ->toArray();

            Log::info('PermissionService: Retrieved permissions', ['count' => count($permissions)]);

            return $permissions;
        } catch (\Exception $e) {
            Log::error('PermissionService: Error retrieving permissions', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get display name from permission name.
     */
    public function getDisplayName(string $permissionName): string
    {
        return ucwords(str_replace('_', ' ', $permissionName));
    }

    /**
     * Get group label for display.
     */
    public function getGroupLabel(string $permissionName): string
    {
        $group = $this->getPermissionGroup($permissionName);
        return ucwords(str_replace('_', ' ', $group));
    }

    /**
     * Create a new permission.
     */
    public function create(array $data): Permission
    {
        DB::beginTransaction();
        try {
            $permissionData = [
                'name' => strtolower($data['name']),
                'guard_name' => $data['guard_name'] ?? 'web',
            ];

            if (isset($data['description'])) {
                $permissionData['description'] = $data['description'];
            }

            if (isset($data['module'])) {
                $permissionData['module'] = strtolower($data['module']);
            }

            $permission = Permission::create($permissionData);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return $permission;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing permission.
     */
    public function update(Permission $permission, array $data): Permission
    {
        DB::beginTransaction();
        try {
            $updateData = [
                'name' => strtolower($data['name']),
                'guard_name' => $data['guard_name'] ?? 'web',
            ];

            if (array_key_exists('description', $data)) {
                $updateData['description'] = $data['description'];
            }

            if (isset($data['module'])) {
                $updateData['module'] = strtolower($data['module']);
            }

            $permission->update($updateData);

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return $permission->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a permission.
     */
    public function delete(Permission $permission): bool
    {
        DB::beginTransaction();
        try {
            $permission->roles()->detach();
            $permission->delete();

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get grouped permissions (for matrix view).
     */
    public function getGroupedPermissions(): array
    {
        $permissions = Permission::all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $group = $this->getPermissionGroup($permission->name);
            $grouped[$group][] = $permission;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Parse a permission name into action and module parts.
     *
     * Strategy: Try to match known actions from LONGEST to SHORTEST against
     * the start of the permission name. The remainder after the matched
     * action prefix (minus the separating underscore) is the module.
     *
     * Examples:
     *   "view_users"           → action="view",         module="users"
     *   "view_example_module"  → action="view",         module="example_module"
     *   "bulk_import_serials"  → action="bulk_import",  module="serials"
     *   "manage_contacts_sites"→ action="manage_contacts", module="sites"
     *   "approve_stock_transfers" → action="approve",   module="stock_transfers"
     */
    protected function parsePermissionName(string $permissionName): array
    {
        $knownActions = array_keys($this->getKnownActions());

        // Sort by length descending so longer actions match first
        // e.g., "bulk_import" matches before "bulk"
        usort($knownActions, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($knownActions as $knownAction) {
            $prefix = $knownAction . '_';
            if (strpos($permissionName, $prefix) === 0) {
                $module = substr($permissionName, strlen($prefix));
                if ($module !== '' && $module !== false) {
                    return [
                        'action' => $knownAction,
                        'module' => $module,
                    ];
                }
            }
        }

        // Fallback: first segment is action, rest is module
        $parts = explode('_', $permissionName);
        if (count($parts) >= 2) {
            $action = $parts[0];
            $module = implode('_', array_slice($parts, 1));
            return [
                'action' => $action,
                'module' => $module,
            ];
        }

        return [
            'action' => $permissionName,
            'module' => 'general',
        ];
    }

    /**
     * Extract permission group/module from permission name.
     *
     * Example: "view_example_module" -> "example_module"
     *          "bulk_import_serials" -> "serials"
     */
    public function getPermissionGroup(string $permissionName): string
    {
        return $this->parsePermissionName($permissionName)['module'];
    }

    /**
     * Extract permission action from permission name.
     *
     * Example: "view_example_module" -> "view"
     *          "bulk_import_serials" -> "bulk_import"
     */
    public function getPermissionAction(string $permissionName): string
    {
        return $this->parsePermissionName($permissionName)['action'];
    }

    /**
     * Get all existing permission groups.
     */
    public function getAllPermissionGroups(): array
    {
        $permissions = Permission::all();
        $groups = [];

        foreach ($permissions as $permission) {
            $group = $this->getPermissionGroup($permission->name);
            if (!in_array($group, $groups)) {
                $groups[] = $group;
            }
        }

        sort($groups);
        return $groups;
    }

    /**
     * Get known actions for permission builder.
     * Returns associative array: key => label
     */
    public function getKnownActions(): array
    {
        return [
            'view' => 'View',
            'view_all' => 'View All',
            'view_team' => 'View Team',
            'view_own' => 'View Own',
            'view_history' => 'View History',
            'view_assets' => 'View Assets',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'restore' => 'Restore',
            'approve' => 'Approve',
            'reject' => 'Reject',
            'submit' => 'Submit',
            'cancel' => 'Cancel',
            'void' => 'Void',
            'export' => 'Export',
            'import' => 'Import',
            'bulk_import' => 'Bulk Import',
            'bulk_update' => 'Bulk Update',
            'bulk_transfer' => 'Bulk Transfer',
            'print' => 'Print',
            'print_labels' => 'Print Labels',
            'send' => 'Send',
            'assign' => 'Assign',
            'assign_roles' => 'Assign Roles',
            'reassign' => 'Reassign',
            'transfer' => 'Transfer',
            'adjust' => 'Adjust',
            'receive' => 'Receive',
            'dispatch' => 'Dispatch',
            'post' => 'Post',
            'complete' => 'Complete',
            'start' => 'Start',
            'fail' => 'Fail',
            'close' => 'Close',
            'convert' => 'Convert',
            'manage' => 'Manage',
            'manage_contacts' => 'Manage Contacts',
            'manage_system' => 'Manage System',
            'schedule' => 'Schedule',
            'mark_paid' => 'Mark as Paid',
            'change_password' => 'Change Password',
        ];
    }

    /**
     * Get permission statistics.
     */
    public function getStatistics(): array
    {
        $totalPermissions = Permission::count();
        $permissionsInUse = Permission::has('roles')->count();
        $unusedPermissions = $totalPermissions - $permissionsInUse;
        $permissionGroups = count($this->getAllPermissionGroups());

        return [
            'total_permissions' => $totalPermissions,
            'permissions_in_use' => $permissionsInUse,
            'unused_permissions' => $unusedPermissions,
            'permission_groups' => $permissionGroups,
        ];
    }
}
