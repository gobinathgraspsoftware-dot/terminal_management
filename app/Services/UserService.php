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

        // NOTE: Do NOT manually Hash::make — User model has 'password' => 'hashed' cast
        // Just pass the plain password; the cast handles hashing automatically.

        // Convert arrays to JSON for coverage_states (kept for backward compat)
        if (isset($data['coverage_states']) && is_array($data['coverage_states'])) {
            $data['coverage_states'] = json_encode($data['coverage_states']);
        }

        if (isset($data['skill_tags']) && is_array($data['skill_tags'])) {
            $data['skill_tags'] = json_encode($data['skill_tags']);
        }

        // Handle state_id / city_id — ensure null when not technician
        if (!isset($data['role']) || $data['role'] !== 'technician') {
            $data['state_id'] = null;
            $data['city_id'] = null;
            $data['supervisor_id'] = null;
            $data['coverage_states'] = null;
            $data['skill_tags'] = null;
        }

        // Extract role before creating user
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Remove non-fillable fields
        unset($data['has_supervisor']);
        unset($data['password_confirmation']);
        unset($data['remove_avatar']);

        // Create user
        $user = User::create($data);

        // Assign role
        if ($role) {
            $user->assignRole($role);
        }

        return $user->fresh(['roles', 'supervisor', 'state', 'city']);
    }

    /**
     * Update an existing user
     */
    public function updateUser(User $user, array $data): User
    {
        // Handle remove avatar
        if (isset($data['remove_avatar']) && $data['remove_avatar']) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = null;
        }
        // Handle avatar upload
        elseif (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        } else {
            unset($data['avatar']);
        }

        // NOTE: Do NOT manually Hash::make — User model has 'password' => 'hashed' cast
        if (isset($data['password']) && !empty($data['password'])) {
            // Pass plain password — the model cast handles hashing
        } else {
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

        // Handle state_id / city_id — clear when not technician
        if (isset($data['role']) && $data['role'] !== 'technician') {
            $data['state_id'] = null;
            $data['city_id'] = null;
            $data['supervisor_id'] = null;
            $data['coverage_states'] = null;
            $data['skill_tags'] = null;
        }

        // Extract role before updating user
        $role = $data['role'] ?? null;
        unset($data['role']);

        // Remove non-fillable fields
        unset($data['has_supervisor']);
        unset($data['password_confirmation']);
        unset($data['remove_avatar']);

        // Update user
        $user->update($data);

        // Update role if provided
        if ($role) {
            $user->syncRoles([$role]);
        }

        return $user->fresh(['roles', 'supervisor', 'state', 'city']);
    }

    /**
     * Handle avatar file upload
     */
    protected function handleAvatarUpload(UploadedFile $file): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $directory = 'avatars';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        $path = $file->storeAs($directory, $filename, 'public');

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
        $query = User::whereHas('roles', fn ($q) => $q->where('roles.name', $role))
                     ->where('status', 'active');

        if ($currentUser && $currentUser->hasRole('supervisor')) {
            $query->where(function ($q) use ($currentUser) {
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
        if ($currentUser->hasRole('admin')) {
            return true;
        }

        if ($currentUser->hasRole('supervisor')) {
            return $targetUser->id === $currentUser->id
                || $targetUser->supervisor_id === $currentUser->id;
        }

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
            'team_with_coverage' => $teamMembers->filter(function ($member) {
                return !empty($member->coverage_states);
            })->count(),
        ];
    }
}
