<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\StockLedger;
use App\Models\TerminalModel;
use App\Services\Inventory\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StockLedgerController extends Controller
{
    use AuthorizesRequests;

    protected $ledgerService;

    public function __construct(StockLedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display technician's own stock ledger movements
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', StockLedger::class);

        if ($request->ajax()) {
            return $this->getDataTableData($request);
        }

        // Get models for filter
        $models = TerminalModel::select('id', 'model_name', 'model_code')
            ->orderBy('model_name')
            ->get();

        return view('technician.stock-ledger.index', compact('models'));
    }

    /**
     * Show specific movement details (only if technician is involved)
     */
    public function show(StockLedger $stockLedger)
    {
        $this->authorize('view', $stockLedger);

        // Ensure this movement involves the technician
        $technicianId = Auth::id();

        if (!$this->isTechnicianMovement($stockLedger, $technicianId)) {
            abort(403, 'Unauthorized access to this stock movement.');
        }

        return view('technician.stock-ledger.show', compact('stockLedger'));
    }

    /**
     * Get DataTable data (only technician's own movements)
     */
    protected function getDataTableData(Request $request)
    {
        $technicianId = Auth::id();

        $query = StockLedger::with(['model.category', 'createdBy'])
            ->where(function($q) use ($technicianId) {
                // Movements where technician is the FROM location
                $q->where(function($subQ) use ($technicianId) {
                    $subQ->where('from_location_type', 'technician')
                         ->where('from_location_id', $technicianId);
                })
                // OR movements where technician is the TO location
                ->orWhere(function($subQ) use ($technicianId) {
                    $subQ->where('to_location_type', 'technician')
                         ->where('to_location_id', $technicianId);
                });
            });

        // Apply filters
        if ($request->filled('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        if ($request->filled('serial_no')) {
            $query->where('serial_no', 'like', '%' . $request->serial_no . '%');
        }

        if ($request->filled('show_reversed') && $request->show_reversed === 'exclude') {
            $query->where('is_reversed', false);
        }

        // Get total count
        $totalRecords = $query->count();

        // Apply sorting
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $columns = [
            'transaction_date',
            'transaction_no',
            'transaction_type',
            'model_id',
            'quantity',
            'from_location_type',
            'to_location_type',
            'created_by',
        ];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('transaction_date', 'desc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $filteredRecords = $query->count();

        $data = $query->skip($start)->take($length)->get();

        // Format data for DataTable
        $formattedData = $data->map(function ($ledger) use ($technicianId) {
            return [
                'id' => $ledger->id,
                'transaction_date' => $ledger->transaction_date->format('d M Y'),
                'transaction_no' => $ledger->transaction_no,
                'type_badge' => $ledger->type_badge,
                'model_name' => $ledger->model->model_name ?? '-',
                'model_code' => $ledger->model->model_code ?? '-',
                'quantity' => number_format($ledger->quantity, 2),
                'serial_no' => $ledger->serial_no ?? '-',
                'from_location' => $this->formatLocation($ledger->from_location_type, $ledger->from_location_id, $technicianId),
                'to_location' => $this->formatLocation($ledger->to_location_type, $ledger->to_location_id, $technicianId),
                'created_by' => $ledger->createdBy->name ?? '-',
                'is_reversed' => $ledger->is_reversed,
                'reversal_badge' => $ledger->is_reversed
                    ? '<span class="badge bg-danger">Reversed</span>'
                    : ($ledger->reversal_of_id
                        ? '<span class="badge bg-warning text-dark">Reversal</span>'
                        : ''),
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $formattedData,
        ]);
    }

    /**
     * Format location for display
     */
    protected function formatLocation($type, $id, $technicianId)
    {
        if (!$type || !$id) {
            return '-';
        }

        if ($type === 'technician') {
            if ($id == $technicianId) {
                return '<span class="badge bg-success">My Stock</span>';
            }
            $tech = \App\Models\User::find($id);
            return $tech ? $tech->name . ' (Tech)' : 'Technician #' . $id;
        }

        if ($type === 'depot') {
            $depot = \App\Models\Depot::find($id);
            return $depot ? $depot->depot_name : 'Depot #' . $id;
        }

        if ($type === 'site') {
            $site = \App\Models\Site::find($id);
            return $site ? $site->site_name : 'Site #' . $id;
        }

        if ($type === 'vendor') {
            return 'Vendor #' . $id;
        }

        return ucfirst($type) . ' #' . $id;
    }

    /**
     * Check if movement involves this technician
     */
    protected function isTechnicianMovement(StockLedger $ledger, $technicianId)
    {
        return ($ledger->from_location_type === 'technician' && $ledger->from_location_id == $technicianId)
            || ($ledger->to_location_type === 'technician' && $ledger->to_location_id == $technicianId);
    }
}
