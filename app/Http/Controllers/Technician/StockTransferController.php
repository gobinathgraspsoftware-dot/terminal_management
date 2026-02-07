<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockTransferController extends Controller
{
    /**
     * Display a listing of stock transfers (view-only)
     * Technicians can only view transfers related to them
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        $statuses = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'in_transit' => 'In Transit',
            'received' => 'Received',
            'cancelled' => 'Cancelled',
        ];

        return view('technician.stock-transfers.index', compact('statuses'));
    }

    /**
     * DataTables AJAX endpoint (technician-scoped)
     */
    public function datatable(Request $request)
    {
        $user = auth()->user();
        
        // Technicians can only see transfers related to their current location
        $query = StockTransfer::with(['fromDepot', 'toDepot', 'lines'])
            ->select('stock_transfers.*')
            ->where(function($q) use ($user) {
                // Show transfers where technician might have received stock
                // Or transfers to/from depots they work with
                // Adjust based on your business logic
                $q->where('created_by', $user->id);
            });

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('from_depot_name', function ($transfer) {
                return $transfer->fromDepot->depot_name ?? 'N/A';
            })
            ->addColumn('to_depot_name', function ($transfer) {
                return $transfer->toDepot->depot_name ?? 'N/A';
            })
            ->addColumn('total_items_display', function ($transfer) {
                return number_format($transfer->total_items);
            })
            ->addColumn('status_badge', function ($transfer) {
                return $this->getStatusBadge($transfer->status);
            })
            ->addColumn('actions', function ($transfer) {
                return '<a href="' . route('technician.stock-transfers.show', $transfer) . '" class="btn btn-sm btn-info">
                            <i class="bi bi-eye"></i> View
                        </a>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Display the specified stock transfer
     */
    public function show(StockTransfer $stockTransfer)
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load([
            'fromDepot',
            'toDepot',
            'lines.model.category',
            'lines.serial'
        ]);

        return view('technician.stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'pending_approval' => '<span class="badge bg-warning text-dark">Pending Approval</span>',
            'approved' => '<span class="badge bg-info">Approved</span>',
            'in_transit' => '<span class="badge bg-primary">In Transit</span>',
            'received' => '<span class="badge bg-success">Received</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
