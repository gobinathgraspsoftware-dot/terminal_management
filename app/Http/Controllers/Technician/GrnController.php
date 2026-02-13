<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;

class GrnController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Display a listing of own GRNs (view-only)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Grn::class);

        if ($request->ajax()) {
            $user = auth()->user();
            
            $query = Grn::with(['vendor', 'receivingDepot', 'purchaseOrder', 'createdBy'])
                ->where('created_by', $user->id)
                ->select('grns.*');

            return DataTables::of($query)
                ->addColumn('action', function ($grn) {
                    return '<a href="' . route('technician.grns.show', $grn) . '" class="btn btn-sm btn-info" title="View">
                        <i class="bi bi-eye"></i> View
                    </a>';
                })
                ->editColumn('grn_date', function ($grn) {
                    return $grn->grn_date->format('d M Y');
                })
                ->editColumn('status', function ($grn) {
                    $badges = [
                        'draft' => 'secondary',
                        'posted' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $badge = $badges[$grn->status] ?? 'secondary';
                    return '<span class="badge bg-' . $badge . '">' . ucfirst($grn->status) . '</span>';
                })
                ->addColumn('vendor_name', function ($grn) {
                    return $grn->vendor->name ?? '-';
                })
                ->addColumn('depot_name', function ($grn) {
                    return $grn->receivingDepot->depot_name ?? '-';
                })
                ->addColumn('po_no', function ($grn) {
                    return $grn->purchaseOrder->po_no ?? '-';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('technician.grns.index');
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
            'postedBy'
        ]);

        return view('technician.grns.show', compact('grn'));
    }
}
