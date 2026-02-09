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
     * Get serial details for return form (AJAX)
     */
    public function getSerialDetails(Request $request)
    {
        $modelId = $request->input('model_id');
        $technicianId = auth()->id();

        $serials = InventorySerial::with('model')
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('model_id', $modelId)
            ->whereIn('status', ['issued', 'deployed', 'faulty'])
            ->orderBy('serial_no')
            ->get();

        return response()->json([
            'success' => true,
            'serials' => $serials->map(function ($serial) {
                return [
                    'id' => $serial->id,
                    'serial_no' => $serial->serial_no,
                    'status' => $serial->status,
                    'model_name' => $serial->model->model_name ?? 'Unknown'
                ];
            })
        ]);
    }

    /**
     * Stock summary view
     */
    public function summary()
    {
        $this->authorize('view_inventory');

        $technicianId = auth()->id();

        // Get comprehensive summary
        $summary = $this->getInventorySummary($technicianId);
        $inventoryByModel = $this->getInventoryByModel($technicianId);
        $inventoryByStatus = $this->getInventoryByStatus($technicianId);
        $inventoryByCategory = $this->getInventoryByCategory($technicianId);

        // Get issue/return statistics
        $issueStats = $this->getIssueReturnStats($technicianId);

        return view('technician.inventory.summary', compact(
            'summary',
            'inventoryByModel',
            'inventoryByStatus',
            'inventoryByCategory',
            'issueStats'
        ));
    }

    /**
     * Get DataTable data for inventory list
     */
    protected function getDataTable(Request $request, $technicianId)
    {
        $query = InventorySerial::with(['model.category'])
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('status', ['issued', 'deployed', 'faulty']);

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
            $query->where('status', $status);
        }

        // Filter by model
        if ($modelId = $request->input('model_id')) {
            $query->where('model_id', $modelId);
        }

        // Total records
        $totalRecords = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('status', ['issued', 'deployed', 'faulty'])
            ->count();

        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc');

        $columns = ['serial_no', 'model.model_name', 'status', 'created_at'];
        if (isset($columns[$orderColumn])) {
            if ($orderColumn == 1) { // model_name
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
        $serials = $query->skip($start)->take($length)->get();

        $data = $serials->map(function ($serial) {
            return [
                'id' => $serial->id,
                'serial_no' => $serial->serial_no,
                'model_name' => $serial->model->model_name ?? 'N/A',
                'category_name' => $serial->model->category->category_name ?? 'N/A',
                'status' => $serial->status,
                'received_date' => $serial->created_at ? $serial->created_at->format('Y-m-d') : 'N/A',
                'action' => view('technician.inventory.partials.action-buttons', compact('serial'))->render()
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
        $totalItems = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('status', ['issued', 'deployed', 'faulty'])
            ->count();

        $issuedItems = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('status', 'issued')
            ->count();

        $deployedItems = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('status', 'deployed')
            ->count();

        $faultyItems = InventorySerial::where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->where('status', 'faulty')
            ->count();

        return [
            'total_items' => $totalItems,
            'issued_items' => $issuedItems,
            'deployed_items' => $deployedItems,
            'faulty_items' => $faultyItems
        ];
    }

    /**
     * Get inventory grouped by model
     */
    protected function getInventoryByModel($technicianId)
    {
        return DB::table('inventory_serials as is')
            ->join('terminal_models as tm', 'is.model_id', '=', 'tm.id')
            ->join('terminal_categories as tc', 'tm.category_id', '=', 'tc.id')
            ->select(
                'tm.id as model_id',
                'tm.model_name',
                'tc.category_name',
                DB::raw('COUNT(*) as total_quantity'),
                DB::raw('SUM(CASE WHEN is.status = "issued" THEN 1 ELSE 0 END) as issued_qty'),
                DB::raw('SUM(CASE WHEN is.status = "deployed" THEN 1 ELSE 0 END) as deployed_qty'),
                DB::raw('SUM(CASE WHEN is.status = "faulty" THEN 1 ELSE 0 END) as faulty_qty')
            )
            ->where('is.current_location_type', 'technician')
            ->where('is.current_location_id', $technicianId)
            ->whereIn('is.status', ['issued', 'deployed', 'faulty'])
            ->groupBy('tm.id', 'tm.model_name', 'tc.category_name')
            ->orderBy('tc.category_name')
            ->orderBy('tm.model_name')
            ->get();
    }

    /**
     * Get inventory grouped by status
     */
    protected function getInventoryByStatus($technicianId)
    {
        return InventorySerial::select('status', DB::raw('COUNT(*) as count'))
            ->where('current_location_type', 'technician')
            ->where('current_location_id', $technicianId)
            ->whereIn('status', ['issued', 'deployed', 'faulty'])
            ->groupBy('status')
            ->get();
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
                'tc.id as category_id',
                'tc.category_name',
                DB::raw('COUNT(*) as total_quantity')
            )
            ->where('is.current_location_type', 'technician')
            ->where('is.current_location_id', $technicianId)
            ->whereIn('is.status', ['issued', 'deployed', 'faulty'])
            ->groupBy('tc.id', 'tc.category_name')
            ->orderBy('tc.category_name')
            ->get();
    }

    /**
     * Get recent stock movements
     */
    protected function getRecentMovements($technicianId, $limit = 10)
    {
        return DB::table('stock_ledger as sl')
            ->join('terminal_models as tm', 'sl.model_id', '=', 'tm.id')
            ->leftJoin('inventory_serials as is', 'sl.serial_id', '=', 'is.id')
            ->select(
                'sl.id',
                'sl.transaction_date',
                'sl.transaction_no',
                'sl.transaction_type',
                'sl.serial_no',
                'sl.quantity',
                'sl.remarks',
                'tm.model_name',
                'is.status as serial_status'
            )
            ->where(function ($q) use ($technicianId) {
                $q->where(function ($q2) use ($technicianId) {
                    $q2->where('sl.to_location_type', 'technician')
                       ->where('sl.to_location_id', $technicianId);
                })
                ->orWhere(function ($q2) use ($technicianId) {
                    $q2->where('sl.from_location_type', 'technician')
                       ->where('sl.from_location_id', $technicianId);
                });
            })
            ->orderBy('sl.transaction_date', 'desc')
            ->orderBy('sl.id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get issue and return statistics
     */
    protected function getIssueReturnStats($technicianId)
    {
        $last30Days = now()->subDays(30);

        return [
            'total_issued' => StockIssue::where('issue_type', 'issue_to_tech')
                ->where('to_technician_id', $technicianId)
                ->where('status', 'posted')
                ->count(),
            'total_returned' => StockIssue::where('issue_type', 'return_from_tech')
                ->where('from_technician_id', $technicianId)
                ->where('status', 'posted')
                ->count(),
            'issued_last_30_days' => StockIssue::where('issue_type', 'issue_to_tech')
                ->where('to_technician_id', $technicianId)
                ->where('status', 'posted')
                ->where('issue_date', '>=', $last30Days)
                ->count(),
            'returned_last_30_days' => StockIssue::where('issue_type', 'return_from_tech')
                ->where('from_technician_id', $technicianId)
                ->where('status', 'posted')
                ->where('issue_date', '>=', $last30Days)
                ->count(),
        ];
    }
}
