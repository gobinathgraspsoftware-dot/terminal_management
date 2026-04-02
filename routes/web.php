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
use App\Http\Controllers\Admin\VendorController as AdminVendorController;
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
use App\Http\Controllers\Admin\JobCategoryController as AdminJobCategoryController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\StockMovementController as AdminStockMovementController;
// REMOVED: Supervisor\InventoryController, Supervisor\StockMovementController, Technician\InventoryController
// Inventory module is now Admin-only
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Supervisor\ReportController as SupervisorReportController;
use App\Http\Controllers\Technician\ReportController as TechnicianReportController;


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
    Route::get('/location/cities', [LocationController::class, 'cities'])->name('location.cities');

    // Clear Cache Route — accessible by all authenticated roles
    Route::get('/clear-cache', function () {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'All caches cleared successfully!');
    })->name('clear-cache');

    // Old Serial Lookup API routes removed
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
        Route::get('/', 'index')->name('index');
        Route::get('/datatable', 'datatable')->name('datatable');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/export', 'export')->name('export');
        Route::get('/ajax/list', 'getUsersList')->name('ajax.list');
        Route::post('/bulk/delete', 'bulkDelete')->name('bulk.delete');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
        Route::post('/{id}/restore', 'restore')->name('restore');
        Route::post('/{user}/change-password', 'changePassword')->name('change-password');
        Route::post('/{user}/assign-role', 'assignRole')->name('assign-role');
        Route::post('/{user}/assign-supervisor', 'assignSupervisor')->name('assign-supervisor');
        Route::post('/{user}/toggle-status', 'toggleStatus')->name('toggle-status');
        Route::get('/{user}/pricing', 'getSupervisorPricing')->name('pricing');
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

        // Vendor code suggestions (MUST be before /{vendor})
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

    /* Job Categories */
    Route::prefix('job-categories')->name('job-categories.')->group(function () {
        Route::get('/datatable', [AdminJobCategoryController::class, 'datatable'])->name('datatable');
        Route::patch('/{job_category}/toggle-status', [AdminJobCategoryController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/', [AdminJobCategoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminJobCategoryController::class, 'create'])->name('create');
        Route::post('/', [AdminJobCategoryController::class, 'store'])->name('store');
        Route::get('/{job_category}/edit', [AdminJobCategoryController::class, 'edit'])->name('edit');
        Route::put('/{job_category}', [AdminJobCategoryController::class, 'update'])->name('update');
        Route::delete('/{job_category}', [AdminJobCategoryController::class, 'destroy'])->name('destroy');
    });

    /* Reporting Module */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])->name('index');

        // Print (opens new tab with filtered data + auto print dialog)
        Route::get('/print', [AdminReportController::class, 'printReport'])->name('print');

        // 1. Ticket Summary
        Route::get('/ticket-summary', [AdminReportController::class, 'ticketSummary'])->name('ticket-summary');
        Route::get('/ticket-summary/data', [AdminReportController::class, 'ticketSummaryData'])->name('ticket-summary.data');
        Route::get('/ticket-summary/export', [AdminReportController::class, 'ticketSummaryExport'])->name('ticket-summary.export');

        // 2. Status Report
        Route::get('/status', [AdminReportController::class, 'statusReport'])->name('status');
        Route::get('/status/data', [AdminReportController::class, 'statusReportData'])->name('status.data');
        Route::get('/status/export', [AdminReportController::class, 'statusReportExport'])->name('status.export');

        // 3. SLA Report
        Route::get('/sla', [AdminReportController::class, 'slaReport'])->name('sla');
        Route::get('/sla/data', [AdminReportController::class, 'slaReportData'])->name('sla.data');
        Route::get('/sla/export', [AdminReportController::class, 'slaReportExport'])->name('sla.export');

        // 4. Supervisor Pricing
        Route::get('/supervisor-pricing', [AdminReportController::class, 'supervisorPricing'])->name('supervisor-pricing');
        Route::get('/supervisor-pricing/data', [AdminReportController::class, 'supervisorPricingData'])->name('supervisor-pricing.data');
        Route::get('/supervisor-pricing/export', [AdminReportController::class, 'supervisorPricingExport'])->name('supervisor-pricing.export');

        // 5. Claim Report
        Route::get('/claim', [AdminReportController::class, 'claimReport'])->name('claim');
        Route::get('/claim/data', [AdminReportController::class, 'claimReportData'])->name('claim.data');
        Route::get('/claim/export', [AdminReportController::class, 'claimReportExport'])->name('claim.export');

        // 6. Payment Report
        Route::get('/payment', [AdminReportController::class, 'paymentReport'])->name('payment');
        Route::get('/payment/data', [AdminReportController::class, 'paymentReportData'])->name('payment.data');
        Route::get('/payment/export', [AdminReportController::class, 'paymentReportExport'])->name('payment.export');

        // 7. Inventory Balance
        Route::get('/inventory-balance', [AdminReportController::class, 'inventoryBalance'])->name('inventory-balance');
        Route::get('/inventory-balance/data', [AdminReportController::class, 'inventoryBalanceData'])->name('inventory-balance.data');
        Route::get('/inventory-balance/export', [AdminReportController::class, 'inventoryBalanceExport'])->name('inventory-balance.export');

        // 8. Router Movement
        Route::get('/router-movement', [AdminReportController::class, 'routerMovement'])->name('router-movement');
        Route::get('/router-movement/data', [AdminReportController::class, 'routerMovementData'])->name('router-movement.data');
        Route::get('/router-movement/export', [AdminReportController::class, 'routerMovementExport'])->name('router-movement.export');

        // 9. Accessories Usage
        Route::get('/accessories-usage', [AdminReportController::class, 'accessoriesUsage'])->name('accessories-usage');
        Route::get('/accessories-usage/data', [AdminReportController::class, 'accessoriesUsageData'])->name('accessories-usage.data');
        Route::get('/accessories-usage/export', [AdminReportController::class, 'accessoriesUsageExport'])->name('accessories-usage.export');

        // 10. Rejected / Rescheduled
        Route::get('/rejected-rescheduled', [AdminReportController::class, 'rejectedRescheduled'])->name('rejected-rescheduled');
        Route::get('/rejected-rescheduled/data', [AdminReportController::class, 'rejectedRescheduledData'])->name('rejected-rescheduled.data');
        Route::get('/rejected-rescheduled/export', [AdminReportController::class, 'rejectedRescheduledExport'])->name('rejected-rescheduled.export');
    });

    /* ══════════════════════════════════════════════════════════
     * INVENTORY MANAGEMENT (Admin-Only)
     * Stock Out = list-only (auto-triggered from tickets)
     * Stock Return = list + manual create form
     * ══════════════════════════════════════════════════════════ */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // DataTable + AJAX + Export (before parameterized routes)
        Route::get('/datatable', [AdminInventoryController::class, 'datatable'])->name('datatable');
        Route::get('/export', [AdminInventoryController::class, 'export'])->name('export');
        Route::get('/ajax/item-stock', [AdminInventoryController::class, 'getItemStock'])->name('get-item-stock');

        // Stock In
        Route::get('/stock-in', [AdminInventoryController::class, 'stockInForm'])->name('stock-in');
        Route::post('/stock-in', [AdminInventoryController::class, 'stockIn'])->name('stock-in.process');

        // Stock Out (list-only — no POST route, auto-triggered from ticket creation)
        Route::get('/stock-out', [AdminInventoryController::class, 'stockOutIndex'])->name('stock-out');
        Route::get('/stock-out/datatable', [AdminInventoryController::class, 'stockOutDatatable'])->name('stock-out.datatable');

        // Stock Return (list-only index + dedicated create page)
        Route::get('/stock-return', [AdminInventoryController::class, 'stockReturnIndex'])->name('stock-return');
        Route::get('/stock-return/datatable', [AdminInventoryController::class, 'stockReturnDatatable'])->name('stock-return.datatable');
        Route::get('/stock-return/create', [AdminInventoryController::class, 'stockReturnCreate'])->name('stock-return.create');
        Route::post('/stock-return', [AdminInventoryController::class, 'stockReturn'])->name('stock-return.process');

        // Stock Adjustment
        Route::get('/stock-adjustment', [AdminInventoryController::class, 'stockAdjustmentForm'])->name('stock-adjustment');
        Route::post('/stock-adjustment', [AdminInventoryController::class, 'stockAdjustment'])->name('stock-adjustment.process');

        // Stock Movements
        Route::get('/movements', [AdminStockMovementController::class, 'index'])->name('movements');
        Route::get('/movements/datatable', [AdminStockMovementController::class, 'datatable'])->name('movements.datatable');
        Route::get('/movements/export', [AdminStockMovementController::class, 'export'])->name('movements.export');

        // CRUD (parameterized routes LAST)
        Route::get('/', [AdminInventoryController::class, 'index'])->name('index');
        Route::get('/create', [AdminInventoryController::class, 'create'])->name('create');
        Route::post('/', [AdminInventoryController::class, 'store'])->name('store');
        Route::get('/{inventory_item}', [AdminInventoryController::class, 'show'])->name('show');
        Route::get('/{inventory_item}/edit', [AdminInventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory_item}', [AdminInventoryController::class, 'update'])->name('update');
        Route::delete('/{inventory_item}', [AdminInventoryController::class, 'destroy'])->name('destroy');
        Route::patch('/{inventory_item}/toggle-status', [AdminInventoryController::class, 'toggleStatus'])->name('toggle-status');
    });

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [AdminTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminTicketController::class, 'datatable'])->name('datatable');
        Route::get('/create', [AdminTicketController::class, 'create'])->name('create');
        Route::post('/', [AdminTicketController::class, 'store'])->name('store');
        // AJAX endpoints — MUST be before /{ticket}
        Route::get('/ajax/vendor-branches', [AdminTicketController::class, 'getVendorBranches'])->name('ajax.vendor-branches');
        Route::get('/ajax/cities', [AdminTicketController::class, 'getCities'])->name('ajax.cities');
        Route::get('/ajax/technicians', [AdminTicketController::class, 'getTechnicians'])->name('ajax.technicians');
        Route::get('/ajax/supervisors', [AdminTicketController::class, 'getSupervisors'])->name('ajax.supervisors');
        Route::get('/ajax/supervisor-mileage-rate', [AdminTicketController::class, 'getSupervisorMileageRate'])->name('ajax.supervisor-mileage-rate');
        Route::get('/ajax/price', [AdminTicketController::class, 'getPrice'])->name('ajax.price');
        Route::get('/ajax/job-category-details', [AdminTicketController::class, 'getJobCategoryDetails'])->name('ajax.job-category-details');
        Route::get('/ajax/available-routers', [AdminTicketController::class, 'getAvailableRouters'])->name('ajax.available-routers');
        Route::get('/ajax/available-accessories', [AdminTicketController::class, 'getAvailableAccessories'])->name('ajax.available-accessories');
        // Parameterized routes
        Route::get('/{ticket}', [AdminTicketController::class, 'show'])->name('show');
        Route::get('/{ticket}/edit', [AdminTicketController::class, 'edit'])->name('edit');
        Route::put('/{ticket}', [AdminTicketController::class, 'update'])->name('update');
        Route::delete('/{ticket}', [AdminTicketController::class, 'destroy'])->name('destroy');
        Route::post('/{ticket}/change-status', [AdminTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/reassign', [AdminTicketController::class, 'reassign'])->name('reassign');
        Route::post('/{ticket}/reassign-supervisor', [AdminTicketController::class, 'reassignSupervisor'])->name('reassign-supervisor');
        Route::post('/{ticket}/update-claim', [AdminTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/update-old-router', [AdminTicketController::class, 'updateOldRouterId'])->name('update-old-router');
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

        // Bulk Payment (DataTable-driven redesign)
        Route::get('/bulk-payment', [AdminClaimController::class, 'bulkPayment'])->name('bulk-payment');
        Route::get('/ajax/bulk-payment-data', [AdminClaimController::class, 'bulkPaymentData'])->name('bulk-payment-data');
        Route::post('/bulk-payment/process', [AdminClaimController::class, 'processBulkPayment'])->name('process-bulk-payment');
        Route::post('/bulk-payment/mark-paid', [AdminClaimController::class, 'markPaid'])->name('mark-paid');

        // Payment History
        Route::get('/payment-history', [AdminClaimController::class, 'paymentHistory'])->name('payment-history');
        Route::get('/ajax/payment-history-data', [AdminClaimController::class, 'paymentHistoryData'])->name('payment-history-data');

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

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [SupervisorTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [SupervisorTicketController::class, 'datatable'])->name('datatable');
        Route::get('/{ticket}', [SupervisorTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/change-status', [SupervisorTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/assign', [SupervisorTicketController::class, 'assign'])->name('assign');
        Route::post('/{ticket}/reassign', [SupervisorTicketController::class, 'reassign'])->name('reassign');
        Route::post('/{ticket}/update-claim', [SupervisorTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/update-old-router', [SupervisorTicketController::class, 'updateOldRouterId'])->name('update-old-router');
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

        // Payment History
        Route::get('/payment-history', [SupervisorClaimController::class, 'paymentHistory'])->name('payment-history');
        Route::get('/ajax/payment-history-data', [SupervisorClaimController::class, 'paymentHistoryData'])->name('payment-history-data');

        // Show (parameterized - MUST be last)
        Route::get('/{claim}', [SupervisorClaimController::class, 'show'])->name('show');
    });

    /* Reporting Module (Team-scoped) */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [SupervisorReportController::class, 'index'])->name('index');

        // Print
        Route::get('/print', [SupervisorReportController::class, 'printReport'])->name('print');

        Route::get('/ticket-summary', [SupervisorReportController::class, 'ticketSummary'])->name('ticket-summary');
        Route::get('/ticket-summary/data', [SupervisorReportController::class, 'ticketSummaryData'])->name('ticket-summary.data');
        Route::get('/ticket-summary/export', [SupervisorReportController::class, 'ticketSummaryExport'])->name('ticket-summary.export');

        Route::get('/status', [SupervisorReportController::class, 'statusReport'])->name('status');
        Route::get('/status/data', [SupervisorReportController::class, 'statusReportData'])->name('status.data');
        Route::get('/status/export', [SupervisorReportController::class, 'statusReportExport'])->name('status.export');

        Route::get('/sla', [SupervisorReportController::class, 'slaReport'])->name('sla');
        Route::get('/sla/data', [SupervisorReportController::class, 'slaReportData'])->name('sla.data');
        Route::get('/sla/export', [SupervisorReportController::class, 'slaReportExport'])->name('sla.export');

        Route::get('/claim', [SupervisorReportController::class, 'claimReport'])->name('claim');
        Route::get('/claim/data', [SupervisorReportController::class, 'claimReportData'])->name('claim.data');
        Route::get('/claim/export', [SupervisorReportController::class, 'claimReportExport'])->name('claim.export');

        Route::get('/inventory-balance', [SupervisorReportController::class, 'inventoryBalance'])->name('inventory-balance');
        Route::get('/inventory-balance/data', [SupervisorReportController::class, 'inventoryBalanceData'])->name('inventory-balance.data');
        Route::get('/inventory-balance/export', [SupervisorReportController::class, 'inventoryBalanceExport'])->name('inventory-balance.export');

        Route::get('/router-movement', [SupervisorReportController::class, 'routerMovement'])->name('router-movement');
        Route::get('/router-movement/data', [SupervisorReportController::class, 'routerMovementData'])->name('router-movement.data');
        Route::get('/router-movement/export', [SupervisorReportController::class, 'routerMovementExport'])->name('router-movement.export');

        Route::get('/accessories-usage', [SupervisorReportController::class, 'accessoriesUsage'])->name('accessories-usage');
        Route::get('/accessories-usage/data', [SupervisorReportController::class, 'accessoriesUsageData'])->name('accessories-usage.data');
        Route::get('/accessories-usage/export', [SupervisorReportController::class, 'accessoriesUsageExport'])->name('accessories-usage.export');

        Route::get('/rejected-rescheduled', [SupervisorReportController::class, 'rejectedRescheduled'])->name('rejected-rescheduled');
        Route::get('/rejected-rescheduled/data', [SupervisorReportController::class, 'rejectedRescheduledData'])->name('rejected-rescheduled.data');
        Route::get('/rejected-rescheduled/export', [SupervisorReportController::class, 'rejectedRescheduledExport'])->name('rejected-rescheduled.export');
    });

    /* REMOVED: Supervisor Inventory Routes — Inventory is now Admin-only */

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

    /* My Jobs */
    Route::get('/jobs', function () {
        return 'My Jobs - Technician Only';
    })->name('jobs.index');

    /* Ticket Management Routes */
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TechnicianTicketController::class, 'index'])->name('index');
        Route::get('/datatable', [TechnicianTicketController::class, 'datatable'])->name('datatable');
        Route::get('/{ticket}', [TechnicianTicketController::class, 'show'])->name('show');
        Route::post('/{ticket}/change-status', [TechnicianTicketController::class, 'changeStatus'])->name('change-status');
        Route::post('/{ticket}/update-claim', [TechnicianTicketController::class, 'updateClaim'])->name('update-claim');
        Route::post('/{ticket}/update-old-router', [TechnicianTicketController::class, 'updateOldRouterId'])->name('update-old-router');
        Route::post('/{ticket}/comment', [TechnicianTicketController::class, 'addComment'])->name('comment');
    });

    /* claim management */
    Route::prefix('claims')->name('claims.')->group(function () {
        // Landing
        Route::get('/', [TechnicianClaimController::class, 'index'])->name('index');

        // Ticket Claims (View Only) — own tickets only
        Route::get('/ticket-claims', [TechnicianClaimController::class, 'ticketClaims'])->name('ticket-claims');
        Route::get('/ajax/ticket-claims-data', [TechnicianClaimController::class, 'ticketClaimsData'])->name('ticket-claims-data');

        // Other Claims (Create + Edit + View)
        Route::get('/other-claims', [TechnicianClaimController::class, 'otherClaims'])->name('other-claims');
        Route::get('/ajax/other-claims-data', [TechnicianClaimController::class, 'otherClaimsData'])->name('other-claims-data');
        Route::get('/create', [TechnicianClaimController::class, 'create'])->name('create');
        Route::post('/store', [TechnicianClaimController::class, 'store'])->name('store');

        // Edit & Update (specific routes BEFORE parameterized /{claim})
        Route::get('/edit/{claim}', [TechnicianClaimController::class, 'edit'])->name('edit');
        Route::put('/update/{claim}', [TechnicianClaimController::class, 'update'])->name('update');

        // Delete Attachment (specific route BEFORE parameterized /{claim})
        Route::delete('/{claim}/attachment/{attachment}', [TechnicianClaimController::class, 'deleteAttachment'])->name('delete-attachment');

        // Payment History (specific route BEFORE parameterized /{claim})
        Route::get('/payment-history', [TechnicianClaimController::class, 'paymentHistory'])->name('payment-history');
        Route::get('/ajax/payment-history-data', [TechnicianClaimController::class, 'paymentHistoryData'])->name('payment-history-data');

        // Show (parameterized - MUST be last)
        Route::get('/{claim}', [TechnicianClaimController::class, 'show'])->name('show');
    });

    /* Reporting Module (Own-scoped) */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [TechnicianReportController::class, 'index'])->name('index');

        // Print
        Route::get('/print', [TechnicianReportController::class, 'printReport'])->name('print');

        Route::get('/ticket-summary', [TechnicianReportController::class, 'ticketSummary'])->name('ticket-summary');
        Route::get('/ticket-summary/data', [TechnicianReportController::class, 'ticketSummaryData'])->name('ticket-summary.data');
        Route::get('/ticket-summary/export', [TechnicianReportController::class, 'ticketSummaryExport'])->name('ticket-summary.export');

        Route::get('/claim', [TechnicianReportController::class, 'claimReport'])->name('claim');
        Route::get('/claim/data', [TechnicianReportController::class, 'claimReportData'])->name('claim.data');
        Route::get('/claim/export', [TechnicianReportController::class, 'claimReportExport'])->name('claim.export');
    });

    /* REMOVED: Technician Inventory Routes — Inventory is now Admin-only */
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
