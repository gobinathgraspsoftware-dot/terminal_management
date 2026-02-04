<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use App\Models\TerminalModel;
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
     * Display stock ledger listing (scoped to supervisor's team)
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockLedger::class);

        if ($request->ajax()) {
            return $this->getDatatableData($request);
        }

        $models = TerminalModel::orderBy('model_name')->get();

        // Get supervisor's team technicians
        $technicians = User::where('supervisor_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('supervisor.stock-ledger.index', compact('models', 'technicians'));
    }

    /**
     * Show specific ledger entry
     */
    public function show(StockLedger $stockLedger)
    {
        Gate::authorize('view', $stockLedger);

        $stockLedger->load(['model', 'serial', 'createdBy', 'reversedByEntry', 'reversalOfEntry', 'reversedByUser']);

        return view('supervisor.stock-ledger.show', compact('stockLedger'));
    }

    /**
     * Reverse a stock movement (if authorized)
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
     * Get DataTable data (scoped to supervisor's team)
     */
    protected function getDatatableData(Request $request)
    {
        // Get supervisor's team technician IDs
        $teamTechIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();

        $query = StockLedger::with(['model', 'serial', 'createdBy'])
            ->where(function ($q) use ($teamTechIds) {
                // Show movements involving supervisor's technicians
                $q->where(function ($sub) use ($teamTechIds) {
                    $sub->where('from_location_type', 'technician')
                        ->whereIn('from_location_id', $teamTechIds);
                })
                ->orWhere(function ($sub) use ($teamTechIds) {
                    $sub->where('to_location_type', 'technician')
                        ->whereIn('to_location_id', $teamTechIds);
                })
                // Or created by supervisor or team members
                ->orWhereIn('created_by', array_merge([$auth()->id()], $teamTechIds));
            })
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        // Apply filters
        $this->applyFilters($query, $request);

        return DataTableHelper::make($query, $request, function ($ledger) {
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
                'can_reverse' => $ledger->is_reversible && Gate::allows('reverse', $ledger),
            ];
        });
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

        if ($request->filled('technician_id')) {
            $query->where(function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('from_location_type', 'technician')
                        ->where('from_location_id', $request->technician_id);
                })
                ->orWhere(function ($sub) use ($request) {
                    $sub->where('to_location_type', 'technician')
                        ->where('to_location_id', $request->technician_id);
                });
            });
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

        if ($request->filled('show_reversed')) {
            if ($request->show_reversed == '0') {
                $query->notReversed();
            }
        } else {
            // Default: exclude reversed entries
            $query->activeMovements();
        }
    }
}
