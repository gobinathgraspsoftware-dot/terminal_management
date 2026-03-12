<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\Depot;
use App\Models\User;
use App\Services\GrnService;
use App\Services\GrnPdfService;
use App\Http\Requests\StoreGrnRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;

class GrnController extends Controller
{
    use AuthorizesRequests;

    protected $grnService;
    protected $pdfService;

    public function __construct(GrnService $grnService, GrnPdfService $pdfService)
    {
        $this->grnService = $grnService;
        $this->pdfService = $pdfService;
    }

    /**
     * Display a listing of team GRNs.
     * FIXED: Replaced team_id with supervisor_id scoping.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Grn::class);

        if ($request->ajax()) {
            $supervisorId = auth()->id();

            // Get team member IDs (supervisor + their technicians)
            $teamUserIds = User::where('supervisor_id', $supervisorId)
                ->pluck('id')
                ->push($supervisorId)
                ->toArray();

            $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy'])
                ->select('grns.*')
                ->whereIn('grns.created_by', $teamUserIds);

            // Apply filters
            if ($request->filled('status')) {
                $query->where('grns.status', $request->status);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('grns.grn_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('grns.grn_date', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addColumn('action', function ($grn) {
                    $actions = '<div class="btn-group btn-group-sm" role="group">';

                    if (auth()->user()->can('view', $grn)) {
                        $actions .= '<a href="' . route('supervisor.grns.show', $grn) . '" class="btn btn-info" title="View">
                            <i class="bi bi-eye"></i>
                        </a>';
                    }

                    if (auth()->user()->can('post', $grn)) {
                        $actions .= '<button type="button" class="btn btn-success post-grn-btn"
                            data-id="' . $grn->id . '"
                            data-grn-no="' . $grn->grn_no . '"
                            title="Post">
                            <i class="bi bi-check-circle"></i>
                        </button>';
                    }

                    if (auth()->user()->can('print', $grn)) {
                        $actions .= '<a href="' . route('supervisor.grns.pdf', $grn) . '" class="btn btn-secondary" title="Print PDF" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>';
                    }

                    if (auth()->user()->can('cancel', $grn) && $grn->status !== 'cancelled') {
                        $actions .= '<button type="button" class="btn btn-danger cancel-grn-btn"
                            data-id="' . $grn->id . '"
                            data-grn-no="' . $grn->grn_no . '"
                            title="Cancel">
                            <i class="bi bi-x-circle"></i>
                        </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->addColumn('vendor_name', fn($grn) => $grn->vendor->vendor_name ?? 'N/A')
                ->addColumn('po_no', fn($grn) => $grn->purchaseOrder->po_no ?? 'N/A')
                ->addColumn('depot_name', fn($grn) => $grn->receivingDepot->depot_name ?? 'N/A')
                ->addColumn('items_count', fn($grn) => $grn->lines_count ?? $grn->lines()->count())
                ->addColumn('status_badge', function ($grn) {
                    $badges = [
                        'draft' => 'secondary',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $class = $badges[$grn->status] ?? 'secondary';
                    $label = ucfirst($grn->status);
                    return '<span class="badge bg-' . $class . '">' . $label . '</span>';
                })
                ->addColumn('created_by_name', fn($grn) => $grn->createdBy->name ?? 'N/A')
                ->rawColumns(['action', 'status_badge'])
                ->make(true);
        }

        $depots = Depot::orderBy('depot_name')->get();

        return view('supervisor.grns.index', compact('depots'));
    }

    /**
     * Show form for creating a new GRN.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Grn::class);

        $purchaseOrders = $this->grnService->getOutstandingPurchaseOrders();
        $depots = Depot::orderBy('depot_name')->get();

        $selectedPo = null;
        if ($request->filled('po_id')) {
            $selectedPo = $this->grnService->getPurchaseOrderDetails($request->po_id);
        }

        return view('supervisor.grns.create', compact('purchaseOrders', 'depots', 'selectedPo'));
    }

    /**
     * Store a newly created GRN.
     */
    public function store(StoreGrnRequest $request)
    {
        $this->authorize('create', Grn::class);

        try {
            $grn = $this->grnService->createGrn($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'GRN created successfully.',
                'redirect' => route('supervisor.grns.show', $grn),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating GRN: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified GRN.
     */
    public function show(Grn $grn)
    {
        $this->authorize('view', $grn);

        $grn->load([
            'vendor',
            'purchaseOrder',
            'receivingDepot',
            'lines.model.category',
            'lines.poLine',
            'lines.serials',
            'createdBy',
        ]);

        return view('supervisor.grns.show', compact('grn'));
    }

    /**
     * Post a GRN (finalize and update inventory).
     */
    public function post(Grn $grn)
    {
        $this->authorize('post', $grn);

        try {
            $this->grnService->postGrn($grn);

            return response()->json([
                'success' => true,
                'message' => 'GRN posted successfully. Inventory has been updated.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error posting GRN: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a GRN.
     */
    public function cancel(Grn $grn)
    {
        $this->authorize('cancel', $grn);

        try {
            $this->grnService->cancelGrn($grn);

            return response()->json([
                'success' => true,
                'message' => 'GRN cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling GRN: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Generate PDF for a GRN.
     */
    public function pdf(Grn $grn)
    {
        $this->authorize('view', $grn);
        return $this->pdfService->stream($grn);
    }

    /**
     * Get PO details for GRN creation (AJAX).
     */
    public function getPurchaseOrderDetails(Request $request)
    {
        $po = $this->grnService->getPurchaseOrderDetails($request->po_id);

        return response()->json([
            'success' => true,
            'data' => $po,
        ]);
    }
}
