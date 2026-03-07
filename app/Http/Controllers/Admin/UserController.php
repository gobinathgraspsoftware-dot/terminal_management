<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    use AuthorizesRequests;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a listing of users with team scoping
     */
    public function index(): View
    {
        $currentUser = Auth::user();

        // Get roles for filter dropdown
        $roles = Role::all();

        // Get supervisors for filter dropdown (all active supervisors)
        $supervisors = User::whereHas('roles', fn ($q) => $q->where('roles.name', 'supervisor'))
            ->where('status', 'active')
            ->get(['id', 'name']);

        return view('admin.users.index', compact('roles', 'supervisors'));
    }

    /**
     * DataTables server-side processing with team scoping
     */
    public function datatable(Request $request): JsonResponse
    {
        $currentUser = Auth::user();

        // Base query with team scoping
        $query = User::with(['roles', 'supervisor', 'state', 'city'])
            ->select('users.*');

        // Apply team scoping
        if ($currentUser->hasRole('supervisor')) {
            $query->where(function ($q) use ($currentUser) {
                $q->where('supervisor_id', $currentUser->id)
                  ->orWhere('id', $currentUser->id);
            });
        } elseif ($currentUser->hasRole('technician')) {
            $query->where('id', $currentUser->id);
        }

        return DataTables::of($query)
            ->addColumn('role', function ($user) {
                $roles = $user->roles->pluck('name')->map(function ($role) {
                    $badgeClass = match ($role) {
                        'admin' => 'danger',
                        'supervisor' => 'primary',
                        'technician' => 'success',
                        default => 'secondary'
                    };
                    return "<span class='badge bg-{$badgeClass}'>{$role}</span>";
                })->join(' ');
                return $roles ?: '<span class="badge bg-secondary">No Role</span>';
            })
            ->addColumn('supervisor_name', function ($user) {
                return $user->supervisor ? $user->supervisor->name : '-';
            })
            ->addColumn('status_badge', function ($user) {
                $badgeClass = match ($user->status) {
                    'active' => 'success',
                    'inactive' => 'warning',
                    'suspended' => 'danger',
                    default => 'secondary'
                };
                return "<span class='badge bg-{$badgeClass}'>" . ucfirst($user->status) . "</span>";
            })
            ->addColumn('actions', function ($user) use ($currentUser) {
                $actions = '<div class="d-flex align-items-center gap-1 flex-nowrap">';

                if ($currentUser->can('view_users')) {
                    $actions .= '<button type="button" class="btn btn-sm btn-info view-user" data-id="' . $user->id . '" title="View" data-bs-toggle="tooltip">
                        <i class="bi bi-eye"></i>
                    </button>';
                }

                if ($currentUser->can('edit_users')) {
                    $actions .= '<a href="' . route('admin.users.edit', $user->id) . '" class="btn btn-sm btn-primary" title="Edit" data-bs-toggle="tooltip">
                        <i class="bi bi-pencil"></i>
                    </a>';
                }

                if ($currentUser->can('delete_users') && $user->id !== $currentUser->id) {
                    if ($user->trashed()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success restore-user" data-id="' . $user->id . '" title="Restore" data-bs-toggle="tooltip">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>';
                    } else {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger delete-user" data-id="' . $user->id . '" title="Delete" data-bs-toggle="tooltip">
                            <i class="bi bi-trash"></i>
                        </button>';
                    }
                }

                $actions .= '</div>';

                return $actions ?: '<span class="text-muted small">No actions</span>';
            })
            ->filter(function ($query) use ($request) {
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('users.name', 'like', "%{$searchValue}%")
                          ->orWhere('users.email', 'like', "%{$searchValue}%")
                          ->orWhere('users.employee_id', 'like', "%{$searchValue}%");
                    });
                }

                if ($request->has('role') && $request->role) {
                    $query->whereHas('roles', function ($q) use ($request) {
                        $q->where('roles.name', $request->role);
                    });
                }

                if ($request->has('status') && $request->status) {
                    $query->where('status', $request->status);
                }

                if ($request->has('supervisor_id') && $request->supervisor_id) {
                    if ($request->supervisor_id === 'null') {
                        $query->whereNull('supervisor_id');
                    } else {
                        $query->where('supervisor_id', $request->supervisor_id);
                    }
                }
            })
            ->rawColumns(['role', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new user
     */
    public function create(): View
    {
        $roles = Role::all();

        // Skill tags
        $skillTags = [
            'Installation', 'Repair', 'Troubleshooting', 'Maintenance',
            'Network Setup', 'POS Configuration', 'Training', 'Collection'
        ];

        return view('admin.users.create', compact('roles', 'skillTags'));
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            if (!Auth::user()->can('create_users')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.'
                ], 403);
            }

            DB::beginTransaction();

            $user = $this->userService->createUser($request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties($request->except('password'))
                ->log('User created');

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user,
                'redirect' => route('admin.users.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(Request $request, $id): View|JsonResponse
    {
        $user = User::with(['roles', 'supervisor', 'technicians', 'state', 'city'])->findOrFail($id);

        // AJAX request from modal
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'user' => $user->append('avatar_url')->load(['roles', 'supervisor', 'state', 'city']),
            ]);
        }

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user): View
    {
        $user->load(['roles', 'supervisor', 'state', 'city']);

        $roles = Role::all();

        // Skill tags
        $skillTags = [
            'Installation', 'Repair', 'Troubleshooting', 'Maintenance',
            'Network Setup', 'POS Configuration', 'Training', 'Collection'
        ];

        return view('admin.users.edit', compact('user', 'roles', 'skillTags'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = $this->userService->updateUser($user, $request->validated());

            DB::commit();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties($request->except('password'))
                ->log('User updated');

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user,
                'redirect' => route('admin.users.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user (soft delete)
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }

            $user->delete();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->log('User deleted');

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore soft-deleted user
     */
    public function restore($id): JsonResponse
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->log('User restored');

            return response()->json([
                'success' => true,
                'message' => 'User restored successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to restore user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(ChangePasswordRequest $request, User $user): JsonResponse
    {
        try {
            $user->update([
                'password' => $request->new_password
            ]);

            // Revoke all tokens for security
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->log('Password changed');

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        try {
            $this->authorize('update', $user);

            $request->validate([
                'role' => 'required|exists:roles,name'
            ]);

            $user->syncRoles([$request->role]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties(['role' => $request->role])
                ->log('Role assigned');

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign supervisor to user
     */
    public function assignSupervisor(Request $request, User $user): JsonResponse
    {
        try {
            $this->authorize('update', $user);

            $request->validate([
                'supervisor_id' => 'nullable|exists:users,id'
            ]);

            if ($request->supervisor_id) {
                $supervisor = User::findOrFail($request->supervisor_id);
                if (!$supervisor->hasRole('supervisor')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected user is not a supervisor'
                    ], 422);
                }
            }

            $user->update([
                'supervisor_id' => $request->supervisor_id
            ]);

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties(['supervisor_id' => $request->supervisor_id])
                ->log('Supervisor assigned');

            return response()->json([
                'success' => true,
                'message' => 'Supervisor assigned successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign supervisor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user): JsonResponse
    {
        try {
            $this->authorize('update', $user);

            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account'
                ], 403);
            }

            $newStatus = $user->status === 'active' ? 'inactive' : 'active';

            $user->update(['status' => $newStatus]);

            if ($newStatus === 'inactive') {
                $user->tokens()->delete();
            }

            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties(['status' => $newStatus])
                ->log('User status toggled');

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users list for dropdowns (AJAX)
     */
    public function getUsersList(Request $request): JsonResponse
    {
        $role = $request->get('role');
        $search = $request->get('search');

        $query = User::where('status', 'active');

        if ($role) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.name', $role));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $users = $query->select('id', 'name', 'email', 'employee_id')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Export users to Excel
     */
    public function export(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Export functionality not yet implemented'
        ]);
    }

    /**
     * Bulk delete users
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id'
            ]);

            if (in_array(Auth::id(), $request->user_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }

            $deletedCount = User::whereIn('id', $request->user_ids)
                ->delete();

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['user_ids' => $request->user_ids])
                ->log('Bulk users deleted');

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} users deleted successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete users: ' . $e->getMessage()
            ], 500);
        }
    }
}
