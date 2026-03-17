<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
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
use App\Http\Controllers\Admin\DepotController as AdminDepotController;
use App\Http\Controllers\Supervisor\DepotController as SupervisorDepotController;
use App\Http\Controllers\Technician\DepotController as TechnicianDepotController;
use App\Http\Controllers\Admin\InventorySerialController as AdminInventorySerialController;
use App\Http\Controllers\Supervisor\InventorySerialController as SupervisorInventorySerialController;
use App\Http\Controllers\Technician\InventorySerialController as TechnicianInventorySerialController;
use App\Http\Controllers\SerialLookupController;
use App\Http\Controllers\Admin\SerialMovementHistoryController as AdminSerialMovementHistoryController;
use App\Http\Controllers\Supervisor\SerialMovementHistoryController as SupervisorSerialMovementHistoryController;
use App\Http\Controllers\Technician\SerialMovementHistoryController as TechnicianSerialMovementHistoryController;
use App\Http\Controllers\Admin\BulkSerialController as AdminBulkSerialController;
use App\Http\Controllers\Supervisor\BulkSerialController as SupervisorBulkSerialController;
use App\Http\Controllers\Technician\BulkSerialController as TechnicianBulkSerialController;
use App\Http\Controllers\Admin\StockLedgerController as AdminStockLedgerController;
use App\Http\Controllers\Admin\StockBalanceController as AdminStockBalanceController;
use App\Http\Controllers\Supervisor\StockLedgerController as SupervisorStockLedgerController;
use App\Http\Controllers\Supervisor\StockBalanceController as SupervisorStockBalanceController;
use App\Http\Controllers\Technician\StockLedgerController as TechnicianStockLedgerController;
use App\Http\Controllers\Technician\StockBalanceController as TechnicianStockBalanceController;
use App\Http\Controllers\Admin\StockReportController as AdminStockReportController;
use App\Http\Controllers\Supervisor\StockReportController as SupervisorStockReportController;
use App\Http\Controllers\Technician\StockReportController as TechnicianStockReportController;
use App\Http\Controllers\Admin\StockValuationController as AdminStockValuationController;
use App\Http\Controllers\Supervisor\StockValuationController as SupervisorStockValuationController;
use App\Http\Controllers\Admin\InventoryDashboardController as AdminInventoryDashboardController;
use App\Http\Controllers\Supervisor\InventoryDashboardController as SupervisorInventoryDashboardController;
use App\Http\Controllers\Technician\InventoryDashboardController as TechnicianInventoryDashboardController;
use App\Http\Controllers\Admin\StockIssueController as AdminStockIssueController;
use App\Http\Controllers\Supervisor\StockIssueController as SupervisorStockIssueController;
use App\Http\Controllers\Technician\StockIssueController as TechnicianStockIssueController;
use App\Http\Controllers\Admin\StockReturnController as AdminStockReturnController;
use App\Http\Controllers\Supervisor\StockReturnController as SupervisorStockReturnController;
use App\Http\Controllers\Technician\StockReturnController as TechnicianStockReturnController;
use App\Http\Controllers\Admin\StockTransferController as AdminStockTransferController;
use App\Http\Controllers\Supervisor\StockTransferController as SupervisorStockTransferController;
use App\Http\Controllers\Technician\StockTransferController as TechnicianStockTransferController;
use App\Http\Controllers\Admin\StockAdjustmentController as AdminStockAdjustmentController;
use App\Http\Controllers\Supervisor\StockAdjustmentController as SupervisorStockAdjustmentController;
use App\Http\Controllers\Technician\InventoryController as TechnicianInventoryController;
use App\Http\Controllers\Admin\QuotationController as AdminQuotationController;
use App\Http\Controllers\Supervisor\QuotationController as SupervisorQuotationController;
use App\Http\Controllers\Technician\QuotationController as TechnicianQuotationController;
use App\Http\Controllers\Admin\PurchaseOrderController as AdminPOController;
use App\Http\Controllers\Supervisor\PurchaseOrderController as SupervisorPOController;
use App\Http\Controllers\Technician\PurchaseOrderController as TechnicianPOController;
use App\Http\Controllers\Admin\GrnController as AdminGrnController;
use App\Http\Controllers\Supervisor\GrnController as SupervisorGrnController;
use App\Http\Controllers\Technician\GrnController as TechnicianGrnController;
use App\Http\Controllers\Admin\GrnReportController as AdminGrnReportController;
use App\Http\Controllers\Supervisor\GrnReportController as SupervisorGrnReportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Admin\JobTypeController as AdminJobTypeController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Supervisor\TicketController as SupervisorTicketController;
use App\Http\Controllers\Technician\TicketController as TechnicianTicketController;
use App\Http\Controllers\Admin\VendorTypeController as AdminVendorTypeController;
use App\Http\Controllers\Supervisor\VendorController as SupervisorVendorController;
use App\Http\Controllers\Admin\ClaimManagementController as AdminClaimController;
use App\Http\Controllers\Supervisor\ClaimController as SupervisorClaimController;
use App\Http\Controllers\Technician\ClaimController as TechnicianClaimController;

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

    // Clear Cache Route — accessible by all authenticated roles
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'All caches cleared successfully!');
    })->name('clear-cache');

    /* Serial Lookup API Routes */
    Route::prefix('api/serials')->name('api.serials.')->group(function () {
        Route::get('/autocomplete', [SerialLookupController::class, 'autocomplete'])->name('autocomplete');
        Route::get('/validate', [SerialLookupController::class, 'validateSerial'])->name('validate');
        Route::post('/batch-lookup', [SerialLookupController::class, 'batchLookup'])->name('batch-lookup');
        Route::get('/search', [SerialLookupController::class, 'search'])->name('search');
        Route::get('/{id}/history', [SerialLookupController::class, 'history'])->name('history');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    /* Admin Dashboard */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/states', [LocationController::class, 'states'])->name('states');
        Route::get('/cities', [LocationController::class, 'cities'])->name('cities');
        Route::get('/supervisors', [LocationController::class, 'supervisors'])->name('supervisors');
        Route::get('/supervisor-detail', [LocationController::class, 'supervisorDetail'])->name('supervisor-detail');
    });

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
        Route::get('/ajax/supervisors', 'supervisorsList')->name('ajax.supervisors');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/stats', 'stats')->name('stats');

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
        Route::get('/create', [PermissionController::class, 'create'])->name('create');
        Route::post('/', [PermissionController::class, 'store'])->name('store');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');

        // Matrix and bulk operations
        Route::get('/matrix', [PermissionController::class, 'matrix'])->name('matrix');
        Route::post('/update-matrix', [PermissionController::class, 'updateMatrix'])->name('update-matrix');
        Route::post('/bulk-update', [PermissionController::class, 'bulkUpdate'])->name('bulk-update');

        // Export and utility routes
        Route::get('/export-matrix', [PermissionController::class, 'exportMatrix'])->name('export-matrix');
        Route::get('/{permission}/details', [PermissionController::class, 'getDetails'])->name('details');
        Route::get('/group/{group}', [PermissionController::class, 'getByGroup'])->name('by-group');
        Route::get('/search', [PermissionController::class, 'search'])->name('search');
        Route::get('/statistics', [PermissionController::class, 'statistics'])->name('statistics');
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
        Route::get('/', [AdminClientController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminClientController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminClientController::class, 'create'])->name('create');
        Route::post('/', [AdminClientController::class, 'store'])->name('store');
        Route::get('/{client}', [AdminClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [AdminClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [AdminClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [AdminClientController::class, 'destroy'])->name('destroy');
        Route::post('/{client}/restore', [AdminClientController::class, 'restore'])->name('restore');
        Route::post('/{client}/toggle-status', [AdminClientController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{client}/contacts', [AdminClientController::class, 'addContact'])->name('contacts.store');
        Route::put('/{client}/contacts/{contact}', [AdminClientController::class, 'updateContact'])->name('contacts.update');
        Route::delete('/{client}/contacts/{contact}', [AdminClientController::class, 'removeContact'])->name('contacts.destroy');
        Route::post('/{client}/contacts/{contact}/set-primary', [AdminClientController::class, 'setPrimaryContact'])->name('contacts.set-primary');
        Route::get('/ajax/list', [AdminClientController::class, 'getList'])->name('ajax.list');
    });


    /* Vendors Management Routes */
    Route::prefix('vendors')->name('vendors.')->group(function () {
        // List & DataTable
        Route::get('/', [AdminVendorController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminVendorController::class, 'datatable'])->name('datatable');

        // Create & Store
        Route::get('/create', [AdminVendorController::class, 'create'])->name('create');
        Route::post('/', [AdminVendorController::class, 'store'])->name('store');

        // Export / Import (MUST be before /{vendor})
        Route::get('/export/excel', [AdminVendorController::class, 'export'])->name('export');
        Route::post('/import/excel', [AdminVendorController::class, 'import'])->name('import');
        Route::get('/import/template', [AdminVendorController::class, 'importTemplate'])->name('import-template');

        // API list for dropdowns (MUST be before /{vendor})
        Route::get('/api/list', [AdminVendorController::class, 'getList'])->name('api.list');

        // Vendor code suggestions (NEW — MUST be before /{vendor})
        Route::get('/suggest-code', [AdminVendorController::class, 'suggestCode'])->name('suggest-code');
        Route::get('/check-code', [AdminVendorController::class, 'checkCode'])->name('check-code');

        // Parameterized routes LAST
        Route::get('/{vendor}', [AdminVendorController::class, 'show'])->name('show');
        Route::get('/{vendor}/edit', [AdminVendorController::class, 'edit'])->name('edit');
        Route::put('/{vendor}', [AdminVendorController::class, 'update'])->name('update');
        Route::delete('/{vendor}', [AdminVendorController::class, 'destroy'])->name('destroy');
        Route::post('/{vendor}/restore', [AdminVendorController::class, 'restore'])->name('restore');
        Route::post('/{vendor}/toggle-status', [AdminVendorController::class, 'toggleStatus'])->name('toggle-status');
    });

    /* Sites Management Routes */
    Route::prefix('sites')->name('sites.')->group(function () {
        Route::get('/', [AdminSiteController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminSiteController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminSiteController::class, 'create'])->name('create');
        Route::post('/', [AdminSiteController::class, 'store'])->name('store');
        Route::get('/{site}', [AdminSiteController::class, 'show'])->name('show');
        Route::get('/{site}/edit', [AdminSiteController::class, 'edit'])->name('edit');
        Route::put('/{site}', [AdminSiteController::class, 'update'])->name('update');
        Route::delete('/{site}', [AdminSiteController::class, 'destroy'])->name('destroy');
        Route::post('/{siteId}/restore', [AdminSiteController::class, 'restore'])->name('restore');
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
        Route::get('/ajax/job-types', [AdminChargeCatalogController::class, 'ajaxJobTypes'])->name('ajax.job-types');
        Route::get('/create', [AdminChargeCatalogController::class, 'create'])->name('create');
        Route::post('/', [AdminChargeCatalogController::class, 'store'])->name('store');
        Route::get('/{chargeCatalog}/edit', [AdminChargeCatalogController::class, 'edit'])->name('edit');
        Route::put('/{chargeCatalog}', [AdminChargeCatalogController::class, 'update'])->name('update');
        Route::delete('/{chargeCatalog}', [AdminChargeCatalogController::class, 'destroy'])->name('destroy');
        Route::post('/{chargeCatalog}/toggle-status', [AdminChargeCatalogController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/search', [AdminChargeCatalogController::class, 'searchCharges'])->name('search');
    });

    /* Rate Card Routes */
    Route::prefix('rate-cards')->name('rate-cards.')->group(function () {
        Route::get('/', [AdminRateCardController::class, 'index'])->name('index');
        Route::get('/create', [AdminRateCardController::class, 'create'])->name('create');
        Route::get('/export', [AdminRateCardController::class, 'export'])->name('export');
        Route::post('/calculate-preview', [AdminRateCardController::class, 'calculatePreview'])->name('calculate-preview');
        Route::post('/find-applicable', [AdminRateCardController::class, 'findApplicableRate'])->name('find-applicable');
        Route::get('/{rate_card}', [AdminRateCardController::class, 'show'])->name('show');
        Route::get('/{rate_card}/edit', [AdminRateCardController::class, 'edit'])->name('edit');
        Route::post('/', [AdminRateCardController::class, 'store'])->name('store');
        Route::put('/{rate_card}', [AdminRateCardController::class, 'update'])->name('update');
        Route::delete('/{rate_card}', [AdminRateCardController::class, 'destroy'])->name('destroy');
        Route::post('/{rate_card}/toggle-status', [AdminRateCardController::class, 'toggleStatus'])->name('toggle-status');
    });

    /* Job Types Routes */
    Route::prefix('job-types')->name('job-types.')->group(function () {
        Route::get('/datatable', [AdminJobTypeController::class, 'datatable'])->name('datatable');
        Route::patch('/{job_type}/toggle-status', [AdminJobTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/', [AdminJobTypeController::class, 'index'])->name('index');
        Route::get('/create', [AdminJobTypeController::class, 'create'])->name('create');
        Route::post('/', [AdminJobTypeController::class, 'store'])->name('store');
        Route::get('/{job_type}/edit', [AdminJobTypeController::class, 'edit'])->name('edit');
        Route::put('/{job_type}', [AdminJobTypeController::class, 'update'])->name('update');
        Route::delete('/{job_type}', [AdminJobTypeController::class, 'destroy'])->name('destroy');
    });

    /* Depots Routes */
    Route::resource('depots', AdminDepotController::class);
    Route::prefix('depots')->name('depots.')->group(function () {
        Route::post('{id}/restore', [AdminDepotController::class, 'restore'])->name('restore');
        Route::get('{depot}/stock-summary', [AdminDepotController::class, 'stockSummary'])->name('stock-summary');
        Route::get('{depot}/movements', [AdminDepotController::class, 'movements'])->name('movements');
    });

    /* Inventory Serial Tracking Routes */
    Route::prefix('inventory-serials')->name('inventory-serials.')->group(function () {
        Route::get('/datatable', [AdminInventorySerialController::class, 'datatable'])->name('datatable');
        Route::get('/export', [AdminInventorySerialController::class, 'export'])->name('export');
        Route::get('/lookup', [AdminInventorySerialController::class, 'lookup'])->name('lookup');
        Route::post('/{id}/restore', [AdminInventorySerialController::class, 'restore'])->name('restore');
        Route::get('/', [AdminInventorySerialController::class, 'index'])->name('index');
        Route::get('/create', [AdminInventorySerialController::class, 'create'])->name('create');
        Route::post('/', [AdminInventorySerialController::class, 'store'])->name('store');
        Route::get('/{inventorySerial}', [AdminInventorySerialController::class, 'show'])->name('show');
        Route::get('/{inventorySerial}/edit', [AdminInventorySerialController::class, 'edit'])->name('edit');
        Route::put('/{inventorySerial}', [AdminInventorySerialController::class, 'update'])->name('update');
        Route::delete('/{inventorySerial}', [AdminInventorySerialController::class, 'destroy'])->name('destroy');
    });

    /* Serial Movement History Routes */
    Route::prefix('serial-movement-history')->name('serial-movement-history.')->group(function () {
        Route::get('/datatable', [AdminSerialMovementHistoryController::class, 'datatable'])->name('datatable');
        Route::get('/', [AdminSerialMovementHistoryController::class, 'index'])->name('index');
        Route::get('/{serialId}', [AdminSerialMovementHistoryController::class, 'show'])->name('show');
        Route::get('/{serialId}/timeline', [AdminSerialMovementHistoryController::class, 'timeline'])->name('timeline');
        Route::post('/{ledgerId}/reverse', [AdminSerialMovementHistoryController::class, 'reverse'])->name('reverse');
    });

    /* Bulk-serials Routes */
    Route::prefix('bulk-serials')->name('bulk-serials.')->group(function () {
        Route::get('/', [AdminBulkSerialController::class, 'index'])->name('index');
        Route::get('/import', [AdminBulkSerialController::class, 'importForm'])->name('import-form');
        Route::post('/import', [AdminBulkSerialController::class, 'import'])->name('import');
        Route::get('/download-template', [AdminBulkSerialController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/bulk-update-form', [AdminBulkSerialController::class, 'bulkUpdateForm'])->name('bulk-update-form');
        Route::post('/bulk-update', [AdminBulkSerialController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/transfer-form', [AdminBulkSerialController::class, 'transferForm'])->name('transfer-form');
        Route::post('/validate-serials', [AdminBulkSerialController::class, 'validateSerials'])->name('validate-serials');
        Route::post('/preview', [AdminBulkSerialController::class, 'preview'])->name('preview');
        Route::post('/generate-labels', [AdminBulkSerialController::class, 'generateLabels'])->name('generate-labels');
        Route::post('/export-serials', [AdminBulkSerialController::class, 'exportSerials'])->name('export-serials');
        Route::get('/get-locations', [AdminBulkSerialController::class, 'getLocations'])->name('get-locations');
    });

    /* Stock-ledger Routes */
    Route::prefix('stock-ledger')->name('stock-ledger.')->group(function () {
        Route::get('/', [AdminStockLedgerController::class, 'index'])->name('index');
        Route::get('/export', [AdminStockLedgerController::class, 'export'])->name('export');
        Route::get('/{stockLedger}', [AdminStockLedgerController::class, 'show'])->name('show');
        Route::post('/{stockLedger}/reverse', [AdminStockLedgerController::class, 'reverse'])->name('reverse');
    });

    /* Stock-balance Routes */
    Route::prefix('stock-balance')->name('stock-balance.')->group(function () {
        Route::get('/', [AdminStockBalanceController::class, 'index'])->name('index');
        Route::get('/export', [AdminStockBalanceController::class, 'export'])->name('export');
        Route::get('/{locationType}/{locationId}', [AdminStockBalanceController::class, 'show'])->name('show');
        Route::get('/alerts', [AdminStockBalanceController::class, 'alerts'])->name('alerts');
        Route::post('/recalculate', [AdminStockBalanceController::class, 'recalculate'])->name('recalculate');
        Route::post('/reserve', [AdminStockBalanceController::class, 'reserve'])->name('reserve');
        Route::post('/release', [AdminStockBalanceController::class, 'releaseReservation'])->name('release');
    });

    /* Stock-reports Routes */
    Route::prefix('stock-reports')->name('stock-reports.')->group(function () {
        Route::get('/', [AdminStockReportController::class, 'index'])->name('index');
        Route::get('/movement', [AdminStockReportController::class, 'movement'])->name('movement');
        Route::get('/stock-card/{serialId?}', [AdminStockReportController::class, 'stockCard'])->name('stock-card');
        Route::get('/summary', [AdminStockReportController::class, 'summary'])->name('summary');
        Route::get('/movement/export', [AdminStockReportController::class, 'exportMovement'])->name('movement-export');
        Route::get('/summary/export', [AdminStockReportController::class, 'exportSummary'])->name('summary-export');
        Route::get('/stock-card/{serialId}/export', [AdminStockReportController::class, 'exportStockCard'])->name('stock-card-export');
        Route::get('/stock-card/{serialId}/print', [AdminStockReportController::class, 'printStockCard'])->name('stock-card-print');
        Route::get('/search-serials', [AdminStockReportController::class, 'searchSerials'])->name('search-serials');
    });

    /* Stock-valuation Routes */
    Route::prefix('stock-valuation')->name('stock-valuation.')->group(function () {
        Route::get('/', [AdminStockValuationController::class, 'index'])->name('index');
        Route::get('/detailed', [AdminStockValuationController::class, 'detailed'])->name('detailed');
        Route::get('/movement-value', [AdminStockValuationController::class, 'movementValue'])->name('movement-value');
        Route::get('/aging', [AdminStockValuationController::class, 'aging'])->name('aging');
        Route::get('/export-summary', [AdminStockValuationController::class, 'exportSummary'])->name('export-summary');
        Route::get('/export-detailed', [AdminStockValuationController::class, 'exportDetailed'])->name('export-detailed');
        Route::get('/export-movement-value', [AdminStockValuationController::class, 'exportMovementValue'])->name('export-movement-value');
    });

    /* Inventory Dashboard Routes */
    Route::prefix('inventory-dashboard')->name('inventory-dashboard.')->group(function () {
        Route::get('/', [AdminInventoryDashboardController::class, 'index'])->name('index');
        Route::get('/stock-by-category', [AdminInventoryDashboardController::class, 'stockByCategory'])->name('stock-by-category');
        Route::get('/stock-by-status', [AdminInventoryDashboardController::class, 'stockByStatus'])->name('stock-by-status');
        Route::get('/stock-by-depot', [AdminInventoryDashboardController::class, 'stockByDepot'])->name('stock-by-depot');
        Route::get('/low-stock-alerts', [AdminInventoryDashboardController::class, 'lowStockAlerts'])->name('low-stock-alerts');
        Route::get('/recent-movements', [AdminInventoryDashboardController::class, 'recentMovements'])->name('recent-movements');
        Route::get('/stock-aging', [AdminInventoryDashboardController::class, 'stockAging'])->name('stock-aging');
        Route::get('/top-models', [AdminInventoryDashboardController::class, 'topModels'])->name('top-models');
        Route::get('/movement-trend', [AdminInventoryDashboardController::class, 'movementTrend'])->name('movement-trend');
        Route::get('/category-distribution', [AdminInventoryDashboardController::class, 'categoryDistribution'])->name('category-distribution');
        Route::get('/movement-summary', [AdminInventoryDashboardController::class, 'movementSummary'])->name('movement-summary');
    });

    /* Stock Issues Routes */
    Route::prefix('stock-issues')->name('stock-issues.')->group(function () {
        Route::get('/export', [AdminStockIssueController::class, 'export'])->name('export');
        Route::get('/get-available-serials', [AdminStockIssueController::class, 'getAvailableSerials'])->name('get-available-serials');
        Route::get('/get-technician-serials', [AdminStockIssueController::class, 'getTechnicianSerials'])->name('get-technician-serials');
        Route::post('/{stockIssue}/post', [AdminStockIssueController::class, 'post'])->name('post');
        Route::post('/{stockIssue}/cancel', [AdminStockIssueController::class, 'cancel'])->name('cancel');
        Route::get('/{stockIssue}/print', [AdminStockIssueController::class, 'print'])->name('print');
        Route::get('/', [AdminStockIssueController::class, 'index'])->name('index');
        Route::get('/create', [AdminStockIssueController::class, 'create'])->name('create');
        Route::post('/', [AdminStockIssueController::class, 'store'])->name('store');
        Route::get('/{stockIssue}', [AdminStockIssueController::class, 'show'])->name('show');
        Route::get('/{stockIssue}/edit', [AdminStockIssueController::class, 'edit'])->name('edit');
        Route::put('/{stockIssue}', [AdminStockIssueController::class, 'update'])->name('update');
        Route::patch('/{stockIssue}', [AdminStockIssueController::class, 'update'])->name('update');
        Route::delete('/{stockIssue}', [AdminStockIssueController::class, 'destroy'])->name('destroy');
    });

    /* Stock Returns Routes */
    Route::prefix('stock-returns')->name('stock-returns.')->group(function () {
        Route::get('/', [AdminStockReturnController::class, 'index'])->name('index');
        Route::get('/create', [AdminStockReturnController::class, 'create'])->name('create');
        Route::post('/', [AdminStockReturnController::class, 'store'])->name('store');
        Route::get('/{stockReturn}', [AdminStockReturnController::class, 'show'])->name('show');
        Route::get('/{stockReturn}/edit', [AdminStockReturnController::class, 'edit'])->name('edit');
        Route::put('/{stockReturn}', [AdminStockReturnController::class, 'update'])->name('update');
        Route::post('/{stockReturn}/post', [AdminStockReturnController::class, 'post'])->name('post');
        Route::post('/{stockReturn}/cancel', [AdminStockReturnController::class, 'cancel'])->name('cancel');
        Route::get('/{stockReturn}/print', [AdminStockReturnController::class, 'print'])->name('print');
        Route::get('/technician/inventory', [AdminStockReturnController::class, 'getTechnicianInventory'])->name('technician-inventory');
        Route::get('/technician/summary', [AdminStockReturnController::class, 'getTechnicianInventorySummary'])->name('technician-summary');
        Route::get('/export/excel', [AdminStockReturnController::class, 'export'])->name('export');
    });

    /* Stock Transfers routes */
    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        Route::get('/', [AdminStockTransferController::class, 'index'])->name('index');
        Route::get('/create', [AdminStockTransferController::class, 'create'])->name('create');
        Route::post('/', [AdminStockTransferController::class, 'store'])->name('store');
        Route::get('/{stockTransfer}', [AdminStockTransferController::class, 'show'])->name('show');
        Route::get('/{stockTransfer}/edit', [AdminStockTransferController::class, 'edit'])->name('edit');
        Route::put('/{stockTransfer}', [AdminStockTransferController::class, 'update'])->name('update');
        Route::post('/{stockTransfer}/submit', [AdminStockTransferController::class, 'submitForApproval'])->name('submit');
        Route::get('/{stockTransfer}/approve', [AdminStockTransferController::class, 'approveForm'])->name('approve-form');
        Route::post('/{stockTransfer}/approve', [AdminStockTransferController::class, 'approve'])->name('approve');
        Route::post('/{stockTransfer}/reject', [AdminStockTransferController::class, 'reject'])->name('reject');
        Route::post('/{stockTransfer}/dispatch', [AdminStockTransferController::class, 'dispatch'])->name('dispatch');
        Route::get('/{stockTransfer}/receive', [AdminStockTransferController::class, 'receiveForm'])->name('receive-form');
        Route::post('/{stockTransfer}/receive', [AdminStockTransferController::class, 'receive'])->name('receive');
        Route::post('/{stockTransfer}/cancel', [AdminStockTransferController::class, 'cancel'])->name('cancel');
        Route::get('/{stockTransfer}/print', [AdminStockTransferController::class, 'print'])->name('print');
        Route::get('/export', [AdminStockTransferController::class, 'export'])->name('export');
        Route::get('/available-stock', [AdminStockTransferController::class, 'getAvailableStock'])->name('available-stock');
    });

    /* Stock Adjustments Routes */
    Route::prefix('stock-adjustments')->name('stock-adjustments.')->group(function () {
        Route::get('/', [AdminStockAdjustmentController::class, 'index'])->name('index');
        Route::get('/create', [AdminStockAdjustmentController::class, 'create'])->name('create');
        Route::post('/', [AdminStockAdjustmentController::class, 'store'])->name('store');
        Route::get('/{stockAdjustment}', [AdminStockAdjustmentController::class, 'show'])->name('show');
        Route::get('/{stockAdjustment}/edit', [AdminStockAdjustmentController::class, 'edit'])->name('edit');
        Route::put('/{stockAdjustment}', [AdminStockAdjustmentController::class, 'update'])->name('update');
        Route::delete('/{stockAdjustment}', [AdminStockAdjustmentController::class, 'destroy'])->name('destroy');
        Route::get('/{stockAdjustment}/approve', [AdminStockAdjustmentController::class, 'approve'])->name('approve');
        Route::post('/{stockAdjustment}/process-approval', [AdminStockAdjustmentController::class, 'processApproval'])->name('process-approval');
        Route::post('/{stockAdjustment}/submit', [AdminStockAdjustmentController::class, 'submit'])->name('submit');
        Route::post('/{stockAdjustment}/post', [AdminStockAdjustmentController::class, 'post'])->name('post');
        Route::get('/ajax/depot-stock', [AdminStockAdjustmentController::class, 'getDepotStock'])->name('get-depot-stock');
        Route::get('/reports/variance', [AdminStockAdjustmentController::class, 'varianceReport'])->name('variance-report');
        Route::get('/export/excel', [AdminStockAdjustmentController::class, 'export'])->name('export');
        Route::get('/export/variance', [AdminStockAdjustmentController::class, 'exportVariance'])->name('export-variance');
    });

    /* Quatation Routes */
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/export', [AdminQuotationController::class, 'export'])->name('export');
        Route::get('/', [AdminQuotationController::class, 'index'])->name('index');
        Route::get('/create', [AdminQuotationController::class, 'create'])->name('create');
        Route::post('/', [AdminQuotationController::class, 'store'])->name('store');
        Route::get('/{quotation}', [AdminQuotationController::class, 'show'])->name('show');
        Route::get('/{quotation}/edit', [AdminQuotationController::class, 'edit'])->name('edit');
        Route::put('/{quotation}', [AdminQuotationController::class, 'update'])->name('update');
        Route::delete('/{quotation}', [AdminQuotationController::class, 'destroy'])->name('destroy');
        Route::post('/{quotation}/submit-approval', [AdminQuotationController::class, 'submitForApproval'])->name('submit-approval');
        Route::post('/{quotation}/process-approval', [AdminQuotationController::class, 'processApproval'])->name('process-approval');
        Route::post('/{quotation}/send', [AdminQuotationController::class, 'send'])->name('send');
        Route::post('/{quotation}/accept', [AdminQuotationController::class, 'accept'])->name('accept');
        Route::post('/{quotation}/cancel', [AdminQuotationController::class, 'cancel'])->name('cancel');
        Route::post('/{quotation}/convert-to-po', [AdminQuotationController::class, 'convertToPO'])->name('convert-to-po');
        Route::post('/{quotation}/duplicate', [AdminQuotationController::class, 'duplicate'])->name('duplicate');
        Route::get('/{quotation}/print', [AdminQuotationController::class, 'print'])->name('print');
        Route::get('/model-price/{model}', [AdminQuotationController::class, 'getModelPrice'])->name('model-price');
        Route::get('/charge-price/{charge}', [AdminQuotationController::class, 'getChargePrice'])->name('charge-price');
        Route::get('{quotation}/pdf/download', [AdminQuotationController::class, 'downloadPdf'])->name('pdf.download');
        Route::get('{quotation}/pdf/preview', [AdminQuotationController::class, 'previewPdf'])->name('pdf.preview');
        Route::post('{quotation}/pdf/email', [AdminQuotationController::class, 'emailPdf'])->name('pdf.email');
    });

    /* Purchase Orders */
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/export', [AdminPOController::class, 'export'])->name('export');
        Route::resource('/', AdminPOController::class)->parameters(['' => 'purchaseOrder']);
        Route::post('/{purchaseOrder}/submit', [AdminPOController::class, 'submitForApproval'])->name('submit');
        Route::post('/{purchaseOrder}/approve', [AdminPOController::class, 'approve'])->name('approve');
        Route::post('/{purchaseOrder}/reject', [AdminPOController::class, 'reject'])->name('reject');
        Route::post('/{purchaseOrder}/send', [AdminPOController::class, 'sendToVendor'])->name('send');
        Route::post('/{purchaseOrder}/close', [AdminPOController::class, 'close'])->name('close');
        Route::post('/{purchaseOrder}/cancel', [AdminPOController::class, 'cancel'])->name('cancel');
        Route::get('/{purchaseOrder}/pdf', [AdminPOController::class, 'pdf'])->name('pdf');
        Route::get('/{purchaseOrder}/download', [AdminPOController::class, 'downloadPdf'])->name('download');
    });

    /* GRNS Routes */
    Route::prefix('grns')->name('grns.')->group(function () {
        Route::get('/', [AdminGrnController::class, 'index'])->name('index');
        Route::get('/create', [AdminGrnController::class, 'create'])->name('create');
        Route::post('/', [AdminGrnController::class, 'store'])->name('store');
        Route::get('/{grn}', [AdminGrnController::class, 'show'])->name('show');
        Route::get('/{grn}/edit', [AdminGrnController::class, 'edit'])->name('edit');
        Route::put('/{grn}', [AdminGrnController::class, 'update'])->name('update');
        Route::delete('/{grn}', [AdminGrnController::class, 'destroy'])->name('destroy');

        // Special routes
        Route::get('/purchase-orders/{po}/details', [AdminGrnController::class, 'getPurchaseOrderDetails'])
            ->name('po-details');
        Route::post('/{grn}/post', [AdminGrnController::class, 'post'])->name('post');
        Route::post('/{grn}/cancel', [AdminGrnController::class, 'cancel'])->name('cancel');
        Route::post('/validate-serial', [AdminGrnController::class, 'validateSerial'])
            ->name('validate-serial');

        Route::get('/{grn}/pdf', [AdminGrnController::class, 'pdf'])->name('pdf');
        Route::get('/{grn}/download', [AdminGrnController::class, 'downloadPdf'])->name('download');
        // Export
        Route::get('/export/excel', [AdminGrnController::class, 'export'])->name('export');
    });

    /* GRN Reports Routes (Admin) */
    Route::prefix('grn-reports')->name('grn-reports.')->middleware('permission:view_reports_grns')->group(function () {
        Route::get('/', [AdminGrnReportController::class, 'index'])->name('index');
        Route::get('/register', [AdminGrnReportController::class, 'register'])->name('register');
        Route::get('/receiving-by-vendor', [AdminGrnReportController::class, 'receivingByVendor'])->name('receiving-by-vendor');
        Route::get('/receiving-by-model', [AdminGrnReportController::class, 'receivingByModel'])->name('receiving-by-model');

        // Exports
        Route::get('/export-list', [AdminGrnReportController::class, 'exportList'])->name('export-list');
        Route::get('/export-register', [AdminGrnReportController::class, 'exportRegister'])->name('export-register');
        Route::get('/export-receiving-by-vendor', [AdminGrnReportController::class, 'exportReceivingByVendor'])->name('export-receiving-by-vendor');
        Route::get('/export-receiving-by-model', [AdminGrnReportController::class, 'exportReceivingByModel'])->name('export-receiving-by-model');
    });

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [AdminTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminTicketController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminTicketController::class, 'create'])->name('create');
        Route::post('/', [AdminTicketController::class, 'store'])->name('store');
        Route::get('/ajax/vendor-branches', [AdminTicketController::class, 'getVendorBranches'])->name('ajax.vendor-branches');
        Route::get('/ajax/cities', [AdminTicketController::class, 'getCities'])->name('ajax.cities');
        Route::get('/ajax/technicians', [AdminTicketController::class, 'getTechnicians'])->name('ajax.technicians');
        Route::get('/ajax/supervisor-mileage-rate', [AdminTicketController::class, 'getSupervisorMileageRate'])->name('ajax.supervisor-mileage-rate');
        Route::get('/ajax/charges', [AdminTicketController::class, 'getCharges'])->name('ajax.charges');
        Route::get('/ajax/supervisors', [AdminTicketController::class, 'getSupervisors'])->name('ajax.supervisors');
        Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [AdminTicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [AdminTicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [AdminTicketController::class, 'destroy'])->name('destroy');
        Route::post('/{ticket}/change-status', [AdminTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/update-claim', [AdminTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/comment', [AdminTicketController::class, 'addComment'])->name('comment');
    });

    /* Vendor Types */
    Route::prefix('vendor-types')->name('vendor-types.')->group(function () {
        Route::get('/', [AdminVendorTypeController::class, 'index'])->name('index');
        Route::get('/create', [AdminVendorTypeController::class, 'create'])->name('create');
        Route::post('/', [AdminVendorTypeController::class, 'store'])->name('store');
        Route::get('/{vendor_type}/edit', [AdminVendorTypeController::class, 'edit'])->name('edit');
        Route::put('/{vendor_type}', [AdminVendorTypeController::class, 'update'])->name('update');
        Route::delete('/{vendor_type}', [AdminVendorTypeController::class, 'destroy'])->name('destroy');
        Route::post('/{vendor_type}/toggle-status', [AdminVendorTypeController::class, 'toggleStatus'])->name('toggle-status');
    });

    /* Claim Management */
    Route::prefix('claims')->name('claims.')->group(function () {
        // Landing
        Route::get('/', [AdminClaimController::class, 'index'])->name('index');

        // Ticket Claims
        Route::get('/ticket-claims', [AdminClaimController::class, 'ticketClaims'])->name('ticket-claims');
        Route::get('/ajax/ticket-claims-data', [AdminClaimController::class, 'ticketClaimsData'])->name('ticket-claims-data');

        // Other Claims
        Route::get('/other-claims', [AdminClaimController::class, 'otherClaims'])->name('other-claims');
        Route::get('/ajax/other-claims-data', [AdminClaimController::class, 'otherClaimsData'])->name('other-claims-data');
        Route::get('/create-other-claim', [AdminClaimController::class, 'createOtherClaim'])->name('create-other-claim');
        Route::post('/store-other-claim', [AdminClaimController::class, 'storeOtherClaim'])->name('store-other-claim');

        // Export
        Route::get('/export', [AdminClaimController::class, 'export'])->name('export');

        // Bulk Payment
        Route::get('/bulk-payment', [AdminClaimController::class, 'bulkPayment'])->name('bulk-payment');
        Route::post('/bulk-payment/process', [AdminClaimController::class, 'processBulkPayment'])->name('process-bulk-payment');
        Route::post('/bulk-payment/mark-paid', [AdminClaimController::class, 'markPaid'])->name('mark-paid');

        // Show / Verify / Non-Claimable / Update Amount (parameterized - MUST be last)
        Route::get('/{claim}', [AdminClaimController::class, 'show'])->name('show');
        Route::post('/{claim}/verify', [AdminClaimController::class, 'verify'])->name('verify');
        Route::post('/{claim}/non-claimable', [AdminClaimController::class, 'markNonClaimable'])->name('non-claimable');
        Route::post('/{claim}/update-amount', [AdminClaimController::class, 'updateAmount'])->name('update-amount');
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

    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/states', [LocationController::class, 'states'])->name('states');
        Route::get('/cities', [LocationController::class, 'cities'])->name('cities');
        Route::get('/supervisors', [LocationController::class, 'supervisors'])->name('supervisors');
    });

    /* User Management Routes */
    Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/{user}', 'show')->name('show');
    });

    /* Vendor Routes (View-Only) */
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [SupervisorVendorController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorVendorController::class, 'datatable'])->name('datatable');
        Route::get('/export/excel', [SupervisorVendorController::class, 'export'])->name('export');
        Route::get('/api/list', [SupervisorVendorController::class, 'getList'])->name('api.list');
        Route::get('/{vendor}', [SupervisorVendorController::class, 'show'])->name('show');
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

    /* Depots Routes */
    Route::prefix('depots')->name('depots.')->group(function () {
        Route::get('/', [SupervisorDepotController::class, 'index'])->name('index');
        Route::get('/{depot}', [SupervisorDepotController::class, 'show'])->name('show');
        Route::get('/{depot}/stock-summary', [SupervisorDepotController::class, 'stockSummary'])->name('stock-summary');
        Route::get('/{depot}/movements', [SupervisorDepotController::class, 'movements'])->name('movements');
    });

    /* Inventory Serial Tracking Routes */
    Route::prefix('inventory-serials')->name('inventory-serials.')->group(function () {
        Route::get('/datatable', [SupervisorInventorySerialController::class, 'datatable'])->name('datatable');
        Route::get('/', [SupervisorInventorySerialController::class, 'index'])->name('index');
        Route::get('/{inventorySerial}', [SupervisorInventorySerialController::class, 'show'])->name('show');
    });

    /* Serial Movement History Routes */
    Route::prefix('serial-movement-history')->name('serial-movement-history.')->group(function () {
        Route::get('/datatable', [SupervisorSerialMovementHistoryController::class, 'datatable'])->name('datatable');
        Route::get('/', [SupervisorSerialMovementHistoryController::class, 'index'])->name('index');
        Route::get('/{serialId}', [SupervisorSerialMovementHistoryController::class, 'show'])->name('show');
        Route::get('/{serialId}/timeline', [SupervisorSerialMovementHistoryController::class, 'timeline'])->name('timeline');
    });

    /* Bulk-serials Routes */
    Route::prefix('bulk-serials')->name('bulk-serials.')->group(function () {
        Route::get('/', [SupervisorBulkSerialController::class, 'index'])->name('index');
        Route::get('/import', [SupervisorBulkSerialController::class, 'importForm'])->name('import-form');
        Route::post('/import', [SupervisorBulkSerialController::class, 'import'])->name('import');
        Route::get('/download-template', [SupervisorBulkSerialController::class, 'downloadTemplate'])->name('download-template');
        Route::get('/bulk-update-form', [SupervisorBulkSerialController::class, 'bulkUpdateForm'])->name('bulk-update-form');
        Route::post('/bulk-update', [SupervisorBulkSerialController::class, 'bulkUpdate'])->name('bulk-update');
        Route::get('/transfer-form', [SupervisorBulkSerialController::class, 'transferForm'])->name('transfer-form');
        Route::post('/generate-labels', [SupervisorBulkSerialController::class, 'generateLabels'])->name('generate-labels');
        Route::post('/export-serials', [SupervisorBulkSerialController::class, 'exportSerials'])->name('export-serials');
        Route::get('/get-locations', [SupervisorBulkSerialController::class, 'getLocations'])->name('get-locations');
    });

    /* Stock Ledger Routes */
    Route::prefix('stock-ledger')->name('stock-ledger.')->group(function () {
        Route::get('/', [SupervisorStockLedgerController::class, 'index'])->name('index');
        Route::get('/{stockLedger}', [SupervisorStockLedgerController::class, 'show'])->name('show');
        Route::post('/{stockLedger}/reverse', [SupervisorStockLedgerController::class, 'reverse'])->name('reverse');
    });

    /* Stock Balance Routes */
    Route::prefix('stock-balance')->name('stock-balance.')->group(function () {
        Route::get('/', [SupervisorStockBalanceController::class, 'index'])->name('index');
        Route::get('/technician/{technician}', [SupervisorStockBalanceController::class, 'show'])->name('show');
    });

    /* Stock Reports Routes */
    Route::prefix('stock-reports')->name('stock-reports.')->group(function () {
        Route::get('/', [SupervisorStockReportController::class, 'index'])->name('index');
        Route::get('/movement', [SupervisorStockReportController::class, 'movement'])->name('movement');
        Route::get('/stock-card/{serialId?}', [SupervisorStockReportController::class, 'stockCard'])->name('stock-card');
        Route::get('/summary', [SupervisorStockReportController::class, 'summary'])->name('summary');
        Route::get('/movement/export', [SupervisorStockReportController::class, 'exportMovement'])->name('movement-export');
        Route::get('/summary/export', [SupervisorStockReportController::class, 'exportSummary'])->name('summary-export');
        Route::get('/stock-card/{serialId}/export', [SupervisorStockReportController::class, 'exportStockCard'])->name('stock-card-export');
        Route::get('/stock-card/{serialId}/print', [SupervisorStockReportController::class, 'printStockCard'])->name('stock-card-print');
        Route::get('/search-serials', [SupervisorStockReportController::class, 'searchSerials'])->name('search-serials');
    });

    /* Stock valuation Routes */
    Route::prefix('stock-valuation')->name('stock-valuation.')->group(function () {
        Route::get('/', [SupervisorStockValuationController::class, 'index'])->name('index');
        Route::get('/detailed', [SupervisorStockValuationController::class, 'detailed'])->name('detailed');
        Route::get('/export-summary', [SupervisorStockValuationController::class, 'exportSummary'])->name('export-summary');
        Route::get('/export-detailed', [SupervisorStockValuationController::class, 'exportDetailed'])->name('export-detailed');
    });

    /* Inventory Dashboard Routes */
    Route::prefix('inventory-dashboard')->name('inventory-dashboard.')->group(function () {
        Route::get('/', [SupervisorInventoryDashboardController::class, 'index'])->name('index');
        Route::get('/stock-by-category', [SupervisorInventoryDashboardController::class, 'stockByCategory'])->name('stock-by-category');
        Route::get('/stock-by-technician', [SupervisorInventoryDashboardController::class, 'stockByTechnician'])->name('stock-by-technician');
        Route::get('/low-stock-alerts', [SupervisorInventoryDashboardController::class, 'lowStockAlerts'])->name('low-stock-alerts');
        Route::get('/recent-movements', [SupervisorInventoryDashboardController::class, 'recentMovements'])->name('recent-movements');
        Route::get('/stock-aging', [SupervisorInventoryDashboardController::class, 'stockAging'])->name('stock-aging');
        Route::get('/top-models', [SupervisorInventoryDashboardController::class, 'topModels'])->name('top-models');
        Route::get('/movement-trend', [SupervisorInventoryDashboardController::class, 'movementTrend'])->name('movement-trend');
        Route::get('/category-distribution', [SupervisorInventoryDashboardController::class, 'categoryDistribution'])->name('category-distribution');
        Route::get('/movement-summary', [SupervisorInventoryDashboardController::class, 'movementSummary'])->name('movement-summary');
    });

    /* Stock Issues Routes */
    Route::prefix('stock-issues')->name('stock-issues.')->group(function () {
        Route::get('/export', [SupervisorStockIssueController::class, 'export'])->name('export');
        Route::get('/get-available-serials', [SupervisorStockIssueController::class, 'getAvailableSerials'])->name('get-available-serials');
        Route::get('/get-technician-serials', [SupervisorStockIssueController::class, 'getTechnicianSerials'])->name('get-technician-serials');
        Route::post('/{stockIssue}/post', [SupervisorStockIssueController::class, 'post'])->name('post');
        Route::get('/{stockIssue}/print', [SupervisorStockIssueController::class, 'print'])->name('print');
        Route::get('/', [SupervisorStockIssueController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorStockIssueController::class, 'create'])->name('create');
        Route::post('/', [SupervisorStockIssueController::class, 'store'])->name('store');
        Route::get('/{stockIssue}', [SupervisorStockIssueController::class, 'show'])->name('show');
        Route::get('/{stockIssue}/edit', [SupervisorStockIssueController::class, 'edit'])->name('edit');
        Route::put('/{stockIssue}', [SupervisorStockIssueController::class, 'update'])->name('update');
        Route::patch('/{stockIssue}', [SupervisorStockIssueController::class, 'update'])->name('update');
        Route::delete('/{stockIssue}', [SupervisorStockIssueController::class, 'destroy'])->name('destroy');
    });

    /* Stock Returns Routes */
    Route::prefix('stock-returns')->name('stock-returns.')->group(function () {
        Route::get('/', [SupervisorStockReturnController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorStockReturnController::class, 'create'])->name('create');
        Route::post('/', [SupervisorStockReturnController::class, 'store'])->name('store');
        Route::get('/{stockReturn}', [SupervisorStockReturnController::class, 'show'])->name('show');
        Route::get('/{stockReturn}/edit', [SupervisorStockReturnController::class, 'edit'])->name('edit');
        Route::put('/{stockReturn}', [SupervisorStockReturnController::class, 'update'])->name('update');
        Route::post('/{stockReturn}/post', [SupervisorStockReturnController::class, 'post'])->name('post');
        Route::post('/{stockReturn}/cancel', [SupervisorStockReturnController::class, 'cancel'])->name('cancel');
        Route::get('/{stockReturn}/print', [SupervisorStockReturnController::class, 'print'])->name('print');
        Route::get('/technician/inventory', [SupervisorStockReturnController::class, 'getTechnicianInventory'])->name('technician-inventory');
        Route::get('/technician/summary', [SupervisorStockReturnController::class, 'getTechnicianInventorySummary'])->name('technician-summary');
        Route::get('/export/excel', [SupervisorStockReturnController::class, 'export'])->name('export');
    });

    /* Stock Transfers Routes */
    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        Route::get('/', [SupervisorStockTransferController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorStockTransferController::class, 'create'])->name('create');
        Route::post('/', [SupervisorStockTransferController::class, 'store'])->name('store');
        Route::get('/{stockTransfer}', [SupervisorStockTransferController::class, 'show'])->name('show');
        Route::get('/{stockTransfer}/approve', [SupervisorStockTransferController::class, 'approveForm'])->name('approve-form');
        Route::post('/{stockTransfer}/approve', [SupervisorStockTransferController::class, 'approve'])->name('approve');
        Route::get('/{stockTransfer}/receive', [SupervisorStockTransferController::class, 'receiveForm'])->name('receive-form');
        Route::post('/{stockTransfer}/receive', [SupervisorStockTransferController::class, 'receive'])->name('receive');
        Route::get('/{stockTransfer}/print', [SupervisorStockTransferController::class, 'print'])->name('print');
        Route::get('/export', [SupervisorStockTransferController::class, 'export'])->name('export');
        Route::get('/available-stock', [SupervisorStockTransferController::class, 'getAvailableStock'])->name('available-stock');
    });

    /* Stock Adjustments Routes */
    Route::prefix('stock-adjustments')->name('stock-adjustments.')->group(function () {
        Route::get('/', [SupervisorStockAdjustmentController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorStockAdjustmentController::class, 'create'])->name('create');
        Route::post('/', [SupervisorStockAdjustmentController::class, 'store'])->name('store');
        Route::get('/{stockAdjustment}', [SupervisorStockAdjustmentController::class, 'show'])->name('show');
        Route::get('/{stockAdjustment}/edit', [SupervisorStockAdjustmentController::class, 'edit'])->name('edit');
        Route::put('/{stockAdjustment}', [SupervisorStockAdjustmentController::class, 'update'])->name('update');
        Route::delete('/{stockAdjustment}', [SupervisorStockAdjustmentController::class, 'destroy'])->name('destroy');
        Route::post('/{stockAdjustment}/submit', [SupervisorStockAdjustmentController::class, 'submit'])->name('submit');
        Route::get('/ajax/depot-stock', [SupervisorStockAdjustmentController::class, 'getDepotStock'])->name('get-depot-stock');
        Route::get('/reports/variance', [SupervisorStockAdjustmentController::class, 'varianceReport'])->name('variance-report');
        Route::get('/export/excel', [SupervisorStockAdjustmentController::class, 'export'])->name('export');
        Route::get('/export/variance', [SupervisorStockAdjustmentController::class, 'exportVariance'])->name('export-variance');
    });

    /* Quatation Routes */
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/export', [SupervisorQuotationController::class, 'export'])->name('export');
        Route::get('/', [SupervisorQuotationController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorQuotationController::class, 'create'])->name('create');
        Route::post('/', [SupervisorQuotationController::class, 'store'])->name('store');
        Route::get('/{quotation}', [SupervisorQuotationController::class, 'show'])->name('show');
        Route::get('/{quotation}/edit', [SupervisorQuotationController::class, 'edit'])->name('edit');
        Route::put('/{quotation}', [SupervisorQuotationController::class, 'update'])->name('update');
        Route::delete('/{quotation}', [SupervisorQuotationController::class, 'destroy'])->name('destroy');
        Route::post('/{quotation}/submit-approval', [SupervisorQuotationController::class, 'submitForApproval'])->name('submit-approval');
        Route::post('/{quotation}/process-approval', [SupervisorQuotationController::class, 'processApproval'])->name('process-approval');
        Route::post('/{quotation}/send', [SupervisorQuotationController::class, 'send'])->name('send');
        Route::post('/{quotation}/convert-to-po', [SupervisorQuotationController::class, 'convertToPO'])->name('convert-to-po');
        Route::get('/{quotation}/print', [SupervisorQuotationController::class, 'print'])->name('print');
        Route::get('/model-price/{model}', [SupervisorQuotationController::class, 'getModelPrice'])->name('model-price');
        Route::get('/charge-price/{charge}', [SupervisorQuotationController::class, 'getChargePrice'])->name('charge-price');
        Route::get('{quotation}/pdf/download', [SupervisorQuotationController::class, 'downloadPdf'])->name('pdf.download');
        Route::get('{quotation}/pdf/preview', [SupervisorQuotationController::class, 'previewPdf'])->name('pdf.preview');
        Route::post('{quotation}/pdf/email', [SupervisorQuotationController::class, 'emailPdf'])->name('pdf.email');
    });

    /* Purchase Orders */
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [SupervisorPOController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorPOController::class, 'create'])->name('create');
        Route::post('/', [SupervisorPOController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [SupervisorPOController::class, 'show'])->name('show');
        Route::get('/{purchaseOrder}/edit', [SupervisorPOController::class, 'edit'])->name('edit');
        Route::put('/{purchaseOrder}', [SupervisorPOController::class, 'update'])->name('update');
        Route::get('/{purchaseOrder}/pdf', [SupervisorPOController::class, 'pdf'])->name('pdf');
    });

    /* GNRS Routes */
    Route::prefix('grns')->name('grns.')->group(function () {
        Route::get('/', [SupervisorGrnController::class, 'index'])->name('index');
        Route::get('/create', [SupervisorGrnController::class, 'create'])->name('create');
        Route::post('/', [SupervisorGrnController::class, 'store'])->name('store');
        Route::get('/{grn}', [SupervisorGrnController::class, 'show'])->name('show');
        Route::get('/purchase-orders/{po}/details', [SupervisorGrnController::class, 'getPurchaseOrderDetails'])->name('po-details');
        Route::post('/{grn}/post', [SupervisorGrnController::class, 'post'])->name('post');
        Route::post('/validate-serial', [SupervisorGrnController::class, 'validateSerial'])->name('validate-serial');
        Route::get('/{grn}/pdf', [SupervisorGrnController::class, 'pdf'])->name('pdf');
        Route::get('/{grn}/download', [SupervisorGrnController::class, 'downloadPdf'])->name('download');
    });

    /* GRN Reports Routes (Supervisor - team-scoped) */
    Route::prefix('grn-reports')->name('grn-reports.')->middleware('permission:view_reports_grns')->group(function () {
        Route::get('/', [SupervisorGrnReportController::class, 'index'])->name('index');
        Route::get('/register', [SupervisorGrnReportController::class, 'register'])->name('register');
        Route::get('/receiving-by-vendor', [SupervisorGrnReportController::class, 'receivingByVendor'])->name('receiving-by-vendor');
        Route::get('/receiving-by-model', [SupervisorGrnReportController::class, 'receivingByModel'])->name('receiving-by-model');

        // Exports
        Route::get('/export-list', [SupervisorGrnReportController::class, 'exportList'])->name('export-list');
        Route::get('/export-register', [SupervisorGrnReportController::class, 'exportRegister'])->name('export-register');
        Route::get('/export-receiving-by-vendor', [SupervisorGrnReportController::class, 'exportReceivingByVendor'])->name('export-receiving-by-vendor');
        Route::get('/export-receiving-by-model', [SupervisorGrnReportController::class, 'exportReceivingByModel'])->name('export-receiving-by-model');
    });

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [SupervisorTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorTicketController::class, 'datatable'])->name('datatable');
        Route::get('/create', [SupervisorTicketController::class, 'create'])->name('create');
        Route::post('/', [SupervisorTicketController::class, 'store'])->name('store');
        Route::get('/ajax/vendor-branches', [SupervisorTicketController::class, 'getVendorBranches'])->name('ajax.vendor-branches');
        Route::get('/ajax/cities', [SupervisorTicketController::class, 'getCities'])->name('ajax.cities');
        Route::get('/ajax/technicians', [SupervisorTicketController::class, 'getTechnicians'])->name('ajax.technicians');
        Route::get('/ajax/supervisor-mileage-rate', [SupervisorTicketController::class, 'getSupervisorMileageRate'])->name('ajax.supervisor-mileage-rate');
        Route::get('/ajax/charges', [SupervisorTicketController::class, 'getCharges'])->name('ajax.charges');
        Route::get('/ajax/supervisors', [AdminTicketController::class, 'getSupervisors'])->name('ajax.supervisors');
        Route::get('/{ticket}', [SupervisorTicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [SupervisorTicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [SupervisorTicketController::class, 'update'])->name('update');
        Route::post('/{ticket}/change-status', [SupervisorTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/assign', [SupervisorTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/update-claim', [SupervisorTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/comment', [SupervisorTicketController::class, 'addComment'])->name('comment');
    });

    /* Claim management */
    Route::prefix('claims')->name('claims.')->group(function () {
        // Landing
        Route::get('/', [SupervisorClaimController::class, 'index'])->name('index');

        // Ticket Claims (Create + View)
        Route::get('/ticket-claims', [SupervisorClaimController::class, 'ticketClaims'])->name('ticket-claims');
        Route::get('/ajax/ticket-claims-data', [SupervisorClaimController::class, 'ticketClaimsData'])->name('ticket-claims-data');

        // Other Claims (Create + View)
        Route::get('/other-claims', [SupervisorClaimController::class, 'otherClaims'])->name('other-claims');
        Route::get('/ajax/other-claims-data', [SupervisorClaimController::class, 'otherClaimsData'])->name('other-claims-data');
        Route::get('/create', [SupervisorClaimController::class, 'create'])->name('create');
        Route::post('/store', [SupervisorClaimController::class, 'store'])->name('store');

        // Show (parameterized - MUST be last)
        Route::get('/{claim}', [SupervisorClaimController::class, 'show'])->name('show');
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

    /* Depots Routes */
    Route::prefix('depots')->name('depots.')->group(function () {
        Route::get('/', [TechnicianDepotController::class, 'index'])->name('index');
        Route::get('/{depot}', [TechnicianDepotController::class, 'show'])->name('show');
        Route::get('/{depot}/stock-summary', [TechnicianDepotController::class, 'stockSummary'])->name('stock-summary');
    });

    /* My Jobs */
    Route::get('/jobs', function () {
        return 'My Jobs - Technician Only';
    })->name('jobs.index');

    /* My Stock (Inventory Serials) */
    Route::prefix('inventory-serials')->name('inventory-serials.')->group(function () {
        Route::get('/datatable', [TechnicianInventorySerialController::class, 'datatable'])->name('datatable');
        Route::get('/', [TechnicianInventorySerialController::class, 'index'])->name('index');
        Route::get('/{inventorySerial}', [TechnicianInventorySerialController::class, 'show'])->name('show');
    });

    /* Serial Movement History Routes */
    Route::prefix('serial-movement-history')->name('serial-movement-history.')->group(function () {
        Route::get('/datatable', [TechnicianSerialMovementHistoryController::class, 'datatable'])->name('datatable');
        Route::get('/', [TechnicianSerialMovementHistoryController::class, 'index'])->name('index');
        Route::get('/{serialId}', [TechnicianSerialMovementHistoryController::class, 'show'])->name('show');
        Route::get('/{serialId}/timeline', [TechnicianSerialMovementHistoryController::class, 'timeline'])->name('timeline');
    });

    /* Bulk-serials Routes */
    Route::prefix('bulk-serials')->name('bulk-serials.')->group(function () {
        Route::get('/', [TechnicianBulkSerialController::class, 'index'])->name('index');
        Route::post('/generate-labels', [TechnicianBulkSerialController::class, 'generateLabels'])->name('generate-labels');
        Route::post('/export-serials', [TechnicianBulkSerialController::class, 'exportSerials'])->name('export-serials');
    });

    /* Stock Ledger Routes */
    Route::prefix('stock-ledger')->name('stock-ledger.')->group(function () {
        Route::get('/', [TechnicianStockLedgerController::class, 'index'])->name('index');
        Route::get('/{stockLedger}', [TechnicianStockLedgerController::class, 'show'])->name('show');
    });

    /* Stock Balance Routes */
    Route::prefix('stock-balance')->name('stock-balance.')->group(function () {
        Route::get('/', [TechnicianStockBalanceController::class, 'index'])->name('index');
    });

    /* Stock Reports Routes */
    Route::prefix('stock-reports')->name('stock-reports.')->group(function () {
        Route::get('/my-inventory', [TechnicianStockReportController::class, 'myInventory'])->name('my-inventory');
        Route::get('/stock-card/{serialId?}', [TechnicianStockReportController::class, 'stockCard'])->name('stock-card');
        Route::get('/stock-card/{serialId}/export', [TechnicianStockReportController::class, 'exportStockCard'])->name('stock-card-export');
        Route::get('/stock-card/{serialId}/print', [TechnicianStockReportController::class, 'printStockCard'])->name('stock-card-print');
        Route::get('/search-serials', [TechnicianStockReportController::class, 'searchSerials'])->name('search-serials');
    });

    /* Inventory Dashboard Routes */
    Route::prefix('inventory-dashboard')->name('inventory-dashboard.')->group(function () {
        Route::get('/', [TechnicianInventoryDashboardController::class, 'index'])->name('index');
        Route::get('/stock-by-category', [TechnicianInventoryDashboardController::class, 'stockByCategory'])->name('stock-by-category');
        Route::get('/low-stock-alerts', [TechnicianInventoryDashboardController::class, 'lowStockAlerts'])->name('low-stock-alerts');
        Route::get('/recent-movements', [TechnicianInventoryDashboardController::class, 'recentMovements'])->name('recent-movements');
        Route::get('/stock-aging', [TechnicianInventoryDashboardController::class, 'stockAging'])->name('stock-aging');
        Route::get('/top-models', [TechnicianInventoryDashboardController::class, 'topModels'])->name('top-models');
        Route::get('/movement-trend', [TechnicianInventoryDashboardController::class, 'movementTrend'])->name('movement-trend');
        Route::get('/category-distribution', [TechnicianInventoryDashboardController::class, 'categoryDistribution'])->name('category-distribution');
        Route::get('/movement-summary', [TechnicianInventoryDashboardController::class, 'movementSummary'])->name('movement-summary');
    });

    /* Stock Issues */
    Route::prefix('stock-issues')->name('stock-issues.')->group(function () {
        Route::get('/', [TechnicianStockIssueController::class, 'index'])->name('index');
        Route::get('/{stockIssue}', [TechnicianStockIssueController::class, 'show'])->name('show');
    });

    /* Stock Returns */
    Route::prefix('stock-returns')->name('stock-returns.')->group(function () {
        Route::get('/', [TechnicianStockReturnController::class, 'index'])->name('index');
        Route::get('/create', [TechnicianStockReturnController::class, 'create'])->name('create');
        Route::post('/', [TechnicianStockReturnController::class, 'store'])->name('store');
        Route::get('/{stockReturn}', [TechnicianStockReturnController::class, 'show'])->name('show');
        Route::get('/{stockReturn}/edit', [TechnicianStockReturnController::class, 'edit'])->name('edit');
        Route::put('/{stockReturn}', [TechnicianStockReturnController::class, 'update'])->name('update');
        Route::get('/{stockReturn}/print', [TechnicianStockReturnController::class, 'print'])->name('print');
        Route::get('/my-inventory', [TechnicianStockReturnController::class, 'getMyInventory'])->name('my-inventory');
    });

    /* Stock Transfers */
    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        Route::get('/', [TechnicianStockTransferController::class, 'index'])->name('index');
        Route::get('/{stockTransfer}', [TechnicianStockTransferController::class, 'show'])->name('show');
    });

    /* My Inventory Routes */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [TechnicianInventoryController::class, 'index'])->name('index');
        Route::get('/summary', [TechnicianInventoryController::class, 'summary'])->name('summary');
        Route::get('/return-request', [TechnicianInventoryController::class, 'returnRequest'])->name('return-request');
        Route::get('/get-serial-details', [TechnicianInventoryController::class, 'getSerialDetails'])->name('get-serial-details');
        Route::get('/{serial}', [TechnicianInventoryController::class, 'show'])->name('show');
    });

    /* Quatation Routes */
    Route::prefix('quotations')->name('quotations.')->group(function () {
        Route::get('/', [TechnicianQuotationController::class, 'index'])->name('index');
        Route::get('/{quotation}', [TechnicianQuotationController::class, 'show'])->name('show');
    });

    /* Purchase Orders */
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [TechnicianPOController::class, 'index'])->name('index');
        Route::get('/{purchaseOrder}', [TechnicianPOController::class, 'show'])->name('show');
        Route::get('/{purchaseOrder}/pdf', [TechnicianPOController::class, 'pdf'])->name('pdf');
    });

    /* GNRS Routes */
    Route::prefix('grns')->name('grns.')->group(function () {
        Route::get('/', [TechnicianGrnController::class, 'index'])->name('index');
        Route::get('/{grn}', [TechnicianGrnController::class, 'show'])->name('show');
    });

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TechnicianTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [TechnicianTicketController::class, 'datatable'])->name('datatable');
        Route::get('/{ticket}', [TechnicianTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/change-status', [TechnicianTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/update-claim', [TechnicianTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/comment', [TechnicianTicketController::class, 'addComment'])->name('comment');
    });

    /* claim management */
    Route::prefix('claims')->name('claims.')->group(function () {
        // Landing
        Route::get('/', [TechnicianClaimController::class, 'index'])->name('index');

        // Ticket Claims (Create + View) — own tickets only
        Route::get('/ticket-claims', [TechnicianClaimController::class, 'ticketClaims'])->name('ticket-claims');
        Route::get('/ajax/ticket-claims-data', [TechnicianClaimController::class, 'ticketClaimsData'])->name('ticket-claims-data');

        // Other Claims (Create + View)
        Route::get('/other-claims', [TechnicianClaimController::class, 'otherClaims'])->name('other-claims');
        Route::get('/ajax/other-claims-data', [TechnicianClaimController::class, 'otherClaimsData'])->name('other-claims-data');
        Route::get('/create', [TechnicianClaimController::class, 'create'])->name('create');
        Route::post('/store', [TechnicianClaimController::class, 'store'])->name('store');

        // Show (parameterized - MUST be last)
        Route::get('/{claim}', [TechnicianClaimController::class, 'show'])->name('show');
    });
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
