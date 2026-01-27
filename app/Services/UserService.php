<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UserService
{
    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        // Handle avatar upload
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        }
        
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        // Convert arrays to JSON
        if (isset($data['coverage_states']) && is_array($data['coverage_states'])) {
            $data['coverage_states'] = json_encode($data['coverage_states']);
        }
        
        if (isset($data['skill_tags']) && is_array($data['skill_tags'])) {
            $data['skill_tags'] = json_encode($data['skill_tags']);
        }
        
        // Extract role before creating user
        $role = $data['role'] ?? null;
        unset($data['role']);
        
        // Create user
        $user = User::create($data);
        
        // Assign role
        if ($role) {
            $user->assignRole($role);
        }
        
        return $user->fresh(['roles', 'supervisor']);
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data): User
    {
        // Handle avatar upload
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            // Delete old avatar if exists
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        } else {
            // Remove avatar from data if not uploading new one
            unset($data['avatar']);
        }
        
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Remove password from data if not updating
            unset($data['password']);
            unset($data['password_confirmation']);
        }
        
        // Convert arrays to JSON
        if (isset($data['coverage_states']) && is_array($data['coverage_states'])) {
            $data['coverage_states'] = json_encode($data['coverage_states']);
        }
        
        if (isset($data['skill_tags']) && is_array($data['skill_tags'])) {
            $data['skill_tags'] = json_encode($data['skill_tags']);
        }
        
        // Extract role before updating user
        $role = $data['role'] ?? null;
        unset($data['role']);
        
        // Update user
        $user->update($data);
        
        // Update role if provided
        if ($role) {
            $user->syncRoles([$role]);
        }
        
        return $user->fresh(['roles', 'supervisor']);
    }

    /**
     * Handle avatar file upload
     */
    protected function handleAvatarUpload(UploadedFile $file): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');
        
        return $path;
    }

    /**
     * Delete avatar file
     */
    protected function deleteAvatar(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Generate unique employee ID
     */
    public function generateEmployeeId(): string
    {
        $year = date('Y');
        $prefix = "EMP{$year}";
        
        $lastUser = User::where('employee_id', 'like', "{$prefix}%")
                        ->orderBy('employee_id', 'desc')
                        ->first();
        
        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->employee_id, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    /**
     * Get users by role with team scoping
     */
    public function getUsersByRole(string $role, ?User $currentUser = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::role($role)->where('status', 'active');
        
        // Apply team scoping if current user is supervisor
        if ($currentUser && $currentUser->hasRole('supervisor')) {
            $query->where(function($q) use ($currentUser) {
                $q->where('supervisor_id', $currentUser->id)
                  ->orWhere('id', $currentUser->id);
            });
        }
        
        return $query->get();
    }

    /**
     * Get technicians under a supervisor
     */
    public function getSupervisorTeam(User $supervisor): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('supervisor_id', $supervisor->id)
            ->where('status', 'active')
            ->with('roles')
            ->get();
    }

    /**
     * Check if user can be assigned as supervisor
     */
    public function canBeAssignedAsSupervisor(User $user): bool
    {
        return $user->hasRole('supervisor') && $user->status === 'active';
    }

    /**
     * Validate team scoping access
     */
    public function canAccessUser(User $currentUser, User $targetUser): bool
    {
        // Admin can access all
        if ($currentUser->hasRole('admin')) {
            return true;
        }
        
        // Supervisor can access their team
        if ($currentUser->hasRole('supervisor')) {
            return $targetUser->id === $currentUser->id 
                || $targetUser->supervisor_id === $currentUser->id;
        }
        
        // Technician can only access self
        if ($currentUser->hasRole('technician')) {
            return $targetUser->id === $currentUser->id;
        }
        
        return false;
    }

    /**
     * Get user statistics
     */
    public function getUserStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'inactive_users' => User::where('status', 'inactive')->count(),
            'suspended_users' => User::where('status', 'suspended')->count(),
            'admins' => User::role('admin')->count(),
            'supervisors' => User::role('supervisor')->count(),
            'technicians' => User::role('technician')->count(),
            'users_with_supervisor' => User::whereNotNull('supervisor_id')->count(),
            'independent_technicians' => User::role('technician')
                ->whereNull('supervisor_id')
                ->count(),
        ];
    }

    /**
     * Get supervisor statistics
     */
    public function getSupervisorStatistics(User $supervisor): array
    {
        $teamMembers = $this->getSupervisorTeam($supervisor);
        
        return [
            'total_team_members' => $teamMembers->count(),
            'active_team_members' => $teamMembers->where('status', 'active')->count(),
            'team_with_coverage' => $teamMembers->filter(function($member) {
                return !empty($member->coverage_states);
            })->count(),
        ];
    }
}