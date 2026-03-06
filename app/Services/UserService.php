<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class UserService
{
    /**
     * Create a new user
     *
     * IMPORTANT - Model casts handle these automatically:
     *   'password' => 'hashed'        → do NOT Hash::make()
     *   'coverage_states' => 'array'   → do NOT json_encode()
     *   'skill_tags' => 'array'        → do NOT json_encode()
     */
    public function createUser(array $data): User
    {
        // Handle avatar upload (cPanel compatible)
        if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        } else {
            unset($data['avatar']);
        }

        // Ensure arrays are proper arrays (Select2 may send strings)
        if (isset($data['coverage_states']) && is_string($data['coverage_states'])) {
            $data['coverage_states'] = json_decode($data['coverage_states'], true) ?? [];
        }
        if (isset($data['skill_tags']) && is_string($data['skill_tags'])) {
            $data['skill_tags'] = json_decode($data['skill_tags'], true) ?? [];
        }

        // Extract non-fillable fields
        $role = $data['role'] ?? null;
        unset($data['role'], $data['has_supervisor'], $data['password_confirmation'], $data['remove_avatar']);

        // Create user (password auto-hashed by model cast, arrays auto-encoded)
        $user = User::create($data);

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
        // Handle remove avatar
        if (!empty($data['remove_avatar'])) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = null;
        }
        // Handle new avatar upload (cPanel compatible)
        elseif (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
            if ($user->avatar) {
                $this->deleteAvatar($user->avatar);
            }
            $data['avatar'] = $this->handleAvatarUpload($data['avatar']);
        } else {
            unset($data['avatar']);
        }

        // Remove password if empty (user didn't change it)
        if (empty($data['password'])) {
            unset($data['password']);
        }
        // If password is provided, model cast 'hashed' will auto-hash it

        // Ensure arrays are proper arrays
        if (isset($data['coverage_states']) && is_string($data['coverage_states'])) {
            $data['coverage_states'] = json_decode($data['coverage_states'], true) ?? [];
        }
        if (isset($data['skill_tags']) && is_string($data['skill_tags'])) {
            $data['skill_tags'] = json_decode($data['skill_tags'], true) ?? [];
        }

        // Extract non-fillable fields
        $role = $data['role'] ?? null;
        unset($data['role'], $data['has_supervisor'], $data['password_confirmation'], $data['remove_avatar']);

        // Update user
        $user->update($data);

        if ($role) {
            $user->syncRoles([$role]);
        }

        return $user->fresh(['roles', 'supervisor']);
    }

    /**
     * Handle avatar file upload — cPanel compatible
     *
     * Uses move() to public_path('storage/') directly instead of storeAs().
     * storeAs() writes to storage/app/public/ which needs a symlink that cPanel breaks.
     * Same pattern as TerminalModelService::handleImageUpload().
     */
    protected function handleAvatarUpload(UploadedFile $file): string
    {
        $directory = 'avatars';
        $targetDir = public_path('storage/' . $directory);

        // Create directory if not exists
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            Log::info('Created avatars directory', ['path' => $targetDir]);
        }

        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $relativePath = $directory . '/' . $filename;

        // Move file directly to public/storage/avatars/ (bypasses symlink)
        $file->move($targetDir, $filename);

        // Verify file exists
        $fullPath = $targetDir . '/' . $filename;
        if (!file_exists($fullPath)) {
            Log::error('Avatar file not found after move', ['path' => $fullPath]);
            throw new \Exception('Failed to save avatar file');
        }

        Log::info('Avatar uploaded (cPanel move)', [
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'file_size' => filesize($fullPath),
        ]);

        return $relativePath;
    }

    /**
     * Delete avatar file — checks both public_path and storage disk
     * Same pattern as TerminalModelService::deleteImage()
     */
    protected function deleteAvatar(string $path): void
    {
        // Check public_path first (cPanel direct uploads via move())
        $publicFile = public_path('storage/' . $path);
        if (file_exists($publicFile)) {
            unlink($publicFile);
            Log::info('Avatar deleted from public_path', ['path' => $publicFile]);
            return;
        }

        // Fallback: check storage disk (for older uploads via storeAs)
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            Log::info('Avatar deleted from storage disk', ['path' => $path]);
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
            'team_with_coverage' => $teamMembers->filter(function($member) {
                return !empty($member->coverage_states);
            })->count(),
        ];
    }
}
