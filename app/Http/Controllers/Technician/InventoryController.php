<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\InventorySerial;
use App\Models\StockIssue;
use App\Models\Depot;
use App\Services\StockReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InventoryController extends Controller
{
    use AuthorizesRequests;

    protected StockReturnService $stockReturnService;

    public function __construct(StockReturnService $stockReturnService)
    {
        $this->stockReturnService = $stockReturnService;
    }

    /**
     * Display technician's inventory dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('view_inventory');

        $technicianId = auth()->id();

        // Get inventory summary
        $summary = $this->getInventorySummary($technicianId);

        // Get inventory by model with status breakdown
        $inventoryByModel = $this->getInventoryByModel($technicianId);

        // Get recent stock movements
        $recentMovements = $this->getRecentMovements($technicianId);

        // For AJAX requests (DataTable)
        if ($request->ajax()) {
            return $this->getDataTable($request, $technicianId);
        }

        return view('technician.inventory.index', compact(
            'summary',
            'inventoryByModel',
            'recentMovements'
        ));
    }

    /**
     * Show serial details
     */
    public function show($serialId)
    {
        $this->authorize('view_inventory');

        $serial = InventorySerial::with([
            'model.category',
            'grn.vendor',
            'purchaseOrder'
        ])->findOrFail($serialId);

        // Verify this serial belongs to the technician
        if ($serial->current_location_type !== 'technician' ||
            $serial->current_location_id !== auth()->id()) {
            abort(403, 'You can only view your own inventory items.');
        }

        // Get movement history for this serial
        $movements = DB::table('stock_ledger')
            ->where('serial_id', $serialId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('technician.inventory.show', compact('serial', 'movements'));
    }

    /**
     * Show return request form
     */
    public function returnRequest()
    {
        $this->authorize('create_stock_returns');

        $technicianId = auth()->id();

        // Get technician's current inventory
        $myInventory = $this->stockReturnService->getTechnicianInventorySummary($technicianId);

        // Get available depots
        $depots = Depot::where('status', 'active')
            ->orderBy('depot_name')
            ->get();

        return view('technician.inventory.return-request', compact('myInventory', 'depots'));
    }

    /**
     * AJAX: Get serial details by model
     */
    public function getSerialDetails(Request $request)
    {
        $this->authorize('view_inventory');

        $modelId = $request->input('model_id');
        $technicianId = auth()->id();

        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('model_id', $modelId)
            ->whereIn('current_status', ['issued', 'deployed', 'faulty'])
            ->get()
            ->map(function ($serial) {
                return [
                    'id' => $serial->id,
                    'serial_no' => $serial->serial_no,
                    'status' => $serial->current_status,
                    'model_name' => $serial->model->model_name ?? 'Unknown',
                ];
            });

        return response()->json(['serials' => $serials]);
    }

    /**
     * Show summary dashboard
     */
    public function summary()
    {
        $this->authorize('view_inventory');

        $technicianId = auth()->id();

        // Get summary statistics
        $summary = $this->getInventorySummary($technicianId);

        // Get inventory by model (for table)
        $inventoryByModel = $this->getInventoryByModel($technicianId);

        // Get inventory by category (for pie chart)
        $inventoryByCategory = $this->getInventoryByCategory($technicianId);

        // Get inventory by status (for doughnut chart)
        $inventoryByStatus = $this->getInventoryByStatus($technicianId);

        // Get issue/return statistics
        $issueReturnStats = $this->getIssueReturnStats($technicianId);

        return view('technician.inventory.summary', compact(
            'summary',
            'inventoryByModel',
            'inventoryByCategory',
            'inventoryByStatus',
            'issueReturnStats'
        ));
    }

    /**
     * Get DataTable data for inventory list
     */
    protected function getDataTable(Request $request, $technicianId)
    {
        $query = InventorySerial::with(['model.category', 'depot'])
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('current_status', ['issued', 'deployed', 'faulty']);

        // Search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_no', 'like', "%{$search}%")
                    ->orWhereHas('model', function ($mq) use ($search) {
                        $mq->where('model_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('current_status', $status);
        }

        // Ordering
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        // Get total count before pagination
        $totalRecords = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('current_status', ['issued', 'deployed', 'faulty'])
            ->count();

        $filteredRecords = $query->count();

        $columns = ['serial_no', 'model.model_name', 'current_status', 'created_at'];
        if (isset($columns[$orderColumn])) {
            if ($orderColumn == 1) {
                $query->join('terminal_models', 'inventory_serials.model_id', '=', 'terminal_models.id')
                    ->orderBy('terminal_models.model_name', $orderDir)
                    ->select('inventory_serials.*');
            } else {
                $query->orderBy($columns[$orderColumn], $orderDir);
            }
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $items = $query->skip($start)->take($length)->get();

        $data = $items->map(function ($serial) {
            return [
                'id' => $serial->id,
                'serial_no' => $serial->serial_no,
                'model_name' => $serial->model->model_name ?? 'N/A',
                'category_name' => $serial->model->category->category_name ?? 'N/A',
                'status' => $serial->current_status,
                'received_date' => $serial->received_date ? date('d M Y', strtotime($serial->received_date)) : 'N/A',
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Get inventory summary statistics
     */
    protected function getInventorySummary($technicianId)
    {
        $total = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('current_status', ['issued', 'deployed', 'faulty'])
            ->count();

        $issued = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('current_status', 'issued')
            ->count();

        $deployed = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('current_status', 'deployed')
            ->count();

        $faulty = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('current_status', 'faulty')
            ->count();

        return [
            'total_items' => $total,
            'issued_items' => $issued,
            'deployed_items' => $deployed,
            'faulty_items' => $faulty,
        ];
    }

    /**
     * Get inventory grouped by model
     */
    protected function getInventoryByModel($technicianId)
    {
        return DB::table('inventory_serials as is')
            ->join('terminal_models as tm', 'is.model_id', '=', 'tm.id')
            ->select(
                'tm.model_name',
                DB::raw('COUNT(*) as total_qty'),
                DB::raw('SUM(CASE WHEN is.current_status = "issued" THEN 1 ELSE 0 END) as issued_qty'),
                DB::raw('SUM(CASE WHEN is.current_status = "deployed" THEN 1 ELSE 0 END) as deployed_qty'),
                DB::raw('SUM(CASE WHEN is.current_status = "faulty" THEN 1 ELSE 0 END) as faulty_qty')
            )
            ->where('is.current_location_type', 'technician')
            ->where('is.current_location_id', $technicianId)
            ->whereIn('is.current_status', ['issued', 'deployed', 'faulty'])
            ->whereNull('is.deleted_at')
            ->groupBy('tm.id', 'tm.model_name')
            ->get();
    }

    /**
     * Get inventory grouped by status
     */
    protected function getInventoryByStatus($technicianId)
    {
        return InventorySerial::select('current_status', DB::raw('COUNT(*) as count'))
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('current_status', ['issued', 'deployed', 'faulty'])
            ->groupBy('current_status')
            ->get()
            ->pluck('count', 'current_status')
            ->toArray();
    }

    /**
     * Get inventory grouped by category
     */
    protected function getInventoryByCategory($technicianId)
    {
        return DB::table('inventory_serials as is')
            ->join('terminal_models as tm', 'is.model_id', '=', 'tm.id')
            ->join('terminal_categories as tc', 'tm.category_id', '=', 'tc.id')
            ->select(
                'tc.category_name',
                DB::raw('COUNT(*) as count')
            )
            ->where('is.current_location_type', 'technician')
            ->where('is.current_location_id', $technicianId)
            ->whereIn('is.current_status', ['issued', 'deployed', 'faulty'])
            ->whereNull('is.deleted_at')
            ->groupBy('tc.id', 'tc.category_name')
            ->get()
            ->pluck('count', 'category_name')
            ->toArray();
    }

    /**
     * Get recent stock movements
     */
    protected function getRecentMovements($technicianId, $limit = 10)
    {
        return DB::table('stock_ledger as sl')
            ->join('inventory_serials as is', 'sl.serial_id', '=', 'is.id')
            ->join('terminal_models as tm', 'is.model_id', '=', 'tm.id')
            ->select(
                'sl.transaction_date',
                'sl.transaction_no',
                'sl.transaction_type',
                'is.serial_no',
                'tm.model_name',
                'sl.from_location_type',
                'sl.from_location_id',
                'sl.to_location_type',
                'sl.to_location_id',
                'sl.quantity',
                'is.current_status as serial_status'
            )
            ->where(function ($query) use ($technicianId) {
                $query->where(function ($q) use ($technicianId) {
                    $q->where('sl.to_location_type', 'technician')
                        ->where('sl.to_location_id', $technicianId);
                })->orWhere(function ($q) use ($technicianId) {
                    $q->where('sl.from_location_type', 'technician')
                        ->where('sl.from_location_id', $technicianId);
                });
            })
            ->orderBy('sl.transaction_date', 'desc')
            ->orderBy('sl.id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get issue/return statistics
     * FIXED: Using correct column names from stock_issues table
     */
    protected function getIssueReturnStats($technicianId)
    {
        // Total issued to technician (all time)
        // Using: to_technician_id (not issued_to_id)
        $totalIssued = StockIssue::where('to_technician_id', $technicianId)
            ->where('issue_type', StockIssue::TYPE_ISSUE_TO_TECH)
            ->where('status', StockIssue::STATUS_POSTED)
            ->count();

        // Total returned by technician (all time)
        // Using: from_technician_id (not returned_by_id)
        $totalReturned = StockIssue::where('from_technician_id', $technicianId)
            ->where('issue_type', StockIssue::TYPE_RETURN_FROM_TECH)
            ->where('status', StockIssue::STATUS_POSTED)
            ->count();

        // Issued in last 30 days
        $issuedLast30Days = StockIssue::where('to_technician_id', $technicianId)
            ->where('issue_type', StockIssue::TYPE_ISSUE_TO_TECH)
            ->where('status', StockIssue::STATUS_POSTED)
            ->where('issue_date', '>=', now()->subDays(30))
            ->count();

        // Returned in last 30 days
        $returnedLast30Days = StockIssue::where('from_technician_id', $technicianId)
            ->where('issue_type', StockIssue::TYPE_RETURN_FROM_TECH)
            ->where('status', StockIssue::STATUS_POSTED)
            ->where('issue_date', '>=', now()->subDays(30))
            ->count();

        return [
            'total_issued' => $totalIssued,
            'total_returned' => $totalReturned,
            'issued_last_30_days' => $issuedLast30Days,
            'returned_last_30_days' => $returnedLast30Days,
        ];
    }
}
