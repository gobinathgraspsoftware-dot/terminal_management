<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoginHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProfileService
{
    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
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
     * NOTE: Model has 'password' => 'hashed' cast, but ProfileService is called
     * from ProfileController which explicitly wants Hash::make() for the password
     * change flow (not going through mass assignment). Keep Hash::make() here since
     * we're using $user->update() with an already-validated password.
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        // Use DB::table to bypass model cast and manually hash
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($newPassword),
                'updated_at' => now(),
            ]);
    }

    /**
     * Update user avatar — cPanel compatible (PERMANENT FIX)
     *
     * CRITICAL: On cPanel, public_path() ≠ $_SERVER['DOCUMENT_ROOT']
     * public_path() → /home/user/project/public (NOT web-accessible)
     * DOCUMENT_ROOT → /home/user/public_html (the actual web root)
     *
     * Uses $_SERVER['DOCUMENT_ROOT'] for file placement.
     * Uses asset('storage/...') for URL generation without file_exists checks.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        try {
            Log::info('=== AVATAR UPLOAD START ===', [
                'user_id' => $user->id,
                'current_avatar' => $user->avatar,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_mime' => $file->getMimeType(),
                'document_root' => $_SERVER['DOCUMENT_ROOT'],
                'public_path' => public_path(),
            ]);

            // Step 1: Delete old avatar if exists
            if ($user->avatar) {
                $this->deleteAvatarFile($user->avatar);
            }

            // Step 2: Determine target directory using DOCUMENT_ROOT
            $directory = 'avatars';
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $directory;

            // Create directory if not exists
            if (!File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
                Log::info('Created avatars directory', ['path' => $targetDir]);
            }

            // Step 3: Generate unique filename
            $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $relativePath = $directory . '/' . $filename;

            // Step 4: Move file directly to DOCUMENT_ROOT/storage/avatars/
            $file->move($targetDir, $filename);

            // Step 5: Verify file exists
            $fullPath = $targetDir . '/' . $filename;
            if (!file_exists($fullPath)) {
                Log::error('Avatar file not found after move', ['path' => $fullPath]);
                throw new \Exception('Failed to save avatar file');
            }

            Log::info('Avatar file moved successfully', [
                'full_path' => $fullPath,
                'file_size' => filesize($fullPath),
            ]);

            // Step 6: Update database
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'avatar' => $relativePath,
                    'updated_at' => now(),
                ]);

            // Step 7: Verify DB update
            $user->refresh();

            Log::info('=== AVATAR UPLOAD SUCCESS ===', [
                'user_id' => $user->id,
                'avatar_path' => $relativePath,
                'db_avatar' => $user->avatar,
            ]);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('=== AVATAR UPLOAD FAILED ===', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
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
                'avatar' => $user->avatar,
            ]);

            $this->deleteAvatarFile($user->avatar);

            $user->update(['avatar' => null]);

            Log::info('Avatar deleted and DB cleared');
        }
    }

    /**
     * Delete avatar file from disk — checks all possible locations
     * (DOCUMENT_ROOT, public_path, storage disk)
     */
    private function deleteAvatarFile(string $path): void
    {
        // 1. Check DOCUMENT_ROOT (cPanel live server)
        $docRootFile = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $path;
        if (file_exists($docRootFile)) {
            unlink($docRootFile);
            Log::info('Avatar deleted from DOCUMENT_ROOT', ['path' => $docRootFile]);
            return;
        }

        // 2. Check public_path (local dev / XAMPP)
        $publicFile = public_path('storage/' . $path);
        if (file_exists($publicFile)) {
            unlink($publicFile);
            Log::info('Avatar deleted from public_path', ['path' => $publicFile]);
            return;
        }

        // 3. Fallback: storage disk (very old uploads via storeAs)
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            Log::info('Avatar deleted from storage disk', ['path' => $path]);
            return;
        }

        Log::warning('Avatar file not found in any location', ['path' => $path]);
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
            'ifsc_code' => $data['ifsc_code'] ?? null,
            'branch_name' => $data['branch_name'] ?? null,
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
        if ($avatar) {
            return asset('storage/' . $avatar);
        }

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
        if ($user->hasRole('technician')) {
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
