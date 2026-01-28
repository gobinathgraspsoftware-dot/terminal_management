<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $loginHistory = $this->profileService->getLoginHistory($user, 10);
        $loginStats = $this->profileService->getLoginStatistics($user);
        $completionPercentage = $this->profileService->getProfileCompletionPercentage($user);

        return view('supervisor.profile.index', compact(
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
        return view('supervisor.profile.edit', compact('user'));
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
                ->route('supervisor.profile.index')
                ->with('success', 'Profile updated successfully.');
        } catch (\Exception $e) {
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
        return view('supervisor.profile.password');
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
                ->route('supervisor.profile.index')
                ->with('success', 'Password changed successfully.');
        } catch (\Exception $e) {
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
        return view('supervisor.profile.avatar', compact('user'));
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(UpdateAvatarRequest $request)
    {
        try {
            $user = Auth::user();
            $this->profileService->updateAvatar($user, $request->file('avatar'));

            return redirect()
                ->route('supervisor.profile.index')
                ->with('success', 'Avatar updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update avatar: ' . $e->getMessage());
        }
    }

    /**
     * Delete the user's avatar.
     */
    public function deleteAvatar()
    {
        try {
            $user = Auth::user();
            $this->profileService->deleteAvatar($user);

            return redirect()
                ->route('supervisor.profile.index')
                ->with('success', 'Avatar deleted successfully.');
        } catch (\Exception $e) {
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
