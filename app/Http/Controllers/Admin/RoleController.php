<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Services\RoleService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * System roles that cannot be modified or deleted
     */
    protected array $systemRoles = ['admin', 'supervisor', 'technician'];

    protected RoleService $roleService;
    protected PermissionService $permissionService;

    public function __construct(RoleService $roleService, PermissionService $permissionService)
    {
        $this->roleService = $roleService;
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->roleService->getDatatableData($request);
        }

        $stats = $this->roleService->getStatistics();
        
        return view('admin.roles.index', compact('stats'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = $this->permissionService->getGroupedPermissions();
        
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request)
    {
        try {
            $role = $this->roleService->createRole($request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully.',
                    'role' => $role
                ]);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create role: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        $isSystemRole = in_array($role->name, $this->systemRoles);
        $groupedPermissions = $this->permissionService->getGroupedPermissions();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.show', compact('role', 'isSystemRole', 'groupedPermissions', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        // Prevent editing system roles
        if (in_array($role->name, $this->systemRoles)) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'System roles cannot be modified.');
        }

        $permissions = $this->permissionService->getGroupedPermissions();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        // Prevent updating system roles
        if (in_array($role->name, $this->systemRoles)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be modified.'
                ], 403);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'System roles cannot be modified.');
        }

        try {
            $role = $this->roleService->updateRole($role, $request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully.',
                    'role' => $role
                ]);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update role: ' . $e->getMessage()
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role)
    {
        // Prevent deleting system roles
        if (in_array($role->name, $this->systemRoles)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be deleted.'
                ], 403);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'System roles cannot be deleted.');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role with assigned users. Please reassign users first.'
                ], 422);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'Cannot delete role with assigned users. Please reassign users first.');
        }

        try {
            $this->roleService->deleteRole($role);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully.'
                ]);
            }

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete role: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }

    /**
     * Update permissions for a role via AJAX.
     */
    public function updatePermissions(Request $request, Role $role): JsonResponse
    {
        // Prevent updating system roles permissions
        if (in_array($role->name, $this->systemRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'System role permissions cannot be modified.'
            ], 403);
        }

        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
            $role->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully.',
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
     * Clone an existing role.
     */
    public function clone(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $newRole = $this->roleService->cloneRole($role, $request->name, $request->description);

            return response()->json([
                'success' => true,
                'message' => 'Role cloned successfully.',
                'role' => $newRole
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clone role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role details for AJAX request.
     */
    public function getDetails(Role $role): JsonResponse
    {
        $role->load('permissions');
        
        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'is_system' => in_array($role->name, $this->systemRoles),
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name
                    ];
                }),
                'users_count' => $role->users()->count(),
                'created_at' => $role->created_at->format('Y-m-d H:i:s')
            ]
        ]);
    }
}
