<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Here is where you can register authentication routes for the TMS application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group.
|
*/

// Guest routes (only accessible when not authenticated)
Route::middleware('guest')->group(function () {
    
    // Login routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])
        ->name('login');
    
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.post');

    // Password reset routes
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

    // Email verification routes (if using email verification)
    Route::get('/verify-email', function () {
        return view('auth.verify-email');
    })->middleware('auth')->name('verification.notice');

    Route::get('/verify-email/{id}/{hash}', function (
        \Illuminate\Foundation\Auth\EmailVerificationRequest $request
    ) {
        $request->fulfill();
        return redirect('/dashboard')->with('status', 'Email verified successfully!');
    })->middleware(['auth', 'signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (
        \Illuminate\Http\Request $request
    ) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['auth', 'throttle:6,1'])->name('verification.send');
});

// Redirect root to login
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        $role = $user->roles->first()?->name;
        
        return match ($role) {
            'admin' => redirect('/admin/dashboard'),
            'supervisor' => redirect('/supervisor/dashboard'),
            'technician' => redirect('/technician/dashboard'),
            default => redirect('/dashboard'),
        };
    }
    
    return redirect('/login');
});