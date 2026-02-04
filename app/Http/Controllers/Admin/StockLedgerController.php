<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use App\Models\TerminalModel;
use App\Models\Depot;
use App\Models\User;
use App\Services\Inventory\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockLedgerController extends Controller
{
    protected StockLedgerService $ledgerService;

    public function __construct(StockLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display stock ledger listing
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockLedger::class);

        if ($request->ajax()) {
            return $this->getDatatableData($request);
        }

        $models = TerminalModel::orderBy('model_name')->get();
        $depots = Depot::orderBy('depot_name')->get();
        $technicians = User::role('technician')->orderBy('name')->get();

        return view('admin.stock-ledger.index', compact('models', 'depots', 'technicians'));
    }

    /**
     * Show specific ledger entry
     */
    public function show(StockLedger $stockLedger)
    {
        Gate::authorize('view', $stockLedger);

        $stockLedger->load(['model', 'serial', 'createdBy', 'reversedByEntry', 'reversalOfEntry', 'reversedByUser']);

        return view('admin.stock-ledger.show', compact('stockLedger'));
    }

    /**
     * Reverse a stock movement
     */
    public function reverse(Request $request, StockLedger $stockLedger)
    {
        Gate::authorize('reverse', $stockLedger);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $reversalLedger = $this->ledgerService->reverseMovement(
                $stockLedger->id,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock movement reversed successfully',
                'data' => [
                    'original_id' => $stockLedger->id,
                    'reversal_id' => $reversalLedger->id,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reverse movement: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export ledger to Excel
     */
    public function export(Request $request)
    {
        Gate::authorize('export', StockLedger::class);

        $filters = $this->buildFilters($request);

        $ledgers = $this->ledgerService->getMovementHistory($filters)->get();

        // Export implementation here
        // return Excel::download(new StockLedgerExport($ledgers), 'stock-ledger-' . date('Y-m-d') . '.xlsx');

        return response()->json([
            'success' => false,
            'message' => 'Export feature not yet implemented'
        ]);
    }

    /**
     * Get DataTable data
     */
    protected function getDatatableData(Request $request)
    {
        $query = StockLedger::with(['model', 'serial', 'createdBy']);

        // Apply filters
        $this->applyFilters($query, $request);

        // Get total count before pagination
        $totalRecords = StockLedger::count();
        $filteredRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $columns = [
            'transaction_date',
            'transaction_no',
            'transaction_type',
            'model_id',
            'serial_no',
            'quantity',
            'from_location_type',
            'to_location_type',
            'created_by',
        ];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);

        $ledgers = $query->skip($start)->take($length)->get();

        // Format data
        $data = $ledgers->map(function ($ledger) {
            return [
                'id' => $ledger->id,
                'transaction_date' => $ledger->transaction_date->format('Y-m-d'),
                'transaction_no' => $ledger->transaction_no,
                'transaction_type' => $ledger->transaction_type,
                'type_badge' => $ledger->type_badge,
                'model_name' => $ledger->model->model_name ?? '-',
                'serial_no' => $ledger->serial_no ?? '-',
                'quantity' => number_format($ledger->quantity, 2),
                'from_location' => $ledger->from_location_name,
                'to_location' => $ledger->to_location_name,
                'reference' => $ledger->reference_label,
                'reference_url' => $ledger->reference_url,
                'is_reversed' => $ledger->is_reversed,
                'reversal_status' => $ledger->reversal_status_badge,
                'created_by' => $ledger->createdBy->name ?? '-',
                'remarks' => $ledger->remarks,
                'can_reverse' => $ledger->is_reversible,
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('location_type') && $request->filled('location_id')) {
            $query->byLocation($request->location_type, $request->location_id);
        }

        if ($request->filled('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        if ($request->filled('serial_no')) {
            $query->where('serial_no', 'like', '%' . $request->serial_no . '%');
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('show_reversed')) {
            if ($request->show_reversed == '0') {
                $query->notReversed();
            }
        } else {
            // Default: exclude reversed entries
            $query->activeMovements();
        }
    }

    /**
     * Build filters for export
     */
    protected function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('model_id')) {
            $filters['model_id'] = $request->model_id;
        }

        if ($request->filled('transaction_type')) {
            $filters['transaction_type'] = $request->transaction_type;
        }

        if ($request->filled('location_type') && $request->filled('location_id')) {
            $filters['location_type'] = $request->location_type;
            $filters['location_id'] = $request->location_id;
        }

        if ($request->filled('from_date')) {
            $filters['from_date'] = $request->from_date;
        }

        if ($request->filled('to_date')) {
            $filters['to_date'] = $request->to_date;
        }

        return $filters;
    }
}
