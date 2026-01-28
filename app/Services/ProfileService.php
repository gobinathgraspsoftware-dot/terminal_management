<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Update user profile information.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
        ]);

        return $user->fresh();
    }

    /**
     * Update user password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Update user avatar.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        // Delete old avatar if exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Create directory if not exists
        $directory = 'avatars';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Generate unique filename
        $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
        $path = $directory . '/' . $filename;

        // Store the original file
        $file->storeAs($directory, $filename, 'public');

        // Create thumbnail (150x150)
        $this->createThumbnail($file, $directory, $filename);

        // Update user avatar path
        $user->update(['avatar' => $path]);

        return $path;
    }

    /**
     * Create thumbnail for avatar.
     */
    private function createThumbnail(UploadedFile $file, string $directory, string $filename): void
    {
        $thumbnailDir = $directory . '/thumbnails';

        if (!Storage::disk('public')->exists($thumbnailDir)) {
            Storage::disk('public')->makeDirectory($thumbnailDir);
        }

        $thumbnailPath = storage_path('app/public/' . $thumbnailDir . '/' . $filename);

        // Simple resize without intervention/image package
        // Just copy the file for now - can be enhanced with image processing library
        copy($file->getRealPath(), $thumbnailPath);
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);

            // Delete thumbnail
            $thumbnailPath = str_replace('avatars/', 'avatars/thumbnails/', $user->avatar);
            Storage::disk('public')->delete($thumbnailPath);

            $user->update(['avatar' => null]);
        }
    }

    /**
     * Update bank details (for technicians).
     * Uses existing DB fields: bank_account_no and bank_account_name
     */
    public function updateBankDetails(User $user, array $data): User
    {
        $user->update([
            'bank_name' => $data['bank_name'],
            'bank_account_no' => $data['bank_account_no'],        // Existing DB field
            'bank_account_name' => $data['bank_account_name'],    // Existing DB field
            'ifsc_code' => $data['ifsc_code'],
            'branch_name' => $data['branch_name'],
        ]);

        return $user->fresh();
    }

    /**
     * Get user login history.
     */
    public function getLoginHistory(User $user, int $limit = 10)
    {
        return LoginHistory::where('user_id', $user->id)
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get login statistics.
     */
    public function getLoginStatistics(User $user): array
    {
        $totalLogins = LoginHistory::where('user_id', $user->id)
            ->where('login_status', 'success')
            ->count();

        $failedLogins = LoginHistory::where('user_id', $user->id)
            ->where('login_status', 'failed')
            ->count();

        $lastLogin = LoginHistory::where('user_id', $user->id)
            ->where('login_status', 'success')
            ->latest('login_at')
            ->first();

        $avgSessionDuration = LoginHistory::where('user_id', $user->id)
            ->where('login_status', 'success')
            ->whereNotNull('session_duration')
            ->avg('session_duration');

        return [
            'total_logins' => $totalLogins,
            'failed_logins' => $failedLogins,
            'last_login' => $lastLogin,
            'avg_session_duration' => round($avgSessionDuration ?? 0),
        ];
    }

    /**
     * Get avatar URL.
     */
    public function getAvatarUrl(?string $avatar): string
    {
        if ($avatar && Storage::disk('public')->exists($avatar)) {
            return Storage::url($avatar);
        }

        // Return default avatar
        return asset('images/default-avatar.png');
    }

    /**
     * Get avatar thumbnail URL.
     */
    public function getAvatarThumbnailUrl(?string $avatar): string
    {
        if ($avatar) {
            $thumbnailPath = str_replace('avatars/', 'avatars/thumbnails/', $avatar);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                return Storage::url($thumbnailPath);
            }
        }

        // Return default avatar
        return asset('images/default-avatar.png');
    }

    /**
     * Check if profile is complete.
     */
    public function isProfileComplete(User $user): bool
    {
        $requiredFields = ['name', 'email', 'phone'];

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get profile completion percentage.
     * Uses existing DB field names: bank_account_no and bank_account_name
     */
    public function getProfileCompletionPercentage(User $user): int
    {
        $fields = [
            'name', 'email', 'phone', 'address', 'avatar',
            'date_of_birth', 'gender', 'emergency_contact_name'
        ];

        // Add bank details for technicians
        if ($user->hasRole('Technician')) {
            $fields = array_merge($fields, [
                'bank_name', 'bank_account_no', 'bank_account_name', 'ifsc_code'
            ]);
        }

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($user->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100);
    }
}
