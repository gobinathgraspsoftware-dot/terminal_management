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
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Supervisor\PartnerController as SupervisorPartnerController;
use App\Http\Controllers\Technician\PartnerController as TechnicianPartnerController;
use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Supervisor\ClientController as SupervisorClientController;
use App\Http\Controllers\Technician\ClientController as TechnicianClientController;
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\SiteContactController as AdminSiteContactController;
use App\Http\Controllers\Supervisor\SiteController as SupervisorSiteController;
use App\Http\Controllers\Technician\SiteController as TechnicianSiteController;
use App\Http\Controllers\Admin\TerminalCategoryController as AdminTerminalCategoryController;
use App\Http\Controllers\Supervisor\TerminalCategoryController as SupervisorTerminalCategoryController;
use App\Http\Controllers\Technician\TerminalCategoryController as TechnicianTerminalCategoryController;
use App\Http\Controllers\Admin\TerminalModelController as AdminTerminalModelController;
use App\Http\Controllers\Supervisor\TerminalModelController as SupervisorTerminalModelController;
use App\Http\Controllers\Technician\TerminalModelController as TechnicianTerminalModelController;
use App\Http\Controllers\Admin\ChargeCatalogController as AdminChargeCatalogController;
use App\Http\Controllers\Supervisor\ChargeCatalogController as SupervisorChargeCatalogController;
use App\Http\Controllers\Technician\ChargeCatalogController as TechnicianChargeCatalogController;
use App\Http\Controllers\Admin\RateCardController as AdminRateCardController;
use App\Http\Controllers\Supervisor\RateCardController as SupervisorRateCardController;
use App\Http\Controllers\Technician\RateCardController as TechnicianRateCardController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (Guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/widget/data', [DashboardController::class, 'getWidgetData'])->name('api.widget.data');
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    /* Admin Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* User Management Routes */
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

    /* Profile Management Routes */
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

    /* Team Management Routes */
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

    /* Role Management Routes */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::get('/{role}/details', [RoleController::class, 'getDetails'])->name('details');
        Route::post('/{role}/update-permissions', [RoleController::class, 'updatePermissions'])->name('update-permissions');
        Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('clone');
    });

    /* Permission Management Routes */
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('/matrix', [PermissionController::class, 'matrix'])->name('matrix');
        Route::post('/update-matrix', [PermissionController::class, 'updateMatrix'])->name('update-matrix');
        Route::post('/bulk-update', [PermissionController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/export-matrix', [PermissionController::class, 'exportMatrix'])->name('export-matrix');
        Route::get('/{permission}/details', [PermissionController::class, 'getDetails'])->name('details');
        Route::get('/group/{group}', [PermissionController::class, 'getByGroup'])->name('by-group');
    });

    /* Partners Management Routes */
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/datatable', [AdminPartnerController::class, 'datatable'])->name('datatable');
        Route::get('/export', [AdminPartnerController::class, 'export'])->name('export');
        Route::post('/import', [AdminPartnerController::class, 'import'])->name('import');
        Route::get('/import-template', [AdminPartnerController::class, 'importTemplate'])->name('import-template');
        Route::post('/{partner}/restore', [AdminPartnerController::class, 'restore'])->name('restore')->withTrashed();
        Route::post('/{partner}/toggle-status', [AdminPartnerController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{partner}/regenerate-api-key', [AdminPartnerController::class, 'regenerateApiKey'])->name('regenerate-api-key');
        Route::get('/ajax/list', [AdminPartnerController::class, 'getList'])->name('ajax.list');
        Route::get('/', [AdminPartnerController::class, 'index'])->name('index');
        Route::get('/create', [AdminPartnerController::class, 'create'])->name('create');
        Route::post('/', [AdminPartnerController::class, 'store'])->name('store');
        Route::get('/{partner}', [AdminPartnerController::class, 'show'])->name('show')->withTrashed();
        Route::get('/{partner}/edit', [AdminPartnerController::class, 'edit'])->name('edit');
        Route::put('/{partner}', [AdminPartnerController::class, 'update'])->name('update');
        Route::delete('/{partner}', [AdminPartnerController::class, 'destroy'])->name('destroy');
    });

    /* Client Management Routes */
    Route::prefix('clients')->name('clients.')->group(function () {
        // Main CRUD
        Route::get('/', [AdminClientController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminClientController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminClientController::class, 'create'])->name('create');
        Route::post('/', [AdminClientController::class, 'store'])->name('store');
        Route::get('/{client}', [AdminClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [AdminClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [AdminClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [AdminClientController::class, 'destroy'])->name('destroy');

        // Additional Actions
        Route::post('/{client}/restore', [AdminClientController::class, 'restore'])->name('restore');
        Route::post('/{client}/toggle-status', [AdminClientController::class, 'toggleStatus'])->name('toggle-status');

        // Contact Management
        Route::post('/{client}/contacts', [AdminClientController::class, 'addContact'])->name('contacts.store');
        Route::put('/{client}/contacts/{contact}', [AdminClientController::class, 'updateContact'])->name('contacts.update');
        Route::delete('/{client}/contacts/{contact}', [AdminClientController::class, 'removeContact'])->name('contacts.destroy');
        Route::post('/{client}/contacts/{contact}/set-primary', [AdminClientController::class, 'setPrimaryContact'])->name('contacts.set-primary');
        Route::get('/ajax/list', [AdminClientController::class, 'getList'])->name('ajax.list');
    });


    /* Vendors Management Routes */
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [AdminVendorController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminVendorController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminVendorController::class, 'create'])->name('create');
        Route::post('/', [AdminVendorController::class, 'store'])->name('store');
        Route::get('/{vendor}', [AdminVendorController::class, 'show'])->name('show');
        Route::get('/{vendor}/edit', [AdminVendorController::class, 'edit'])->name('edit');
        Route::put('/{vendor}', [AdminVendorController::class, 'update'])->name('update');
        Route::delete('/{vendor}', [AdminVendorController::class, 'destroy'])->name('destroy');
        Route::post('/{vendor}/restore', [AdminVendorController::class, 'restore'])->name('restore');
        Route::post('/{vendor}/toggle-status', [AdminVendorController::class, 'toggleStatus'])->name('toggle-status');

        // Import/Export
        Route::get('/export/excel', [AdminVendorController::class, 'export'])->name('export');
        Route::post('/import/excel', [AdminVendorController::class, 'import'])->name('import');
        Route::get('/import/template', [AdminVendorController::class, 'importTemplate'])->name('import-template');

        // AJAX
        Route::get('/api/list', [AdminVendorController::class, 'getList'])->name('api.list');
    });

    /* Sites Management Routes */
    Route::prefix('sites')->name('sites.')->group(function () {
        // Main CRUD
        Route::get('/', [AdminSiteController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminSiteController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminSiteController::class, 'create'])->name('create');
        Route::post('/', [AdminSiteController::class, 'store'])->name('store');
        Route::get('/{site}', [AdminSiteController::class, 'show'])->name('show');
        Route::get('/{site}/edit', [AdminSiteController::class, 'edit'])->name('edit');
        Route::put('/{site}', [AdminSiteController::class, 'update'])->name('update');
        Route::delete('/{site}', [AdminSiteController::class, 'destroy'])->name('destroy');
        Route::post('/{siteId}/restore', [AdminSiteController::class, 'restore'])->name('restore');

        // Export & GPS
        Route::get('/export', [AdminSiteController::class, 'export'])->name('export');
        Route::post('/capture-gps', [AdminSiteController::class, 'captureGps'])->name('capture-gps');

        /* Site Contacts Management Routes */
        Route::prefix('{site}/contacts')->name('contacts.')->group(function () {
            Route::get('/', [AdminSiteContactController::class, 'index'])->name('index');
            Route::post('/', [AdminSiteContactController::class, 'store'])->name('store');
            Route::get('/{contact}', [AdminSiteContactController::class, 'show'])->name('show');
            Route::put('/{contact}', [AdminSiteContactController::class, 'update'])->name('update');
            Route::delete('/{contact}', [AdminSiteContactController::class, 'destroy'])->name('destroy');
            Route::post('/{contact}/set-primary', [AdminSiteContactController::class, 'setPrimary'])->name('set-primary');
        });
    });

    /* Terminal categories routes */
    Route::prefix('terminal-categories')->name('terminal-categories.')->group(function () {
        Route::get('/', [AdminTerminalCategoryController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminTerminalCategoryController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminTerminalCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminTerminalCategoryController::class, 'store'])->name('store');
        Route::get('/{terminalCategory}/edit', [AdminTerminalCategoryController::class, 'edit'])->name('edit');
        Route::put('/{terminalCategory}', [AdminTerminalCategoryController::class, 'update'])->name('update');
        Route::delete('/{terminalCategory}', [AdminTerminalCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{terminalCategory}/toggle-status', [AdminTerminalCategoryController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{terminalCategory}/toggle-serial-tracking', [AdminTerminalCategoryController::class, 'toggleSerialTracking'])->name('toggle-serial-tracking');
        Route::post('/update-sort-order', [AdminTerminalCategoryController::class, 'updateSortOrder'])->name('update-sort-order');
    });

    /* Terminal model routes */
    Route::prefix('terminal-models')->name('terminal-models.')->group(function () {
        Route::get('/datatable', [AdminTerminalModelController::class, 'datatable'])->name('datatable');
        Route::post('/toggle-status/{terminalModel}', [AdminTerminalModelController::class, 'toggleStatus'])->name('toggle-status');
        Route::delete('/{terminalModel}/delete-image', [AdminTerminalModelController::class, 'deleteImage'])->name('delete-image');
        Route::get('/export', [AdminTerminalModelController::class, 'export'])->name('export');
        Route::post('/import', [AdminTerminalModelController::class, 'import'])->name('import');
        Route::resource('', AdminTerminalModelController::class)->parameters(['' => 'terminalModel']);
    });

    /* Charge Catalog Routes */
    Route::prefix('charge-catalog')->name('charge-catalog.')->group(function () {
        Route::get('/', [AdminChargeCatalogController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminChargeCatalogController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminChargeCatalogController::class, 'create'])->name('create');
        Route::post('/', [AdminChargeCatalogController::class, 'store'])->name('store');
        Route::get('/{chargeCatalog}/edit', [AdminChargeCatalogController::class, 'edit'])->name('edit');
        Route::put('/{chargeCatalog}', [AdminChargeCatalogController::class, 'update'])->name('update');
        Route::delete('/{chargeCatalog}', [AdminChargeCatalogController::class, 'destroy'])->name('destroy');
        Route::post('/{chargeCatalog}/toggle-status', [AdminChargeCatalogController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/search', [AdminChargeCatalogController::class, 'searchCharges'])->name('search');
    });

    /* Charge Catalog Routes */
    Route::prefix('rate-cards')->name('rate-cards.')->group(function () {
        Route::get('/', [AdminRateCardController::class, 'index'])->name('index');
        Route::get('/create', [AdminRateCardController::class, 'create'])->name('create');
        Route::post('/', [AdminRateCardController::class, 'store'])->name('store');
        Route::get('/{rate_card}', [AdminRateCardController::class, 'show'])->name('show');
        Route::get('/{rate_card}/edit', [AdminRateCardController::class, 'edit'])->name('edit');
        Route::put('/{rate_card}', [AdminRateCardController::class, 'update'])->name('update');
        Route::delete('/{rate_card}', [AdminRateCardController::class, 'destroy'])->name('destroy');
        Route::post('/calculate-preview', [AdminRateCardController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/find-applicable', [AdminRateCardController::class, 'findApplicableRate'])->name('find-applicable');
        Route::get('/export', [AdminRateCardController::class, 'export'])->name('export');
        Route::post('/{rate_card}/toggle-status', [AdminRateCardController::class, 'toggle-status'])->name('toggle-status');
    });


    /* Settings (requires specific permission) */
    Route::middleware(['permission:settings.edit'])->group(function () {
        Route::get('/settings', function () {
            return 'Settings - Admin with settings permission';
        })->name('settings.index');
    });
});

/*
|--------------------------------------------------------------------------
| SUPERVISOR ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['supervisor'])->prefix('supervisor')->name('supervisor.')->group(function () {
    /* Supervisor Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* User Management Routes */
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/{user}', 'show')->name('show');
    });

    /* Team Management Routes */
    Route::controller(SupervisorTeamController::class)->prefix('teams')->name('teams.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/stats', 'stats')->name('stats');
        Route::get('/performance', 'performance')->name('performance');
        Route::get('/{user}', 'show')->name('show');
    });

    /* Profile Management Routes */
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

    /* Partners Management Routes */
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/datatable', [SupervisorPartnerController::class, 'datatable'])->name('datatable');
        Route::get('/ajax/list', [SupervisorPartnerController::class, 'getList'])->name('ajax.list');
        Route::get('/', [SupervisorPartnerController::class, 'index'])->name('index');
        Route::get('/{partner}', [SupervisorPartnerController::class, 'show'])->name('show');
    });

    /* Client Routes */
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [SupervisorClientController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorClientController::class, 'datatable'])->name('datatable');
        Route::get('/{client}', [SupervisorClientController::class, 'show'])->name('show');
        Route::get('/ajax/list', [SupervisorClientController::class, 'getList'])->name('ajax.list');
    });

    /* Sites management Routes */
    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [SupervisorSiteController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorSiteController::class, 'datatable'])->name('datatable');
        Route::get('/{site}', [SupervisorSiteController::class, 'show'])->name('show');
    });

    /* Terminal categories Management Routes */
    Route::prefix('terminal-categories')->name('terminal-categories.')->group(function () {
        Route::get('/', [SupervisorTerminalCategoryController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorTerminalCategoryController::class, 'datatable'])->name('datatable');
    });

    /* Terminal Model Routes */
    Route::prefix('terminal-models')->name('terminal-models.')->group(function () {
        Route::get('/datatable', [SupervisorTerminalModelController::class, 'datatable'])->name('datatable');
        Route::get('/', [SupervisorTerminalModelController::class, 'index'])->name('index');
        Route::get('/{terminalModel}', [SupervisorTerminalModelController::class, 'show'])->name('show');
    });

    /* Charge Catalog Routes */
    Route::prefix('charge-catalog')->name('charge-catalog.')->group(function () {
        Route::get('/', [SupervisorChargeCatalogController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorChargeCatalogController::class, 'datatable'])->name('datatable');

        // AJAX endpoint for quotations
        Route::get('/search', [SupervisorChargeCatalogController::class, 'searchCharges'])->name('search');
    });

    /* Rate Cards Routes */
    Route::prefix('rate-cards')->name('rate-cards.')->group(function () {
        Route::get('/', [SupervisorRateCardController::class, 'index'])->name('index');
        Route::get('/{rate_card}', [SupervisorRateCardController::class, 'show'])->name('show');
        Route::post('/calculate-preview', [SupervisorRateCardController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/find-applicable', [SupervisorRateCardController::class, 'findApplicableRate'])->name('find-applicable');
    });

    /* Job Assignment (supervisor or admin) */
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/jobs/assign', function () {
            return 'Assign Jobs - Admin or Supervisor';
        })->name('jobs.assign');
    });
});

