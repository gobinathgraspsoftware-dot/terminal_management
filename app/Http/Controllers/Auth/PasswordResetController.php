<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Log the attempt
        activity()
            ->withProperties([
                'email' => $request->email,
                'ip' => $request->ip(),
            ])
            ->log('Password reset link requested');

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
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
     */
    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        // Reset the user's password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing tokens
                $user->tokens()->delete();

                // Log password reset
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                    ])
                    ->log('Password reset successfully');
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}