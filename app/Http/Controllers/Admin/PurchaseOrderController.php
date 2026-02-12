<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\Quotation;
use App\Models\Depot;
use App\Models\TerminalModel;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseOrderPdfService;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Http\Requests\ApprovePurchaseOrderRequest;
use App\Http\Requests\SendPurchaseOrderRequest;
use App\Http\Requests\ClosePurchaseOrderRequest;
use App\Exports\PurchaseOrdersExport;
use App\Mail\PurchaseOrderEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
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

    /**
     * Display listing with status tabs
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        // FIXED: Changed is_active to status = 'active'
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $statistics = $this->poService->getStatistics();

        return view('admin.purchase-orders.index', compact('vendors', 'statistics'));
    }

    /**
     * DataTables AJAX endpoint
     */
    protected function datatable(Request $request)
    {
        $query = $this->poService->getFilteredPurchaseOrders($request->all());

        return DataTables::of($query)
            ->addColumn('vendor_name', fn($po) => $po->vendor->vendor_name ?? 'N/A')
            ->addColumn('status_badge', function ($po) {
                $badges = [
                    'draft' => 'secondary',
                    'pending_approval' => 'warning',
                    'approved' => 'info',
                    'sent' => 'primary',
                    'open' => 'success',
                    'partially_received' => 'info',
                    'fully_received' => 'success',
                    'closed' => 'dark',
                    'cancelled' => 'danger',
                ];
                $class = $badges[$po->status] ?? 'secondary';
                $label = ucwords(str_replace('_', ' ', $po->status));
                return '<span class="badge bg-' . $class . '">' . $label . '</span>';
            })
            ->addColumn('total', fn($po) => number_format($po->total_amount, 2))
            ->addColumn('outstanding', function($po) {
                $total = 0;
                foreach ($po->lines as $line) {
                    $total += $line->quantity_outstanding;
                }
                return $total;
            })
            ->addColumn('actions', function ($po) {
                $actions = '<div class="btn-group" role="group">';
                $actions .= '<a href="' . route('admin.purchase-orders.show', $po) . '" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>';

                if (in_array($po->status, ['draft', 'pending_approval'])) {
                    $actions .= '<a href="' . route('admin.purchase-orders.edit', $po) . '" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
                }

                $actions .= '<a href="' . route('admin.purchase-orders.pdf', $po) . '" class="btn btn-sm btn-secondary" title="PDF" target="_blank"><i class="bi bi-file-pdf"></i></a>';
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show create form
     */
    public function create(Request $request)
    {
        // FIXED: Changed is_active to status = 'active'
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();

        $quotation = null;
        if ($request->filled('quotation_id')) {
            $quotation = Quotation::with('lines.model')->findOrFail($request->quotation_id);
        }

        return view('admin.purchase-orders.create', compact('vendors', 'depots', 'models', 'quotation'));
    }

    /**
     * Store new PO
     */
    public function store(StorePurchaseOrderRequest $request)
    {
        try {
            $po = $this->poService->createPurchaseOrder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order created successfully',
                'redirect' => route('admin.purchase-orders.show', $po)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating Purchase Order: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Show PO details
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'vendor',
            'quotation',
            'receivingDepot',
            'lines.model',
            'grns.lines',
            'createdBy',
            'approvedBy',
            'closedBy'
        ]);

        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    /**
     * Show edit form
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['draft', 'pending_approval'])) {
            return redirect()->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'Only draft or pending approval purchase orders can be edited');
        }

        // FIXED: Changed is_active to status = 'active'
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $depots = Depot::orderBy('depot_name')->get();
        $models = TerminalModel::orderBy('model_name')->get();

        $purchaseOrder->load('lines.model');

        return view('admin.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'depots', 'models'));
    }

    /**
     * Update PO
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $this->poService->updatePurchaseOrder($purchaseOrder, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order updated successfully',
                'redirect' => route('admin.purchase-orders.show', $purchaseOrder)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating Purchase Order: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(PurchaseOrder $purchaseOrder)
    {
        try {
            $this->poService->submitForApproval($purchaseOrder);

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order submitted for approval'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve PO
     */
    public function approve(ApprovePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $this->poService->approvePurchaseOrder($purchaseOrder, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order approved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject PO
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            $this->poService->rejectPurchaseOrder($purchaseOrder, $request->rejection_reason);

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order rejected'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Send to vendor (with email)
     */
    public function sendToVendor(SendPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $this->poService->sendToVendor($purchaseOrder);

            // Generate PDF
            $pdfContent = $this->pdfService->output($purchaseOrder);

            // Send email
            if ($request->filled('email_to')) {
                Mail::to($request->email_to)
                    ->cc($request->email_cc ?? [])
                    ->send(new PurchaseOrderEmail($purchaseOrder, $pdfContent, $request->email_message));
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order sent to vendor successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Close PO
     */
    public function close(ClosePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $this->poService->closePurchaseOrder($purchaseOrder, $request->closure_reason);

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order closed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel PO
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        try {
            $this->poService->cancelPurchaseOrder($purchaseOrder, $request->cancellation_reason);

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Generate PDF
     */
    public function pdf(PurchaseOrder $purchaseOrder)
    {
        return $this->pdfService->stream($purchaseOrder);
    }

    /**
     * Download PDF
     */
    public function downloadPdf(PurchaseOrder $purchaseOrder)
    {
        return $this->pdfService->download($purchaseOrder);
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->all();
        return Excel::download(new PurchaseOrdersExport($filters), 'purchase-orders-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Delete PO
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft purchase orders can be deleted'
            ], 422);
        }

        try {
            $purchaseOrder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting Purchase Order: ' . $e->getMessage()
            ], 422);
        }
    }
}
