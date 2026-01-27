<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Regenerate session
        $request->session()->regenerate();

        // Create Sanctum token for API access
        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Store token in session (optional, for API usage)
        $request->session()->put('api_token', $token);

        // Log successful login
        activity()
            ->causedBy($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('User logged in');

        // Redirect based on user role
        return $this->redirectBasedOnRole($user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Log logout activity
        if ($user) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                ])
                ->log('User logged out');

            // Revoke all tokens for this user
            $user->tokens()->delete();
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
        // Get user's primary role
        $role = $user->roles->first()?->name;

        return match ($role) {
            'admin' => redirect()->intended('/admin/dashboard'),
            'supervisor' => redirect()->intended('/supervisor/dashboard'),
            'technician' => redirect()->intended('/technician/dashboard'),
            default => redirect()->intended('/dashboard'),
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