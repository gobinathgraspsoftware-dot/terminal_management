<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\Depot;
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
     * Display a listing of team GRNs
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Grn::class);

        if ($request->ajax()) {
            $teamId = auth()->user()->team_id;

            $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy'])
                ->select('grns.*')
                ->whereHas('createdBy', function ($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                });

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

                    // Print PDF button
                    if (auth()->user()->can('print', $grn)) {
                        $actions .= '<a href="' . route('supervisor.grns.pdf', $grn) . '" class="btn btn-secondary" title="Print PDF" target="_blank">
                            <i class="bi bi-printer"></i>
                        </a>';
                    }

                    // Cancel button (only for draft)
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
                ->editColumn('grn_date', function ($grn) {
                    return $grn->grn_date->format('d M Y');
                })
                ->editColumn('status', function ($grn) {
                    $badges = [
                        'draft'     => 'secondary',
                        'posted'    => 'success',
                        'cancelled' => 'danger',
                    ];
                    $badge = $badges[$grn->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($grn->status) . '</span>';
                })
                ->addColumn('vendor_name', function ($grn) {
                    return $grn->vendor->vendor_name ?? $grn->vendor->company_name ?? '-';
                })
                ->addColumn('depot_name', function ($grn) {
                    return $grn->receivingDepot->depot_name ?? '-';
                })
                ->addColumn('po_no', function ($grn) {
                    return $grn->purchaseOrder->po_no ?? '-';
                })
                ->addColumn('created_by_name', function ($grn) {
                    return $grn->createdBy->name ?? '-';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('supervisor.grns.index');
    }

    /**
     * Show the form for creating a new GRN
     */
    public function create()
    {
        $this->authorize('create', Grn::class);

        $purchaseOrders = $this->grnService->getOutstandingPurchaseOrders();
        $depots = Depot::where('status', 'active')->orderBy('depot_name')->get();

        return view('supervisor.grns.create', compact('purchaseOrders', 'depots'));
    }

    /**
     * Store a newly created GRN
     */
    public function store(StoreGrnRequest $request)
    {
        try {
            $grn = $this->grnService->createGrn($request->validated());

            return response()->json([
                'success'  => true,
                'message'  => 'GRN created successfully.',
                'redirect' => route('supervisor.grns.show', $grn),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating GRN: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified GRN
     */
    public function show(Grn $grn)
    {
        $this->authorize('view', $grn);

        $grn->load([
            'vendor',
            'receivingDepot',
            'purchaseOrder.lines',
            'lines.model.category',
            'lines.serials',
            'createdBy',
            'postedBy',
        ]);

        return view('supervisor.grns.show', compact('grn'));
    }

    /**
     * Get Purchase Order details (AJAX)
     */
    public function getPurchaseOrderDetails($poId)
    {
        try {
            $po = $this->grnService->getPurchaseOrderDetails($poId);

            $lines = $po->lines->map(function ($line) {
                return [
                    'id'                  => $line->id,
                    'line_no'             => $line->line_no,
                    'model_id'            => $line->model_id,
                    'model_name'          => $line->model->model_name ?? '',
                    'description'         => $line->description,
                    'quantity_ordered'    => (float) $line->quantity_ordered,
                    'quantity_received'   => (float) $line->quantity_received,
                    'quantity_cancelled'  => (float) $line->quantity_cancelled,
                    'quantity_outstanding' => (float) $line->quantity_outstanding,
                    'unit'                => $line->unit,
                    'unit_price'          => (float) $line->unit_price,
                    'is_serial_tracked'   => $line->model->is_serial_tracked ?? false,
                ];
            });

            return response()->json([
                'success'        => true,
                'purchase_order' => [
                    'id'                 => $po->id,
                    'po_no'              => $po->po_no,
                    'po_date'            => $po->po_date->format('Y-m-d'),
                    'vendor_id'          => $po->vendor_id,
                    'vendor_name'        => $po->vendor->vendor_name ?? $po->vendor->company_name ?? '',
                    'receiving_depot_id' => $po->receiving_depot_id,
                    'depot_name'         => $po->receivingDepot->depot_name ?? '',
                ],
                'lines' => $lines,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading purchase order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Post GRN (AJAX) — triggers inventory update + email notification
     * Same GrnService handles email for both admin and supervisor
     */
    public function post(Grn $grn)
    {
        $this->authorize('post', $grn);

        try {
            $grn = $this->grnService->postGrn($grn);

            return response()->json([
                'success' => true,
                'message' => "GRN {$grn->grn_no} posted successfully. Inventory and stock balances updated.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error posting GRN: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate serial uniqueness (AJAX)
     */
    public function validateSerial(Request $request)
    {
        $serialNo         = $request->input('serial_no');
        $excludeGrnLineId = $request->input('exclude_grn_line_id');

        $result = $this->grnService->validateSerialUniqueness($serialNo, $excludeGrnLineId);

        return response()->json($result);
    }

    /**
     * Stream GRN PDF (view in browser)
     */
    public function pdf(Grn $grn)
    {
        $this->authorize('print', $grn);

        try {
            return $this->pdfService->stream($grn);
        } catch (\Exception $e) {
            return redirect()->route('supervisor.grns.show', $grn)
                ->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Download GRN PDF
     */
    public function downloadPdf(Grn $grn)
    {
        $this->authorize('print', $grn);

        try {
            return $this->pdfService->download($grn);
        } catch (\Exception $e) {
            return redirect()->route('supervisor.grns.show', $grn)
                ->with('error', 'Error downloading PDF: ' . $e->getMessage());
        }
    }
}
