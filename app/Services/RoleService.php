<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    /**
     * System roles that cannot be modified or deleted.
     */
    protected array $systemRoles = ['admin', 'supervisor', 'technician'];

    /**
     * Get DataTables data for roles listing.
     */
    public function getDatatableData(Request $request): JsonResponse
    {
        $query = Role::withCount(['permissions', 'users']);

        // Search filter
        if ($search = $request->input('search.value')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Get total count before pagination
        $totalRecords = Role::count();
        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');
        
        $columns = ['id', 'name', 'permissions_count', 'users_count', 'created_at'];
        $sortColumn = $columns[$orderColumn] ?? 'name';
        
        $query->orderBy($sortColumn, $orderDir);

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        
        $roles = $query->skip($start)->take($length)->get();

        $data = $roles->map(function ($role) {
            $isSystem = in_array($role->name, $this->systemRoles);
            
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $this->formatDisplayName($role->name),
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'is_system' => $isSystem,
                'guard_name' => $role->guard_name,
                'created_at' => $role->created_at->format('M d, Y'),
                'actions' => $this->renderActions($role, $isSystem)
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
     * Render action buttons for DataTables.
     */
    protected function renderActions(Role $role, bool $isSystem): string
    {
        $actions = '<div class="btn-group btn-group-sm" role="group">';
        
        // View button - always available
        $actions .= '<a href="' . route('admin.roles.show', $role) . '" class="btn btn-info" title="View">
            <i class="bi bi-eye"></i>
        </a>';

        if ($isSystem) {
            // System role - only view and info
            $actions .= '<button type="button" class="btn btn-secondary" disabled title="System Role">
                <i class="bi bi-lock"></i>
            </button>';
        } else {
            // Edit button
            $actions .= '<a href="' . route('admin.roles.edit', $role) . '" class="btn btn-warning" title="Edit">
                <i class="bi bi-pencil"></i>
            </a>';
            
            // Delete button
            $actions .= '<button type="button" class="btn btn-danger btn-delete" 
                data-id="' . $role->id . '" 
                data-name="' . $role->name . '" 
                data-users="' . $role->users_count . '"
                title="Delete">
                <i class="bi bi-trash"></i>
            </button>';
            
            // Clone button
            $actions .= '<button type="button" class="btn btn-success btn-clone" 
                data-id="' . $role->id . '" 
                data-name="' . $role->name . '"
                title="Clone">
                <i class="bi bi-copy"></i>
            </button>';
        }
        
        $actions .= '</div>';
        
        return $actions;
    }

    /**
     * Format role name for display.
     */
    protected function formatDisplayName(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }

    /**
     * Create a new role with permissions.
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
            ]);

            if (!empty($data['permissions'])) {
                $permissions = Permission::whereIn('id', $data['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Update an existing role with permissions.
     */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? $role->guard_name,
            ]);

            if (isset($data['permissions'])) {
                $permissions = Permission::whereIn('id', $data['permissions'])->get();
                $role->syncPermissions($permissions);
            }

            return $role->fresh()->load('permissions');
        });
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): bool
    {
        return DB::transaction(function () use ($role) {
            // Remove all permissions from role first
            $role->syncPermissions([]);
            
            return $role->delete();
        });
    }

    /**
     * Clone an existing role with a new name.
     */
    public function cloneRole(Role $sourceRole, string $newName, ?string $description = null): Role
    {
        return DB::transaction(function () use ($sourceRole, $newName, $description) {
            $newRole = Role::create([
                'name' => $newName,
                'guard_name' => $sourceRole->guard_name,
            ]);

            // Copy permissions from source role
            $permissions = $sourceRole->permissions;
            $newRole->syncPermissions($permissions);

            return $newRole->load('permissions');
        });
    }

    /**
     * Get role statistics.
     */
    public function getStatistics(): array
    {
        $totalRoles = Role::count();
        $systemRolesCount = Role::whereIn('name', $this->systemRoles)->count();
        $customRolesCount = $totalRoles - $systemRolesCount;
        $totalPermissions = Permission::count();

        $rolesWithUsers = Role::has('users')->count();
        $unusedRoles = $totalRoles - $rolesWithUsers;

        return [
            'total_roles' => $totalRoles,
            'system_roles' => $systemRolesCount,
            'custom_roles' => $customRolesCount,
            'total_permissions' => $totalPermissions,
            'roles_with_users' => $rolesWithUsers,
            'unused_roles' => $unusedRoles,
        ];
    }

    /**
     * Check if a role is a system role.
     */
    public function isSystemRole(Role $role): bool
    {
        return in_array($role->name, $this->systemRoles);
    }

    /**
     * Get all system role names.
     */
    public function getSystemRoles(): array
    {
        return $this->systemRoles;
    }

    /**
     * Get roles for dropdown selection.
     */
    public function getRolesForDropdown(bool $excludeSystemRoles = false): array
    {
        $query = Role::orderBy('name');

        if ($excludeSystemRoles) {
            $query->whereNotIn('name', $this->systemRoles);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Get roles with their permission counts.
     */
    public function getRolesWithPermissionCounts(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::withCount('permissions')
            ->orderBy('name')
            ->get();
    }
}