/*
|--------------------------------------------------------------------------
| TECHNICIAN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['technician'])->prefix('technician')->name('technician.')->group(function () {
    /* Technician Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* Profile Management Routes */
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

    /* Partners Management Routes */
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('/datatable', [TechnicianPartnerController::class, 'datatable'])->name('datatable');
        Route::get('/ajax/list', [TechnicianPartnerController::class, 'getList'])->name('ajax.list');
        Route::get('/', [TechnicianPartnerController::class, 'index'])->name('index');
        Route::get('/{partner}', [TechnicianPartnerController::class, 'show'])->name('show');
    });

    /* Client Routes */
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [TechnicianClientController::class, 'index'])->name('index');
        Route::get('/datatable', [TechnicianClientController::class, 'datatable'])->name('datatable');
        Route::get('/{client}', [TechnicianClientController::class, 'show'])->name('show');
        Route::get('/ajax/list', [TechnicianClientController::class, 'getList'])->name('ajax.list');
    });

    /* Sites management Routes */
    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [TechnicianSiteController::class, 'index'])->name('index');
        Route::get('/{site}', [TechnicianSiteController::class, 'show'])->name('show');
    });

    /* Terminal Categories Management Routes */
    Route::prefix('terminal-categories')->name('terminal-categories.')->group(function () {
        Route::get('/', [TechnicianTerminalCategoryController::class, 'index'])->name('index');
        Route::get('/datatable', [TechnicianTerminalCategoryController::class, 'datatable'])->name('datatable');
    });

    /* Terminal model Management Routes */
    Route::prefix('terminal-models')->name('terminal-models.')->group(function () {
        Route::get('/datatable', [TechnicianTerminalModelController::class, 'datatable'])->name('datatable');
        Route::get('/', [TechnicianTerminalModelController::class, 'index'])->name('index');
        Route::get('/{terminalModel}', [TechnicianTerminalModelController::class, 'show'])->name('show');
    });

    /* Charge Catalog Routes */
    Route::prefix('charge-catalog')->name('charge-catalog.')->group(function () {
        Route::get('/', [TechnicianChargeCatalogController::class, 'index'])->name('index');
        Route::get('/by-type', [TechnicianChargeCatalogController::class, 'getByType'])->name('by-type');
        Route::get('/search', [TechnicianChargeCatalogController::class, 'search'])->name('search');
    });

    /* Rate cards Routes */
    Route::prefix('charge-catalog')->name('charge-catalog.')->group(function () {
        Route::get('/', [TechnicianRateCardController::class, 'index'])->name('index');
        Route::post('/calculate-preview', [TechnicianRateCardController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/find-applicable', [TechnicianRateCardController::class, 'findApplicableRate'])->name('find-applicable');
    });

    /* My Jobs */
    Route::get('/jobs', function () {
        return 'My Jobs - Technician Only';
    })->name('jobs.index');

    /* My Inventory */
    Route::get('/inventory', function () {
        return 'My Inventory - Technician Only';
    })->name('inventory.index');

    /* Claims */
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
