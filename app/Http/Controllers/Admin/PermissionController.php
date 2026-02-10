<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Permission\StorePermissionRequest;
use App\Http\Requests\Admin\Permission\UpdatePermissionRequest;
use App\Services\PermissionService;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PermissionController extends Controller
{
    use AuthorizesRequests;

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Display a listing of permissions.
     * Returns DataTables JSON for AJAX requests, otherwise returns view.
     */
    public function index(Request $request)
    {
        // Check if this is an AJAX/DataTables request
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            try {
                $data = $this->permissionService->getPermissionsData();

                // Log for debugging
                Log::info('Permission AJAX Request', [
                    'count' => count($data),
                    'sample' => isset($data[0]) ? $data[0] : null
                ]);

                return response()->json([
                    'data' => $data
                ]);
            } catch (\Exception $e) {
                Log::error('Permission DataTable Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'error' => $e->getMessage(),
                    'data' => []
                ], 500);
            }
        }

        // Regular page request - return view with stats
        $stats = $this->permissionService->getStatistics();

        return view('admin.permissions.index', compact('stats'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        $this->authorize('create_permissions');

        $knownActions = $this->permissionService->getKnownActions();
        $existingGroups = $this->permissionService->getAllPermissionGroups();

        return view('admin.permissions.create', compact('knownActions', 'existingGroups'));
    }

    /**
     * Store a newly created permission.
     */
    public function store(StorePermissionRequest $request)
    {
        $this->authorize('create_permissions');

        try {
            $permission = $this->permissionService->create($request->validated());

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission created successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        $this->authorize('edit_permissions');

        $knownActions = $this->permissionService->getKnownActions();
        $existingGroups = $this->permissionService->getAllPermissionGroups();
        $rolesUsingPermission = $permission->roles()->get();
        $action = $this->permissionService->getPermissionAction($permission->name);
        $group = $this->permissionService->getPermissionGroup($permission->name);

        return view('admin.permissions.edit', compact(
            'permission',
            'knownActions',
            'existingGroups',
            'rolesUsingPermission',
            'action',
            'group'
        ));
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $this->authorize('edit_permissions');

        try {
            $this->permissionService->update($permission, $request->validated());

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission updated successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('delete_permissions');

        try {
            $rolesCount = $permission->roles()->count();

            if ($rolesCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete permission. It is currently assigned to {$rolesCount} role(s)."
                ], 422);
            }

            $this->permissionService->delete($permission);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permission deleted successfully.'
                ]);
            }

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete permission: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }

    /**
     * Display the permission matrix.
     */
    public function matrix()
    {
        $this->authorize('manage_permissions');

        $groupedPermissions = $this->permissionService->getGroupedPermissions();
        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.permissions.matrix', compact('groupedPermissions', 'roles'));
    }

    /**
     * Update permission matrix.
     */
    public function updateMatrix(Request $request)
    {
        $this->authorize('manage_permissions');

        try {
            DB::beginTransaction();

            foreach ($request->permissions ?? [] as $permissionId => $roleIds) {
                $permission = Permission::findOrFail($permissionId);
                $permission->roles()->sync($roleIds ?? []);
            }

            DB::commit();

            return back()->with('success', 'Permission matrix updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update matrix: ' . $e->getMessage());
        }
    }

    /**
     * Export permission matrix as CSV.
     */
    public function exportMatrix()
    {
        $this->authorize('export_permissions');

        $groupedPermissions = $this->permissionService->getGroupedPermissions();
        $roles = \Spatie\Permission\Models\Role::all();

        $filename = 'permission_matrix_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($groupedPermissions, $roles) {
            $file = fopen('php://output', 'w');

            $header = ['Module', 'Permission'];
            foreach ($roles as $role) {
                $header[] = ucfirst($role->name);
            }
            fputcsv($file, $header);

            foreach ($groupedPermissions as $group => $permissions) {
                foreach ($permissions as $permission) {
                    $row = [$group, $permission->name];

                    foreach ($roles as $role) {
                        $row[] = $role->hasPermissionTo($permission->name) ? 'Yes' : 'No';
                    }

                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
