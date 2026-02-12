<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use App\Services\PurchaseOrderPdfService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

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

        // FIXED: Changed is_active to status = 'active'
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $statistics = $this->poService->getStatistics(Auth::user());

        return view('technician.purchase-orders.index', compact('vendors', 'statistics'));
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
            ->addColumn('actions', function ($po) {
                return '<div class="btn-group">
                    <a href="' . route('technician.purchase-orders.show', $po) . '" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                    <a href="' . route('technician.purchase-orders.pdf', $po) . '" class="btn btn-sm btn-secondary" target="_blank"><i class="bi bi-file-pdf"></i></a>
                </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['vendor', 'quotation', 'receivingDepot', 'lines.model', 'grns.lines', 'createdBy']);
        return view('technician.purchase-orders.show', compact('purchaseOrder'));
    }

    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        return $this->pdfService->stream($purchaseOrder);
    }
}
