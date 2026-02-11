<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class QuotationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view_quotations');
    }

    /**
     * Display listing of own quotations
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        $statuses = Quotation::getStatusList();
        $types = Quotation::getTypeList();

        return view('technician.quotations.index', compact('statuses', 'types'));
    }

    /**
     * DataTables AJAX endpoint - Own records only
     */
    protected function datatable(Request $request)
    {
        $query = Quotation::with(['client', 'vendor'])
            ->where('created_by', Auth::id())
            ->select('quotations.*');

        // Filters
        if ($request->filled('type')) {
            $query->where('quotation_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('quotation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('quotation_date', '<=', $request->date_to);
        }

        return DataTables::of($query)
            ->addColumn('party_name', fn($q) => $q->party_name)
            ->addColumn('type_badge', function ($q) {
                $color = $q->quotation_type === 'customer' ? 'primary' : 'info';
                return '<span class="badge bg-' . $color . '">' . $q->getTypeLabel() . '</span>';
            })
            ->addColumn('status_badge', function ($q) {
                return '<span class="badge ' . $q->getStatusBadgeClass() . '">' . $q->getStatusLabel() . '</span>';
            })
            ->addColumn('amount', fn($q) => number_format($q->total_amount, 2))
            ->addColumn('actions', function ($quotation) {
                return '<a href="' . route('technician.quotations.show', $quotation) . '" 
                    class="btn btn-sm btn-info">
                    <i class="bi bi-eye"></i> View
                </a>';
            })
            ->rawColumns(['type_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Display quotation details
     */
    public function show(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['lines.model', 'lines.charge', 'client', 'vendor', 'createdBy']);

        return view('technician.quotations.show', compact('quotation'));
    }
}
