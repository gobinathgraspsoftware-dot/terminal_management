<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GrnReportService;
use App\Exports\GrnsExport;
use App\Exports\GrnRegisterExport;
use App\Exports\ReceivingSummaryByVendorExport;
use App\Exports\ReceivingSummaryByModelExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GrnReportController extends Controller
{
    protected GrnReportService $reportService;

    public function __construct(GrnReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * GRN Reports dashboard
     */
    public function index()
    {
        $filterOptions = $this->reportService->getFilterOptions();

        return view('admin.grn-reports.index', compact('filterOptions'));
    }

    /**
     * GRN Register report
     */
    public function register(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'purchase_order_id',
            'receiving_depot_id', 'status', 'search',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $grns = $this->reportService->getGrnRegister($filters)->paginate(50);
        $summary = $this->reportService->getGrnRegisterSummary($filters);
        $filterOptions = $this->reportService->getFilterOptions();

        return view('admin.grn-reports.register', compact('grns', 'summary', 'filters', 'filterOptions'));
    }

    /**
     * Receiving summary by vendor
     */
    public function receivingByVendor(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'receiving_depot_id', 'status',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $vendorSummary = $this->reportService->getReceivingSummaryByVendor($filters);
        $filterOptions = $this->reportService->getFilterOptions();

        // Calculate grand totals
        $grandTotals = [
            'total_grns'  => $vendorSummary->sum('grn_count'),
            'total_qty'   => $vendorSummary->sum('total_qty_received'),
            'total_value' => $vendorSummary->sum('total_value'),
        ];

        return view('admin.grn-reports.receiving-by-vendor', compact(
            'vendorSummary', 'filters', 'filterOptions', 'grandTotals'
        ));
    }

    /**
     * Receiving summary by model
     */
    public function receivingByModel(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'receiving_depot_id',
            'status', 'category_id', 'model_id',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $modelSummary = $this->reportService->getReceivingSummaryByModel($filters);
        $filterOptions = $this->reportService->getFilterOptions();

        // Calculate grand totals
        $grandTotals = [
            'total_grns'  => $modelSummary->sum('grn_count'),
            'total_qty'   => $modelSummary->sum('total_qty_received'),
            'total_value' => $modelSummary->sum('total_value'),
        ];

        return view('admin.grn-reports.receiving-by-model', compact(
            'modelSummary', 'filters', 'filterOptions', 'grandTotals'
        ));
    }

    // =========================================================================
    // EXPORTS
    // =========================================================================

    /**
     * Export GRN list to Excel
     */
    public function exportList(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'purchase_order_id',
            'receiving_depot_id', 'status', 'search',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $filename = 'grn-list-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new GrnsExport($filters), $filename);
    }

    /**
     * Export GRN register to Excel
     */
    public function exportRegister(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'purchase_order_id',
            'receiving_depot_id', 'status',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $filename = 'grn-register-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new GrnRegisterExport($filters), $filename);
    }

    /**
     * Export receiving by vendor to Excel
     */
    public function exportReceivingByVendor(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'receiving_depot_id', 'status',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $filename = 'receiving-by-vendor-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new ReceivingSummaryByVendorExport($filters), $filename);
    }

    /**
     * Export receiving by model to Excel
     */
    public function exportReceivingByModel(Request $request)
    {
        $filters = $request->only([
            'date_from', 'date_to', 'vendor_id', 'receiving_depot_id',
            'status', 'category_id', 'model_id',
        ]);

        $filters['role'] = 'admin';
        $filters['user'] = auth()->user();

        $filename = 'receiving-by-model-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new ReceivingSummaryByModelExport($filters), $filename);
    }
}
