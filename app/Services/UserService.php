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
        // Handle avatar upload (cPanel compatible — uses DOCUMENT_ROOT)
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

        // Create user (password auto-hashed by model cast, arrays auto-encoded by cast)
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
        // If password is provided, model cast 'hashed' will auto-hash it — do NOT Hash::make()

        // Ensure arrays are proper arrays (model cast handles encoding)
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
     * Handle avatar file upload — cPanel compatible (PERMANENT FIX)
     *
     * CRITICAL: On cPanel, public_path() returns the Laravel project's /public directory,
     * but the actual web root is $_SERVER['DOCUMENT_ROOT'] (e.g., /home/user/public_html).
     * Using public_path() writes files to a directory the web server can't serve.
     *
     * Solution: Use $_SERVER['DOCUMENT_ROOT'] for file placement,
     * and asset('storage/...') for URL generation (no file_exists checks).
     */
    protected function handleAvatarUpload(UploadedFile $file): string
    {
        $directory = 'avatars';

        // Use DOCUMENT_ROOT — the actual web-accessible directory on cPanel
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $directory;

        // Create directory if not exists
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
            Log::info('Created avatars directory', ['path' => $targetDir]);
        }

        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $relativePath = $directory . '/' . $filename;

        // Move file directly to DOCUMENT_ROOT/storage/avatars/ (bypasses symlink issues)
        $file->move($targetDir, $filename);

        // Verify file exists
        $fullPath = $targetDir . '/' . $filename;
        if (!file_exists($fullPath)) {
            Log::error('Avatar file not found after move', ['path' => $fullPath]);
            throw new \Exception('Failed to save avatar file');
        }

        Log::info('Avatar uploaded (cPanel DOCUMENT_ROOT)', [
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'file_size' => filesize($fullPath),
        ]);

        return $relativePath;
    }

    /**
     * Delete avatar file — checks DOCUMENT_ROOT first, then storage disk fallback
     */
    protected function deleteAvatar(string $path): void
    {
        // Check DOCUMENT_ROOT first (cPanel direct uploads)
        $docRootFile = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $path;
        if (file_exists($docRootFile)) {
            unlink($docRootFile);
            Log::info('Avatar deleted from DOCUMENT_ROOT', ['path' => $docRootFile]);
            return;
        }

        // Fallback: check public_path (local dev / XAMPP)
        $publicFile = public_path('storage/' . $path);
        if (file_exists($publicFile)) {
            unlink($publicFile);
            Log::info('Avatar deleted from public_path', ['path' => $publicFile]);
            return;
        }

        // Fallback: check storage disk (for very old uploads via storeAs)
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
