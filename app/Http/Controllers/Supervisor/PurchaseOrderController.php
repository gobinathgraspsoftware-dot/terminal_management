<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Models\Quotation;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseOrderPdfService;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    protected $poService;
    protected $pdfService;

    public function __construct(PurchaseOrderService $poService, PurchaseOrderPdfService $pdfService)
    {
        $this->poService = $poService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $statistics = $this->poService->getStatistics(Auth::user());

        return view('supervisor.purchase-orders.index', compact('vendors', 'statistics'));
    }

    protected function datatable(Request $request)
    {
        $query = $this->poService->getFilteredPurchaseOrders($request->all(), Auth::user());

        return DataTables::of($query)
            ->addColumn('vendor_name', fn($po) => $po->vendor->vendor_name ?? 'N/A')
            ->addColumn('status_badge', function ($po) {
                $badges = [
                    'draft' => 'secondary', 'pending_approval' => 'warning',
                    'approved' => 'info', 'sent' => 'primary',
                    'open' => 'success', 'partially_received' => 'info',
                    'fully_received' => 'success', 'closed' => 'dark',
                    'cancelled' => 'danger',
                ];
                $class = $badges[$po->status] ?? 'secondary';
                $label = ucwords(str_replace('_', ' ', $po->status));
                return '<span class="badge bg-' . $class . '">' . $label . '</span>';
            })
            ->addColumn('total', fn($po) => number_format($po->total_amount, 2))
            ->addColumn('outstanding', function ($po) {
                $outstanding = $po->lines->sum(function ($line) {
                    return $line->quantity_ordered - $line->quantity_received - $line->quantity_cancelled;
                });
                return number_format($outstanding);
            })
            ->addColumn('actions', function ($po) {
                $actions = '<div class="btn-group">';
                $actions .= '<a href="' . route('supervisor.purchase-orders.show', $po) . '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>';
                if (in_array($po->status, ['draft', 'pending_approval'])) {
                    $actions .= '<a href="' . route('supervisor.purchase-orders.edit', $po) . '" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>';
                }
                $actions .= '<a href="' . route('supervisor.purchase-orders.pdf', $po) . '" class="btn btn-sm btn-secondary" target="_blank"><i class="bi bi-file-pdf"></i></a>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create(Request $request)
    {
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();

        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = Quotation::with('lines.model')->findOrFail($request->quotation_id);
        }

        return view('supervisor.purchase-orders.create', compact('vendors', 'depots', 'models', 'quotation'));
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            $po = $this->poService->createPurchaseOrder($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order created successfully',
                'redirect' => route('supervisor.purchase-orders.show', $po)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['vendor', 'quotation', 'receivingDepot', 'lines.model', 'grns.lines', 'createdBy']);
        return view('supervisor.purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return redirect()->route('supervisor.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft or pending approval POs can be edited');
        }

        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();
        $purchaseOrder->load('lines.model');

        return view('supervisor.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'depots', 'models'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        try {
            $this->poService->updatePurchaseOrder($purchaseOrder, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Purchase Order updated successfully',
                'redirect' => route('supervisor.purchase-orders.show', $purchaseOrder)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        return $this->pdfService->stream($purchaseOrder);
    }
}
