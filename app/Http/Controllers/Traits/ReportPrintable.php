<?php

namespace App\Http\Controllers\Traits;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ReportPrintable Trait
 *
 * Adds a universal printReport() method to any ReportController.
 * Opens report data in a print-friendly new tab with auto window.print().
 *
 * Usage in controller:
 *   use App\Http\Controllers\Traits\ReportPrintable;
 *   class ReportController extends Controller {
 *       use ReportPrintable;
 *   }
 *
 * Route:
 *   Route::get('/reports/print', [ReportController::class, 'printReport'])->name('reports.print');
 */
trait ReportPrintable
{
    /**
     * Render a print-friendly page for any report type.
     *
     * URL: /admin/reports/print?type=ticket-summary&date_from=...
     */
    public function printReport(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view_reports')) abort(403);

        $service    = app(ReportService::class);
        $filters    = $service->extractFilters($request);
        $reportType = $request->input('type', 'ticket-summary');
        $user       = Auth::user();

        // Determine scoping: admin sees all, supervisor sees team, technician sees own
        $scopedUser = $user->hasRole('admin') ? null : $user;

        // Map report type → service query method
        $queryMap = [
            'ticket-summary'       => fn() => $service->ticketSummaryQuery($filters, $scopedUser)->limit(1000)->get(),
            'status'               => fn() => $service->statusReportQuery($filters, $scopedUser)->limit(1000)->get(),
            'sla'                  => fn() => $service->slaReportQuery($filters, $scopedUser)->limit(1000)->get(),
            'supervisor-pricing'   => fn() => $service->supervisorPricingQuery($filters)->limit(1000)->get(),
            'claim'                => fn() => $service->claimReportQuery($filters, $scopedUser)->limit(1000)->get(),
            'payment'              => fn() => $service->paymentReportQuery($filters)->limit(1000)->get(),
            'inventory-balance'    => fn() => $service->inventoryBalanceQuery($filters, $scopedUser)->limit(1000)->get(),
            'router-movement'      => fn() => $service->routerMovementQuery($filters, $scopedUser)->limit(1000)->get(),
            'accessories-usage'    => fn() => $service->accessoriesUsageQuery($filters, $scopedUser)->limit(1000)->get(),
            'rejected-rescheduled' => fn() => $service->rejectedRescheduledQuery($filters, $scopedUser)->limit(1000)->get(),
        ];

        // Title map
        $titleMap = [
            'ticket-summary'       => 'Ticket Summary Report',
            'status'               => 'Status Report',
            'sla'                  => 'SLA Report',
            'supervisor-pricing'   => 'Supervisor Pricing Report',
            'claim'                => 'Claim Report',
            'payment'              => 'Payment Report',
            'inventory-balance'    => 'Inventory Balance Report',
            'router-movement'      => 'Router Movement Report',
            'accessories-usage'    => 'Accessories Usage Report',
            'rejected-rescheduled' => 'Rejected / Rescheduled Report',
        ];

        if (!isset($queryMap[$reportType])) {
            abort(404, 'Unknown report type.');
        }

        try {
            $data = $queryMap[$reportType]();
        } catch (\Throwable $e) {
            abort(500, 'Failed to load report data: ' . $e->getMessage());
        }

        return view('exports.reports.report-print', [
            'title'      => $titleMap[$reportType] ?? 'Report',
            'data'       => $data,
            'reportType' => $reportType,
            'generatedAt'=> now()->format('d/m/Y H:i:s'),
            'generatedBy'=> $user->name,
        ]);
    }
}
