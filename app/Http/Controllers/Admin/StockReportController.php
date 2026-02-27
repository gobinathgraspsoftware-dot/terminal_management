<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StockReportService;
use App\Models\InventorySerial;
use App\Models\StockLedger;
use App\Models\StockBalance;
use App\Models\TerminalModel;
use App\Models\TerminalCategory;
use App\Models\Depot;
use App\Exports\StockMovementReportExport;
use App\Exports\StockSummaryExport;
use App\Exports\StockCardExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class StockReportController extends Controller
{
    protected StockReportService $stockReportService;

    public function __construct(StockReportService $stockReportService)
    {
        $this->stockReportService = $stockReportService;
    }

    /**
     * Stock Reports Dashboard
     */
    public function index(Request $request)
    {
        // Quick stats
        $stats = [
            'total_serials' => InventorySerial::count(),
            'in_stock' => InventorySerial::where('current_status', 'in_stock')->count(),
            'issued' => InventorySerial::whereIn('current_status', ['issued', 'issued_to_tech'])->count(),
            'installed' => InventorySerial::where('current_status', 'installed')->count(),
            'faulty' => InventorySerial::where('current_status', 'faulty')->count(),
            'total_movements_today' => StockLedger::whereDate('transaction_date', today())->count(),
            'total_movements_this_month' => StockLedger::whereMonth('transaction_date', now()->month)
                ->whereYear('transaction_date', now()->year)->count(),
        ];

        // Recent movements (last 10) — FIX: use 'createdBy' instead of 'user'
        $recentMovements = StockLedger::with(['serial.model', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Stock by status
        $stockByStatus = InventorySerial::select('current_status', DB::raw('count(*) as count'))
            ->groupBy('current_status')
            ->get()
            ->pluck('count', 'current_status');

        // Stock by location type
        $stockByLocationType = InventorySerial::select('current_location_type', DB::raw('count(*) as count'))
            ->whereNotNull('current_location_type')
            ->groupBy('current_location_type')
            ->get()
            ->pluck('count', 'current_location_type');

        return view('admin.stock-reports.index', compact(
            'stats',
            'recentMovements',
            'stockByStatus',
            'stockByLocationType'
        ));
    }

    /**
     * Movement Report
     */
    public function movement(Request $request)
    {
        $filters = $request->only([
            'from_date', 'to_date', 'serial_no', 'model_id',
            'category_id', 'transaction_type', 'location_type', 'location_id'
        ]);

        $query = StockLedger::with(['serial.model', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($filters['from_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['serial_no'])) {
            $query->whereHas('serial', function ($q) use ($filters) {
                $q->where('serial_no', 'like', '%' . $filters['serial_no'] . '%');
            });
        }
        if (!empty($filters['model_id'])) {
            $query->whereHas('serial', function ($q) use ($filters) {
                $q->where('model_id', $filters['model_id']);
            });
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('serial.model', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['location_type']) && !empty($filters['location_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where(function ($sub) use ($filters) {
                    $sub->where('from_location_type', $filters['location_type'])
                        ->where('from_location_id', $filters['location_id']);
                })->orWhere(function ($sub) use ($filters) {
                    $sub->where('to_location_type', $filters['location_type'])
                        ->where('to_location_id', $filters['location_id']);
                });
            });
        }

        $summary = [
            'total_movements' => (clone $query)->count(),
            'total_in' => (clone $query)->where('quantity', '>', 0)->sum('quantity'),
            'total_out' => (clone $query)->where('quantity', '<', 0)->sum('quantity'),
        ];

        $movements = $query->paginate(50)->appends($filters);

        $categories = TerminalCategory::orderBy('category_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();
        $depots = Depot::orderBy('depot_name')->get();

        return view('admin.stock-reports.movement', compact(
            'movements', 'summary', 'filters', 'categories', 'models', 'depots'
        ));
    }

    /**
     * Stock Card View
     */
    public function stockCard(Request $request, $serialId = null)
    {
        $stockCardData = null;

        if ($serialId) {
            $stockCardData = $this->stockReportService->getStockCard($serialId);
        }

        $serials = InventorySerial::with('model')
            ->orderBy('serial_no')
            ->get();

        return view('admin.stock-reports.stock-card', compact('stockCardData', 'serials'));
    }

    /**
     * Summary Report
     */
    public function summary(Request $request)
    {
        // Stock by Model
        $byModel = InventorySerial::select(
            'model_id',
            DB::raw('count(*) as total'),
            DB::raw('sum(case when current_status = "in_stock" then 1 else 0 end) as in_stock'),
            DB::raw('sum(case when current_status in ("issued","issued_to_tech") then 1 else 0 end) as issued'),
            DB::raw('sum(case when current_status = "installed" then 1 else 0 end) as installed'),
            DB::raw('sum(case when current_status = "faulty" then 1 else 0 end) as faulty')
        )
            ->with('model.category')
            ->groupBy('model_id')
            ->havingRaw('total > 0')
            ->orderByDesc('total')
            ->get();

        // Stock by Depot — FIX: correct column names
        $byDepot = StockBalance::select(
            'location_id',
            'location_type',
            'model_id',
            DB::raw('sum(quantity_on_hand) as on_hand'),
            DB::raw('sum(quantity_reserved) as reserved'),
            DB::raw('sum(quantity_on_hand - quantity_reserved) as available'),
            DB::raw('sum(quantity_on_hand) as total')
        )
            ->where('location_type', 'depot')
            ->with(['model', 'depot'])
            ->groupBy('location_id', 'location_type', 'model_id')
            ->havingRaw('total > 0')
            ->orderByDesc('total')
            ->get();

        // Stock by Status
        $byStatus = InventorySerial::select(
            'current_status',
            DB::raw('count(*) as count'),
            DB::raw('ROUND((count(*) * 100.0 / (SELECT count(*) FROM inventory_serials)), 2) as percentage')
        )
            ->groupBy('current_status')
            ->orderByDesc('count')
            ->get();

        // Stock Aging
        $aging = InventorySerial::select(
            DB::raw('CASE
                WHEN grn_date IS NULL THEN "Unknown"
                WHEN DATEDIFF(NOW(), grn_date) <= 90 THEN "0-3 months"
                WHEN DATEDIFF(NOW(), grn_date) <= 180 THEN "3-6 months"
                WHEN DATEDIFF(NOW(), grn_date) <= 365 THEN "6-12 months"
                ELSE "Over 1 year"
            END as age_group'),
            DB::raw('count(*) as count')
        )
            ->groupBy('age_group')
            ->orderByRaw('FIELD(age_group, "0-3 months", "3-6 months", "6-12 months", "Over 1 year", "Unknown")')
            ->get();

        // Low stock alerts
        $lowStock = StockBalance::select(
            'model_id',
            'location_type',
            'location_id',
            DB::raw('sum(quantity_on_hand) as on_hand'),
            DB::raw('sum(quantity_reserved) as reserved'),
            DB::raw('sum(quantity_on_hand - quantity_reserved) as available')
        )
            ->with(['model', 'depot'])
            ->where('location_type', 'depot')
            ->groupBy('model_id', 'location_type', 'location_id')
            ->havingRaw('sum(quantity_on_hand - quantity_reserved) <= 5')
            ->orderByRaw('sum(quantity_on_hand - quantity_reserved) asc')
            ->get();

        return view('admin.stock-reports.summary', compact(
            'byModel', 'byDepot', 'byStatus', 'aging', 'lowStock'
        ));
    }

    /**
     * Export Movement Report
     */
    public function exportMovement(Request $request)
    {
        $filters = $request->only([
            'from_date', 'to_date', 'serial_no', 'model_id',
            'category_id', 'transaction_type', 'location_type', 'location_id'
        ]);

        $filename = 'stock-movement-report-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockMovementReportExport($filters), $filename);
    }

    /**
     * Export Summary Report
     */
    public function exportSummary(Request $request)
    {
        $filename = 'stock-summary-report-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockSummaryExport(), $filename);
    }

    /**
     * Export Stock Card
     */
    public function exportStockCard(Request $request, int $serialId)
    {
        $filename = 'stock-card-' . $serialId . '-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(new StockCardExport($serialId), $filename);
    }

    /**
     * Print Stock Card
     */
    public function printStockCard(Request $request, int $serialId)
    {
        $stockCardData = $this->stockReportService->getStockCard($serialId);

        return view('admin.stock-reports.print-stock-card', compact('stockCardData'));
    }

    /**
     * Search serials (AJAX)
     */
    public function searchSerials(Request $request)
    {
        $term = $request->input('term', '');

        $serials = InventorySerial::with('model')
            ->where('serial_no', 'like', '%' . $term . '%')
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
