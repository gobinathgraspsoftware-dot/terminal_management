<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\StockReportService;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\Depot;
use App\Models\User;
use App\Models\InventorySerial;
use App\Exports\StockMovementReportExport;
use App\Exports\StockCardExport;
use App\Exports\StockSummaryExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * Stock reports index/landing page
     */
    public function index()
    {
        return view('supervisor.stock-reports.index');
    }

    /**
     * Movement report (scoped to supervisor's team)
     */
    public function movementReport(Request $request)
    {
        $filters = $request->only([
            'from_date', 'to_date', 'location_type', 'location_id',
            'model_id', 'category_id', 'transaction_type', 'serial_no', 'technician_id'
        ]);

        // Add role-based filtering
        $filters['role'] = 'supervisor';
        $filters['user'] = auth()->user();

        $movements = $this->stockReportService->getMovementReport($filters)->paginate(50);
        $summary = $this->stockReportService->getMovementSummary($filters);

        // Get filter options
        $categories = TerminalCategory::orderBy('category_name')->get();
        $models = TerminalModel::with('category')->orderBy('model_name')->get();
        $depots = Depot::orderBy('depot_name')->get();

        // Get team technicians
        $technicians = User::where('supervisor_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('supervisor.stock-reports.movement', compact(
            'movements',
            'summary',
            'categories',
            'models',
            'depots',
            'technicians',
            'filters'
        ));
    }

    /**
     * Stock card view (single serial - scoped to supervisor's team)
     */
    public function stockCard(Request $request, $serialId = null)
    {
        $stockCardData = null;

        if ($serialId) {
            $stockCardData = $this->stockReportService->getStockCard($serialId);

            // Verify this serial belongs to supervisor's team
            $serial = $stockCardData['serial'];
            if ($serial->current_location_type === 'technician') {
                $technician = User::find($serial->current_location_id);
                if ($technician && $technician->supervisor_id !== auth()->id()) {
                    abort(403, 'Unauthorized access to this serial number.');
                }
            }
        }

        // Get team technicians' serials for search
        $teamTechnicianIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();

        $serials = InventorySerial::with('model')
            ->where(function ($q) use ($teamTechnicianIds) {
                $q->where(function ($sub) use ($teamTechnicianIds) {
                    $sub->where('current_location_type', 'technician')
                        ->whereIn('current_location_id', $teamTechnicianIds);
                })->orWhere('current_location_type', 'depot'); // Depot serials accessible to all
            })
            ->orderBy('serial_no')
            ->limit(100)
            ->get();

        return view('supervisor.stock-reports.stock-card', compact('stockCardData', 'serials'));
    }

    /**
     * Summary report (scoped to supervisor's team)
     */
    public function summaryReport(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'category_id']);

        // Add role-based filtering
        $filters['role'] = 'supervisor';
        $filters['user'] = auth()->user();

        $byLocation = $this->stockReportService->getMovementByLocation($filters);
        $byModel = $this->stockReportService->getMovementByModel($filters);
        $topMovers = $this->stockReportService->getTopMovers(10, $filters);
        $summary = $this->stockReportService->getMovementSummary($filters);

        $categories = TerminalCategory::orderBy('category_name')->get();

        return view('supervisor.stock-reports.summary', compact(
            'byLocation',
            'byModel',
            'topMovers',
            'summary',
            'categories',
            'filters'
        ));
    }

    /**
     * Export movement report to Excel
     */
    public function exportMovement(Request $request)
    {
        $filters = $request->only([
            'from_date', 'to_date', 'location_type', 'location_id',
            'model_id', 'category_id', 'transaction_type', 'serial_no', 'technician_id'
        ]);

        // Add role-based filtering
        $filters['role'] = 'supervisor';
        $filters['user'] = auth()->user();

        $filename = 'stock-movement-report-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockMovementReportExport($filters), $filename);
    }

    /**
     * Export stock card to Excel
     */
    public function exportStockCard(Request $request, int $serialId)
    {
        $filename = 'stock-card-' . $serialId . '-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockCardExport($serialId), $filename);
    }

    /**
     * Export summary report to Excel
     */
    public function exportSummary(Request $request)
    {
        $filters = $request->only(['from_date', 'to_date', 'category_id']);

        // Add role-based filtering
        $filters['role'] = 'supervisor';
        $filters['user'] = auth()->user();

        $filename = 'stock-summary-report-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockSummaryExport($filters), $filename);
    }

    /**
     * Print stock card
     */
    public function printStockCard(Request $request, int $serialId)
    {
        $stockCardData = $this->stockReportService->getStockCard($serialId);

        return view('supervisor.stock-reports.print-stock-card', compact('stockCardData'));
    }

    /**
     * Search serials for stock card (AJAX) - scoped to supervisor's team
     */
    public function searchSerials(Request $request)
    {
        $term = $request->input('term', '');
        $teamTechnicianIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();

        $serials = InventorySerial::with('model')
            ->where('serial_no', 'like', '%' . $term . '%')
            ->where(function ($q) use ($teamTechnicianIds) {
                $q->where(function ($sub) use ($teamTechnicianIds) {
                    $sub->where('current_location_type', 'technician')
                        ->whereIn('current_location_id', $teamTechnicianIds);
                })->orWhere('current_location_type', 'depot');
            })
            ->orderBy('serial_no')
            ->limit(20)
            ->get()
            ->map(function ($serial) {
                return [
                    'id' => $serial->id,
                    'text' => $serial->serial_no . ' - ' . ($serial->model ? $serial->model->model_name : 'Unknown Model'),
                ];
            });

        return response()->json($serials);
    }
}
