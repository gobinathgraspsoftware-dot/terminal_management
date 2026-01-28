<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController as AdminTeamController;
use App\Http\Controllers\Supervisor\TeamController as SupervisorTeamController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Supervisor\ProfileController as SupervisorProfileController;
use App\Http\Controllers\Technician\ProfileController as TechnicianProfileController;

// =====================================================
// PUBLIC ROUTES (Guest only)
// =====================================================
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

// =====================================================
// AUTHENTICATED ROUTES
// =====================================================
Route::middleware(['auth', 'active'])->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Unified Dashboard (automatically shows role-based dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Widget API Endpoint (for AJAX refresh)
    Route::get('/api/widget/data', [DashboardController::class, 'getWidgetData'])->name('api.widget.data');
});

// =====================================================
// ADMIN ROUTES
// =====================================================
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard (uses unified controller)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Management Routes
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {

        // Main CRUD Routes
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');

        // Restore Soft-Deleted User
        Route::post('/{id}/restore', 'restore')->name('restore');

        // Password Management
        Route::post('/{user}/change-password', 'changePassword')->name('change-password');

        // Role & Supervisor Assignment
        Route::post('/{user}/assign-role', 'assignRole')->name('assign-role');
        Route::post('/{user}/assign-supervisor', 'assignSupervisor')->name('assign-supervisor');

        // Status Management
        Route::post('/{user}/toggle-status', 'toggleStatus')->name('toggle-status');

        // AJAX Endpoints
        Route::get('/ajax/list', 'getUsersList')->name('ajax.list');

        // Bulk Operations
        Route::post('/bulk/delete', 'bulkDelete')->name('bulk.delete');

        // Export
        Route::get('/export', 'export')->name('export');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [AdminProfileController::class, 'index'])->name('index');
        Route::get('/edit', [AdminProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [AdminProfileController::class, 'update'])->name('update');

        Route::get('/password', [AdminProfileController::class, 'password'])->name('password');
        Route::put('/password', [AdminProfileController::class, 'updatePassword'])->name('password.update');

        Route::get('/avatar', [AdminProfileController::class, 'avatar'])->name('avatar');
        Route::put('/avatar', [AdminProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [AdminProfileController::class, 'deleteAvatar'])->name('avatar.delete');

        Route::get('/login-history', [AdminProfileController::class, 'loginHistory'])->name('login-history');
    });

    // Team Management (Admin)
    Route::controller(AdminTeamController::class)->prefix('teams')->name('teams.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::post('/assign', 'assign')->name('assign');
        Route::post('/bulk-assign', 'bulkAssign')->name('bulk-assign');
        Route::post('/{user}/remove', 'remove')->name('remove');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/stats', 'stats')->name('stats');
        Route::get('/ajax/supervisors', 'supervisorsList')->name('ajax.supervisors');
        Route::get('/ajax/independent', 'independentList')->name('ajax.independent');
    });

    // Settings (requires specific permission)
    Route::middleware(['permission:settings.edit'])->group(function () {
        Route::get('/settings', function () {
            return 'Settings - Admin with settings permission';
        })->name('settings.index');
    });
});

// =====================================================
// SUPERVISOR ROUTES
// =====================================================
Route::middleware(['supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    // Supervisor Dashboard (uses unified controller)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/{user}', 'show')->name('show');

        // Can only view, cannot create/edit/delete (unless given specific permissions)
    });

    // Team Management (Supervisor - Read Only)
    Route::controller(SupervisorTeamController::class)->prefix('teams')->name('teams.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/stats', 'stats')->name('stats');
        Route::get('/performance', 'performance')->name('performance');
        Route::get('/{user}', 'show')->name('show');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [SupervisorProfileController::class, 'index'])->name('index');
        Route::get('/edit', [SupervisorProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [SupervisorProfileController::class, 'update'])->name('update');

        Route::get('/password', [SupervisorProfileController::class, 'password'])->name('password');
        Route::put('/password', [SupervisorProfileController::class, 'updatePassword'])->name('password.update');

        Route::get('/avatar', [SupervisorProfileController::class, 'avatar'])->name('avatar');
        Route::put('/avatar', [SupervisorProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [SupervisorProfileController::class, 'deleteAvatar'])->name('avatar.delete');

        Route::get('/login-history', [SupervisorProfileController::class, 'loginHistory'])->name('login-history');
    });

    // Job Assignment (supervisor or admin)
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/jobs/assign', function () {
            return 'Assign Jobs - Admin or Supervisor';
        })->name('jobs.assign');
    });
});

