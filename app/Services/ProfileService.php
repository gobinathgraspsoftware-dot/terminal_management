<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * Update user avatar with comprehensive debugging.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        try {
            Log::info('=== AVATAR UPLOAD START ===', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'current_avatar' => $user->avatar,
                'file_original_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'file_extension' => $file->getClientOriginalExtension(),
            ]);

            // Step 1: Delete old avatar if exists
            if ($user->avatar) {
                Log::info('Deleting old avatar', ['old_avatar' => $user->avatar]);

                if (Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                    Log::info('Old avatar deleted successfully');
                } else {
                    Log::warning('Old avatar file does not exist', ['path' => $user->avatar]);
                }

                // Delete old thumbnail
                $oldThumbnail = str_replace('avatars/', 'avatars/thumbnails/', $user->avatar);
                if (Storage::disk('public')->exists($oldThumbnail)) {
                    Storage::disk('public')->delete($oldThumbnail);
                    Log::info('Old thumbnail deleted');
                }
            }

            // Step 2: Create directory if not exists
            $directory = 'avatars';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
                Log::info('Created avatars directory');
            }

            // Step 3: Generate unique filename
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $path = $directory . '/' . $filename;

            Log::info('Generated filename', [
                'filename' => $filename,
                'full_path' => $path,
                'storage_path' => storage_path('app/public/' . $path)
            ]);

            // Step 4: Store the file
            Log::info('Attempting to store file...');
            $stored = $file->storeAs($directory, $filename, 'public');

            if (!$stored) {
                Log::error('Failed to store file');
                throw new \Exception('Failed to store avatar file');
            }

            Log::info('File stored successfully', ['stored_path' => $stored]);

            // Step 5: Verify file was stored
            $storedPath = storage_path('app/public/' . $path);
            if (!file_exists($storedPath)) {
                Log::error('File was not found after storage', ['path' => $storedPath]);
                throw new \Exception('Avatar file was not saved to storage');
            }

            $fileSize = filesize($storedPath);
            Log::info('File verified on disk', [
                'path' => $storedPath,
                'size' => $fileSize,
                'exists' => file_exists($storedPath)
            ]);

            // Step 6: Create thumbnail
            try {
                $this->createThumbnail($file, $directory, $filename);
                Log::info('Thumbnail created successfully');
            } catch (\Exception $e) {
                Log::warning('Failed to create thumbnail', ['error' => $e->getMessage()]);
                // Continue even if thumbnail fails
            }

            // Step 7: Update database using DB facade for better debugging
            Log::info('Updating database...', [
                'user_id' => $user->id,
                'new_avatar_path' => $path
            ]);

            DB::beginTransaction();
            try {
                // Update using query builder for explicit control
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'avatar' => $path,
                        'updated_at' => now()
                    ]);

                DB::commit();
                Log::info('Database updated successfully via DB facade');
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Database update failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Failed to update avatar in database: ' . $e->getMessage());
            }

            // Step 8: Verify database update
            $updatedUser = DB::table('users')->where('id', $user->id)->first();
            Log::info('Database verification', [
                'user_id' => $user->id,
                'avatar_in_db' => $updatedUser->avatar,
                'updated_at' => $updatedUser->updated_at
            ]);

            if ($updatedUser->avatar !== $path) {
                Log::error('Database update verification failed', [
                    'expected' => $path,
                    'got' => $updatedUser->avatar
                ]);
                throw new \Exception('Avatar path mismatch in database');
            }

            // Step 9: Refresh the user model
            $user->refresh();
            Log::info('User model refreshed', ['user_avatar' => $user->avatar]);

            Log::info('=== AVATAR UPLOAD SUCCESS ===', [
                'user_id' => $user->id,
                'avatar_path' => $path,
                'file_size' => $fileSize,
                'db_updated' => true
            ]);

            return $path;

        } catch (\Exception $e) {
            Log::error('=== AVATAR UPLOAD FAILED ===', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            throw $e;
        }
    }

    /**
     * Create thumbnail for avatar.
     */
    private function createThumbnail(UploadedFile $file, string $directory, string $filename): void
    {
        $thumbnailDir = $directory . '/thumbnails';

        if (!Storage::disk('public')->exists($thumbnailDir)) {
            Storage::disk('public')->makeDirectory($thumbnailDir);
            Log::info('Created thumbnails directory');
        }

        $thumbnailPath = storage_path('app/public/' . $thumbnailDir . '/' . $filename);

        // Simple resize without intervention/image package
        // Just copy the file for now - can be enhanced with image processing library
        if (copy($file->getRealPath(), $thumbnailPath)) {
            Log::info('Thumbnail file copied', ['path' => $thumbnailPath]);
        } else {
            throw new \Exception('Failed to create thumbnail');
        }
    }

    /**
     * Delete user avatar.
     */
    public function deleteAvatar(User $user): void
    {
        if ($user->avatar) {
            Log::info('Deleting avatar', [
                'user_id' => $user->id,
                'avatar' => $user->avatar
            ]);

            Storage::disk('public')->delete($user->avatar);

            // Delete thumbnail
            $thumbnailPath = str_replace('avatars/', 'avatars/thumbnails/', $user->avatar);
            Storage::disk('public')->delete($thumbnailPath);

            $user->update(['avatar' => null]);

            Log::info('Avatar deleted successfully');
        }
    }

    /**
     * Update bank details (for technicians).
     */
    public function updateBankDetails(User $user, array $data): User
    {
        $user->update([
            'bank_name' => $data['bank_name'],
            'bank_account_no' => $data['bank_account_no'],
            'bank_account_name' => $data['bank_account_name'],
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
