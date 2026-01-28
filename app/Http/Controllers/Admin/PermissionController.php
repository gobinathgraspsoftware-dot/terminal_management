<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;
    protected RoleService $roleService;

    /**
     * System roles that cannot be modified
     */
    protected array $systemRoles = ['admin', 'supervisor', 'technician'];

    public function __construct(PermissionService $permissionService, RoleService $roleService)
    {
        $this->permissionService = $permissionService;
        $this->roleService = $roleService;
    }

    /**
     * Display a listing of all permissions.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->permissionService->getDatatableData($request);
        }

        $stats = $this->permissionService->getStatistics();
        $permissionGroups = $this->permissionService->getGroupedPermissions();

        return view('admin.permissions.index', compact('stats', 'permissionGroups'));
    }

    /**
     * Display the permission matrix view.
     */
    public function matrix(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = $this->permissionService->getGroupedPermissions();
        $systemRoles = $this->systemRoles;

        return view('admin.permissions.matrix', compact('roles', 'permissions', 'systemRoles'));
    }

    /**
     * Update permission matrix via AJAX.
     */
    public function updateMatrix(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permission_id' => 'required|exists:permissions,id',
            'granted' => 'required|boolean'
        ]);

        $role = Role::findById($request->role_id);
        $permission = Permission::findById($request->permission_id);

        // Prevent modifying system roles
        if (in_array($role->name, $this->systemRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'System role permissions cannot be modified.'
            ], 403);
        }

        try {
            if ($request->granted) {
                $role->givePermissionTo($permission);
                $message = "Permission '{$permission->name}' granted to role '{$role->name}'.";
            } else {
                $role->revokePermissionTo($permission);
                $message = "Permission '{$permission->name}' revoked from role '{$role->name}'.";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update permissions for a role.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::findById($request->role_id);

        // Prevent modifying system roles
        if (in_array($role->name, $this->systemRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'System role permissions cannot be modified.'
            ], 403);
        }

        try {
            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' permissions updated successfully.",
                'permissions_count' => $permissions->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permissions for a specific role.
     */
    public function getRolePermissions(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_system' => in_array($role->name, $this->systemRoles)
            ],
            'permissions' => $role->permissions->pluck('id')
        ]);
    }

    /**
     * Get permission details.
     */
    public function show(Permission $permission): JsonResponse
    {
        $roles = $permission->roles()->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'group' => $this->permissionService->getPermissionGroup($permission->name),
                'roles' => $roles,
                'roles_count' => $roles->count(),
                'created_at' => $permission->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get all permissions grouped by module.
     */
    public function getGrouped(): JsonResponse
    {
        $permissions = $this->permissionService->getGroupedPermissions();

        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

    /**
     * Search permissions.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $permissions = Permission::where('name', 'like', '%' . $request->query('query') . '%')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'permissions' => $permissions
        ]);
    }

    /**
     * Get permission usage statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->permissionService->getDetailedStatistics();

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }

    /**
     * Get permission details via AJAX.
     */
    public function getDetails(Permission $permission): JsonResponse
    {
        $roles = $permission->roles()->get(['id', 'name']);
        $group = $this->permissionService->getPermissionGroup($permission->name);
        $action = $this->permissionService->getPermissionAction($permission->name);

        return response()->json([
            'success' => true,
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => ucwords(str_replace(['.', '_'], ' ', $permission->name)),
                'guard_name' => $permission->guard_name,
                'group' => $group,
                'group_label' => $this->permissionService->getPermissionGroupLabels()[$group] ?? ucfirst($group),
                'action' => $action,
                'roles' => $roles,
                'roles_count' => $roles->count(),
                'created_at' => $permission->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Get permissions by group via AJAX.
     */
    public function getByGroup(string $group): JsonResponse
    {
        $permissions = $this->permissionService->getPermissionsByGroup($group);
        $groupLabels = $this->permissionService->getPermissionGroupLabels();

        return response()->json([
            'success' => true,
            'group' => $group,
            'group_label' => $groupLabels[$group] ?? ucfirst($group),
            'permissions' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => ucwords(str_replace(['.', '_'], ' ', $permission->name)),
                    'action' => $this->permissionService->getPermissionAction($permission->name),
                    'roles_count' => $permission->roles()->count()
                ];
            }),
            'count' => $permissions->count()
        ]);
    }

    /**
     * Export permission matrix as CSV.
     */
    public function exportMatrix()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        $filename = 'permission_matrix_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($roles, $permissions) {
            $file = fopen('php://output', 'w');
            
            // Header row
            $header = ['Permission'];
            foreach ($roles as $role) {
                $header[] = $role->name;
            }
            fputcsv($file, $header);

            // Data rows
            foreach ($permissions as $permission) {
                $row = [$permission->name];
                foreach ($roles as $role) {
                    $row[] = $role->hasPermissionTo($permission) ? 'Yes' : 'No';
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
