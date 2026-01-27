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
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
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
        
        // Get supervisors for assignment dropdown
        $supervisors = User::role('supervisor')
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
        $query = User::with(['roles', 'supervisor'])
            ->select('users.*');
        
        // Apply team scoping
        if ($currentUser->hasRole('supervisor')) {
            // Supervisor sees only their team technicians
            $query->where(function($q) use ($currentUser) {
                $q->where('supervisor_id', $currentUser->id)
                  ->orWhere('id', $currentUser->id); // Include self
            });
        } elseif ($currentUser->hasRole('technician')) {
            // Technician sees only self
            $query->where('id', $currentUser->id);
        }
        // Admin sees all users (no additional filter)
        
        return DataTables::of($query)
            ->addColumn('role', function ($user) {
                $roles = $user->roles->pluck('name')->map(function($role) {
                    $badgeClass = match($role) {
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
                $badgeClass = match($user->status) {
                    'active' => 'success',
                    'inactive' => 'warning',
                    'suspended' => 'danger',
                    default => 'secondary'
                };
                return "<span class='badge bg-{$badgeClass}'>" . ucfirst($user->status) . "</span>";
            })
            ->addColumn('last_login', function ($user) {
                return $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i') : 'Never';
            })
            ->addColumn('actions', function ($user) use ($currentUser) {
                $actions = '';
                
                // View button
                if ($currentUser->can('users.view')) {
                    $actions .= '<button type="button" class="btn btn-sm btn-info view-user" data-id="' . $user->id . '" title="View">
                        <i class="fas fa-eye"></i>
                    </button> ';
                }
                
                // Edit button
                if ($currentUser->can('users.edit')) {
                    $actions .= '<a href="' . route('admin.users.edit', $user->id) . '" class="btn btn-sm btn-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a> ';
                }
                
                // Delete/Restore button
                if ($currentUser->can('users.delete')) {
                    if ($user->trashed()) {
                        $actions .= '<button type="button" class="btn btn-sm btn-success restore-user" data-id="' . $user->id . '" title="Restore">
                            <i class="fas fa-undo"></i>
                        </button>';
                    } else {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger delete-user" data-id="' . $user->id . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>';
                    }
                }
                
                return $actions;
            })
            ->filter(function ($query) use ($request) {
                // Search functionality
                if ($request->has('search') && $request->search['value']) {
                    $searchValue = $request->search['value'];
                    $query->where(function($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                          ->orWhere('email', 'like', "%{$searchValue}%")
                          ->orWhere('phone', 'like', "%{$searchValue}%")
                          ->orWhere('employee_id', 'like', "%{$searchValue}%");
                    });
                }
                
                // Role filter
                if ($request->has('role') && $request->role) {
                    $query->whereHas('roles', function($q) use ($request) {
                        $q->where('name', $request->role);
                    });
                }
                
                // Status filter
                if ($request->has('status') && $request->status) {
                    $query->where('status', $request->status);
                }
                
                // Supervisor filter
                if ($request->has('supervisor_id') && $request->supervisor_id) {
                    $query->where('supervisor_id', $request->supervisor_id);
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
        
        $supervisors = User::role('supervisor')
            ->where('status', 'active')
            ->get(['id', 'name']);
        
        // Coverage states (Malaysian states)
        $states = [
            'Johor', 'Kedah', 'Kelantan', 'Malacca', 'Negeri Sembilan',
            'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak',
            'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
        ];
        
        // Skill tags
        $skillTags = [
            'Installation', 'Repair', 'Troubleshooting', 'Maintenance',
            'Network Setup', 'POS Configuration', 'Training', 'Collection'
        ];
        
        return view('admin.users.create', compact('roles', 'supervisors', 'states', 'skillTags'));
    }

    /**
     * Store a newly created user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $user = $this->userService->createUser($request->validated());
            
            DB::commit();
            
            // Log activity
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
    public function show(User $user): JsonResponse
    {
        // Check authorization
        $this->authorize('view', $user);
        
        $user->load(['roles', 'supervisor']);
        
        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user): View
    {
        // Check authorization
        $this->authorize('update', $user);
        
        $user->load(['roles', 'supervisor']);
        
        $roles = Role::all();
        
        $supervisors = User::role('supervisor')
            ->where('status', 'active')
            ->where('id', '!=', $user->id) // Exclude current user
            ->get(['id', 'name']);
        
        // Coverage states
        $states = [
            'Johor', 'Kedah', 'Kelantan', 'Malacca', 'Negeri Sembilan',
            'Pahang', 'Penang', 'Perak', 'Perlis', 'Sabah', 'Sarawak',
            'Selangor', 'Terengganu', 'Kuala Lumpur', 'Labuan', 'Putrajaya'
        ];
        
        // Skill tags
        $skillTags = [
            'Installation', 'Repair', 'Troubleshooting', 'Maintenance',
            'Network Setup', 'POS Configuration', 'Training', 'Collection'
        ];
        
        return view('admin.users.edit', compact('user', 'roles', 'supervisors', 'states', 'skillTags'));
    }

    /**
     * Update the specified user
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            // Check authorization
            $this->authorize('update', $user);
            
            DB::beginTransaction();
            
            $oldData = $user->toArray();
            
            $user = $this->userService->updateUser($user, $request->validated());
            
            DB::commit();
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties([
                    'old' => $oldData,
                    'new' => $request->except('password')
                ])
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
            // Check authorization
            $this->authorize('delete', $user);
            
            // Prevent deleting self
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            // Soft delete
            $user->delete();
            
            // Revoke all tokens
            $user->tokens()->delete();
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->log('User deleted (soft)');
            
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
     * Restore a soft-deleted user
     */
    public function restore($id): JsonResponse
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            
            // Check authorization
            $this->authorize('restore', $user);
            
            $user->restore();
            
            // Log activity
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
            // Check authorization
            $this->authorize('update', $user);
            
            $user->update([
                'password' => Hash::make($request->password)
            ]);
            
            // Revoke all tokens (force re-login)
            $user->tokens()->delete();
            
            // Log activity
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
            // Check authorization
            $this->authorize('update', $user);
            
            $request->validate([
                'role' => 'required|exists:roles,name'
            ]);
            
            // Sync role (replace existing)
            $user->syncRoles([$request->role]);
            
            // Log activity
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
            // Check authorization
            $this->authorize('update', $user);
            
            $request->validate([
                'supervisor_id' => 'nullable|exists:users,id'
            ]);
            
            // Verify supervisor has supervisor role
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
            
            // Log activity
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
            // Check authorization
            $this->authorize('update', $user);
            
            // Prevent deactivating self
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account'
                ], 403);
            }
            
            $newStatus = $user->status === 'active' ? 'inactive' : 'active';
            
            $user->update(['status' => $newStatus]);
            
            // If deactivating, revoke all tokens
            if ($newStatus === 'inactive') {
                $user->tokens()->delete();
            }
            
            // Log activity
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
            $query->role($role);
        }
        
        if ($search) {
            $query->where(function($q) use ($search) {
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
        // Implementation for export functionality
        // You can use Laravel Excel package or generate CSV
        
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
            
            // Prevent deleting self
            if (in_array(Auth::id(), $request->user_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            $deletedCount = User::whereIn('id', $request->user_ids)
                ->delete();
            
            // Log activity
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