// =====================================================
// TECHNICIAN ROUTES
// =====================================================
Route::middleware(['technician'])->prefix('technician')->name('technician.')->group(function () {
    // Technician Dashboard (uses unified controller)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [TechnicianProfileController::class, 'index'])->name('index');
        Route::get('/edit', [TechnicianProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [TechnicianProfileController::class, 'update'])->name('update');

        Route::get('/password', [TechnicianProfileController::class, 'password'])->name('password');
        Route::put('/password', [TechnicianProfileController::class, 'updatePassword'])->name('password.update');

        Route::get('/avatar', [TechnicianProfileController::class, 'avatar'])->name('avatar');
        Route::put('/avatar', [TechnicianProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [TechnicianProfileController::class, 'deleteAvatar'])->name('avatar.delete');

        Route::get('/bank-details', [TechnicianProfileController::class, 'bankDetails'])->name('bank-details');
        Route::put('/bank-details', [TechnicianProfileController::class, 'updateBankDetails'])->name('bank-details.update');

        Route::get('/login-history', [TechnicianProfileController::class, 'loginHistory'])->name('login-history');
    });

    // My Jobs
    Route::get('/jobs', function () {
        return 'My Jobs - Technician Only';
    })->name('jobs.index');

    // My Inventory
    Route::get('/inventory', function () {
        return 'My Inventory - Technician Only';
    })->name('inventory.index');

    // Claims
    Route::get('/claims', function () {
        return 'My Claims - Technician Only';
    })->name('claims.index');
});

// =====================================================
// MANAGEMENT ROUTES (Admin OR Supervisor)
// =====================================================
Route::middleware(['management'])->prefix('management')->name('management.')->group(function () {
    // Reports accessible by both admin and supervisor
    Route::get('/reports', function () {
        return 'Reports - Admin or Supervisor';
    })->name('reports.index');

    // Job overview
    Route::get('/jobs', function () {
        return 'All Jobs - Admin or Supervisor';
    })->name('jobs.index');
});

// =====================================================
// API ROUTES (with AJAX auth check)
// =====================================================
Route::middleware(['ajax.auth'])->prefix('api')->name('api.')->group(function () {
    // Check authentication status
    Route::get('/auth/check', function () {
        return response()->json([
            'authenticated' => true,
            'user' => auth()->user()->only(['id', 'name', 'email']),
            'role' => auth()->user()->roles->first()?->name,
        ]);
    })->name('auth.check');

    // Other AJAX endpoints...
});

// =====================================================
// EXAMPLE: MULTIPLE MIDDLEWARE
// =====================================================
Route::middleware(['auth', 'active', 'role:admin', 'permission:users.delete'])->group(function () {
    Route::delete('/users/{user}', function () {
        return 'Delete User - Requires Admin role AND users.delete permission';
    })->name('users.destroy');
});

// =====================================================
// EXAMPLE: INLINE MIDDLEWARE
// =====================================================
Route::get('/profile', function () {
    return 'User Profile';
})->middleware(['auth', 'active'])->name('profile.show');

// =====================================================
// FALLBACK ROUTES
// =====================================================
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// 404 handler
Route::fallback(function () {
    if (request()->expectsJson() || request()->ajax()) {
        return response()->json(['message' => 'Not Found'], 404);
    }
    return response()->view('errors.404', [], 404);
});
