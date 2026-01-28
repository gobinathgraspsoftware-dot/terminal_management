<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display the user's profile.
     */
    public function index()
    {
        $user = Auth::user();
        $user->refresh(); // Ensure we have latest data including avatar

        $loginHistory = $this->profileService->getLoginHistory($user, 10);
        $loginStats = $this->profileService->getLoginStatistics($user);
        $completionPercentage = $this->profileService->getProfileCompletionPercentage($user);

        return view('admin.profile.index', compact(
            'user',
            'loginHistory',
            'loginStats',
            'completionPercentage'
        ));
    }

    /**
     * Show the form for editing the profile.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('admin.profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = Auth::user();
            $this->profileService->updateProfile($user, $request->validated());

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for changing password.
     */
    public function password()
    {
        return view('admin.profile.password');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            $user = Auth::user();
            $this->profileService->updatePassword($user, $request->password);

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Password changed successfully.');
        } catch (\Exception $e) {
            Log::error('Password update failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to change password: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for updating avatar.
     */
    public function avatar()
    {
        $user = Auth::user();
        $user->refresh();
        return view('admin.profile.avatar', compact('user'));
    }

    /**
     * Update the user's avatar with comprehensive debugging.
     */
    public function updateAvatar(UpdateAvatarRequest $request)
    {
        Log::info('====== AVATAR UPLOAD REQUEST RECEIVED ======');

        try {
            $user = Auth::user();

            // Log request details
            Log::info('Avatar upload request details', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'current_avatar' => $user->avatar,
                'request_method' => $request->method(),
                'request_has_file' => $request->hasFile('avatar'),
                'request_file_valid' => $request->file('avatar') ? $request->file('avatar')->isValid() : false,
            ]);

            // Check if file exists in request
            if (!$request->hasFile('avatar')) {
                Log::error('No file in request');
                return redirect()
                    ->back()
                    ->with('error', 'No file was uploaded. Please select an image file.');
            }

            $file = $request->file('avatar');

            // Validate file
            if (!$file->isValid()) {
                Log::error('Invalid file uploaded', [
                    'error' => $file->getError(),
                    'error_message' => $file->getErrorMessage()
                ]);
                return redirect()
                    ->back()
                    ->with('error', 'Invalid file: ' . $file->getErrorMessage());
            }

            Log::info('File validation passed', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'extension' => $file->getClientOriginalExtension()
            ]);

            // Call service to upload avatar
            Log::info('Calling ProfileService::updateAvatar...');
            $path = $this->profileService->updateAvatar($user, $file);
            Log::info('ProfileService::updateAvatar completed', ['returned_path' => $path]);

            // Refresh user model to get updated avatar from database
            $user->refresh();

            Log::info('User model refreshed', [
                'user_id' => $user->id,
                'avatar_after_refresh' => $user->avatar,
                'updated_at' => $user->updated_at
            ]);

            // Verify file exists
            $fullPath = storage_path('app/public/' . $path);
            $fileExists = file_exists($fullPath);

            Log::info('Final verification', [
                'path' => $path,
                'full_path' => $fullPath,
                'file_exists' => $fileExists,
                'file_size' => $fileExists ? filesize($fullPath) : 0,
                'user_avatar_in_model' => $user->avatar
            ]);

            if (!$fileExists) {
                Log::error('File does not exist after upload!', ['path' => $fullPath]);
                return redirect()
                    ->back()
                    ->with('error', 'Avatar file was uploaded but could not be found on disk.');
            }

            Log::info('====== AVATAR UPLOAD COMPLETED SUCCESSFULLY ======');

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Avatar updated successfully. Path: ' . $path);

        } catch (\Exception $e) {
            Log::error('====== AVATAR UPLOAD EXCEPTION ======', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to update avatar: ' . $e->getMessage() . ' (Check logs for details)');
        }
    }

    /**
     * Delete the user's avatar.
     */
    public function deleteAvatar()
    {
        try {
            $user = Auth::user();

            Log::info('Avatar deletion started', [
                'user_id' => $user->id,
                'avatar' => $user->avatar
            ]);

            $this->profileService->deleteAvatar($user);

            // Refresh user model
            $user->refresh();

            Log::info('Avatar deleted successfully', [
                'user_id' => $user->id,
                'avatar_after_delete' => $user->avatar
            ]);

            return redirect()
                ->route('admin.profile.index')
                ->with('success', 'Avatar deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Avatar deletion failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to delete avatar: ' . $e->getMessage());
        }
    }

    /**
     * Get login history via AJAX.
     */
    public function loginHistory(Request $request)
    {
        $user = Auth::user();
        $limit = $request->input('limit', 20);

        $loginHistory = $this->profileService->getLoginHistory($user, $limit);

        return response()->json([
            'data' => $loginHistory,
            'stats' => $this->profileService->getLoginStatistics($user)
        ]);
    }
}
