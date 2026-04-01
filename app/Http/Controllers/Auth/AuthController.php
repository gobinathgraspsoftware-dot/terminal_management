<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // Ensure the login request is not rate limited
        $this->ensureIsNotRateLimited($request);

        // Attempt to authenticate the user
        if (!Auth::attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            // Increment rate limiter on failed attempt
            RateLimiter::hit($this->throttleKey($request));

            // ════════════════════════════════════════════════════════
            // FIX: Record FAILED login attempt in LoginHistory
            // ════════════════════════════════════════════════════════
            try {
                $failedUser = \App\Models\User::where('email', $request->input('email'))->first();
                if ($failedUser) {
                    LoginHistory::recordLogin(
                        $failedUser->id,
                        $request->ip(),
                        $request->userAgent(),
                        'failed'
                    );
                }
            } catch (\Exception $e) {
                Log::warning('Failed login history recording: ' . $e->getMessage());
            }

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        // Check if user is active
        $user = Auth::user();
        if ($user->status !== 'active') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('Your account has been suspended. Please contact administrator.'),
            ]);
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($this->throttleKey($request));

        // Update last login timestamp
        $user->update(['last_login_at' => now()]);

        // ════════════════════════════════════════════════════════
        // FIX: Record SUCCESSFUL login in LoginHistory
        // ════════════════════════════════════════════════════════
        try {
            $loginRecord = LoginHistory::recordLogin(
                $user->id,
                $request->ip(),
                $request->userAgent(),
                'success'
            );
        } catch (\Exception $e) {
            Log::warning('Login history recording failed: ' . $e->getMessage());
        }

        // Regenerate session
        $request->session()->regenerate();

        // Store login history ID in session (after regenerate to ensure persistence)
        if (isset($loginRecord)) {
            $request->session()->put('login_history_id', $loginRecord->id);
        }

        // ============================================================
        // FIX #1: Clean up old Sanctum tokens BEFORE creating new one
        // ============================================================
        try {
            $user->tokens()->delete();
        } catch (\Exception $e) {
            Log::warning('Token cleanup failed: ' . $e->getMessage());
        }

        // Create Sanctum token for API access
        try {
            $token = $user->createToken('auth-token')->plainTextToken;
            $request->session()->put('api_token', $token);
        } catch (\Exception $e) {
            Log::warning('Sanctum token creation failed: ' . $e->getMessage());
        }

        // ============================================================
        // FIX #2: Wrap activity log in try-catch
        // ============================================================
        try {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('User logged in');
        } catch (\Exception $e) {
            Log::warning('Activity log failed on login: ' . $e->getMessage());
        }

        // Redirect based on user role
        return $this->redirectBasedOnRole($user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            // ════════════════════════════════════════════════════════
            // FIX: Record logout + calculate session duration
            // ════════════════════════════════════════════════════════
            try {
                $loginHistoryId = $request->session()->get('login_history_id');
                if ($loginHistoryId) {
                    $loginRecord = LoginHistory::find($loginHistoryId);
                    if ($loginRecord) {
                        $loginRecord->recordLogout();
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Logout history recording failed: ' . $e->getMessage());
            }

            // Log logout activity
            try {
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                    ])
                    ->log('User logged out');
            } catch (\Exception $e) {
                Log::warning('Activity log failed on logout: ' . $e->getMessage());
            }

            // Revoke all tokens for this user
            try {
                $user->tokens()->delete();
            } catch (\Exception $e) {
                Log::warning('Token cleanup failed on logout: ' . $e->getMessage());
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('status', 'You have been successfully logged out.');
    }

    /**
     * Redirect user based on their role.
     */
    protected function redirectBasedOnRole($user): RedirectResponse
    {
        $role = $user->roles->first()?->name;

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'technician' => redirect()->route('technician.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->input('email')) . '|' . $request->ip()
        );
    }
}
