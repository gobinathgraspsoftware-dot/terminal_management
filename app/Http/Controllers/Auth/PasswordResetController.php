<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * Display the forgot password form.
     */
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle forgot password request.
     *
     * Uses PasswordResetMail Mailable (same pattern as PurchaseOrderEmail).
     * Standard form POST → Redirect (NOT AJAX — global ajax.auth middleware blocks guest AJAX).
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return back()->withErrors([
                    'email' => 'We could not find an account with that email address.',
                ]);
            }

            // Create reset token via Laravel's Password broker
            $token = Password::broker()->createToken($user);

            // Build reset URL
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false));

            // Get expiration from config
            $expirationMinutes = config(
                'auth.passwords.' . config('auth.defaults.passwords') . '.expire',
                60
            );

            // Send branded email using PasswordResetMail Mailable
            // (same pattern as PurchaseOrderEmail / GrnPostedEmail)
            Mail::to($user->email)->send(
                new PasswordResetMail($user, $resetUrl, $expirationMinutes)
            );

            // Log the attempt safely
            $this->logActivity('Password reset link requested', null, [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);

            return back()->with('status', 'We have emailed your password reset link! Please check your inbox.');

        } catch (\Exception $e) {
            Log::error('Password reset link failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to send password reset link. Please try again later or contact the administrator.',
            ]);
        }
    }

    /**
     * Display the password reset form.
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset.
     *
     * Standard form POST → Redirect (NOT AJAX — global ajax.auth middleware blocks guest AJAX).
     */
    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) use ($request) {
                    $user->forceFill([
                        'password'       => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    // Revoke all existing Sanctum tokens
                    $user->tokens()->delete();

                    // Log password reset safely
                    $this->logActivity('Password reset successfully', $user, [
                        'ip' => $request->ip(),
                    ]);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return redirect()->route('login')->with(
                    'status',
                    'Your password has been reset successfully! Please login with your new password.'
                );
            }

            // Token invalid/expired or other error
            return back()->withErrors([
                'email' => $this->getResetErrorMessage($status),
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to reset password. Please try again later.',
            ]);
        }
    }

    /**
     * Get human-readable reset error message.
     */
    protected function getResetErrorMessage(string $status): string
    {
        $messages = [
            Password::INVALID_TOKEN    => 'This password reset link has expired or is invalid. Please request a new one.',
            Password::INVALID_USER     => 'We could not find an account with that email address.',
            Password::RESET_THROTTLED  => 'Please wait before requesting another reset link.',
        ];

        return $messages[$status] ?? __($status);
    }

    /**
     * Safely log activity.
     *
     * Uses spatie/activity-log if available, falls back to Laravel Log.
     * Never breaks the main flow even if logging fails.
     */
    protected function logActivity(string $description, $causer = null, array $properties = []): void
    {
        try {
            if (function_exists('activity')) {
                $logger = activity()->withProperties($properties);
                if ($causer) {
                    $logger->causedBy($causer);
                }
                $logger->log($description);
            } else {
                Log::info($description, $properties);
            }
        } catch (\Exception $e) {
            Log::warning('Activity logging failed: ' . $e->getMessage());
        }
    }
}
