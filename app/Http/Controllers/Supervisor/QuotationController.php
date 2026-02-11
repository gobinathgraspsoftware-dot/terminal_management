<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\User;
use App\Models\TerminalModel;
use App\Models\ChargeCatalog;
use App\Services\QuotationService;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\ApproveQuotationRequest;
use App\Exports\QuotationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class QuotationController extends Controller
{
    use AuthorizesRequests;

    protected $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * Get team member IDs
     */
    protected function getTeamMemberIds()
    {
        $teamMemberIds = User::where('supervisor_id', Auth::id())->pluck('id')->toArray();
        $teamMemberIds[] = Auth::id(); // Include supervisor
        return $teamMemberIds;
    }

    /**
     * Display listing of quotations
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatable($request);
        }

        $clients = Client::where('status', 'active')->orderBy('client_name')->get();
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $statuses = Quotation::getStatusList();
        $types = Quotation::getTypeList();

        return view('supervisor.quotations.index', compact('clients', 'vendors', 'statuses', 'types'));
    }

    /**
     * DataTables AJAX endpoint - Team scoped
     */
    protected function datatable(Request $request)
    {
        $query = Quotation::with(['client', 'vendor', 'createdBy', 'approvedBy'])
            ->whereIn('created_by', $this->getTeamMemberIds())
            ->select('quotations.*');

        // Filters
        if ($request->filled('type')) {
            $query->where('quotation_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
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
                return view('supervisor.quotations._actions', compact('quotation'))->render();
            })
            ->rawColumns(['type_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $this->authorize('create', Quotation::class);

        $clients = Client::where('status', 'active')->orderBy('client_name')->get();
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        return view('supervisor.quotations.create', compact('clients', 'vendors', 'models', 'charges'));
    }

    /**
     * Store new quotation
     */
    public function store(StoreQuotationRequest $request)
    {
        try {
            $quotation = $this->quotationService->createQuotation($request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation created successfully.',
                    'redirect' => route('supervisor.quotations.show', $quotation)
                ]);
            }

            return redirect()->route('supervisor.quotations.show', $quotation)
                ->with('success', 'Quotation created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Display quotation
     */
    public function show(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['lines.model', 'lines.charge', 'client', 'vendor', 'createdBy', 'approvedBy', 'purchaseOrder']);

        return view('supervisor.quotations.show', compact('quotation'));
    }

    /**
     * Show edit form
     */
    public function edit(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        $quotation->load(['lines.model', 'lines.charge']);

        $clients = Client::where('status', 'active')->orderBy('client_name')->get();
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        return view('supervisor.quotations.edit', compact('quotation', 'clients', 'vendors', 'models', 'charges'));
    }

    /**
     * Update quotation
     */
    public function update(UpdateQuotationRequest $request, Quotation $quotation)
    {
        try {
            $quotation = $this->quotationService->updateQuotation($quotation, $request->validated());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quotation updated successfully.',
                    'redirect' => route('supervisor.quotations.show', $quotation)
                ]);
            }

            return redirect()->route('supervisor.quotations.show', $quotation)
                ->with('success', 'Quotation updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete quotation
     */
    public function destroy(Quotation $quotation)
    {
        $this->authorize('delete', $quotation);

        try {
            $quotation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Quotation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    // Other methods same as Admin controller...
    public function submitForApproval(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        try {
            $this->quotationService->submitForApproval($quotation);
            return response()->json(['success' => true, 'message' => 'Quotation submitted for approval successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function processApproval(ApproveQuotationRequest $request, Quotation $quotation)
    {
        try {
            if ($request->action === 'approve') {
                $this->quotationService->approveQuotation($quotation);
                $message = 'Quotation approved successfully.';
            } else {
                $this->quotationService->rejectQuotation($quotation, $request->reason);
                $message = 'Quotation rejected successfully.';
            }
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function send(Quotation $quotation)
    {
        $this->authorize('send', $quotation);

        try {
            $this->quotationService->sendQuotation($quotation);
            return response()->json(['success' => true, 'message' => 'Quotation sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function convertToPO(Quotation $quotation)
    {
        $this->authorize('convertToPO', $quotation);

        try {
            $po = $this->quotationService->convertToPurchaseOrder($quotation);
            return response()->json([
                'success' => true,
                'message' => 'Quotation converted to Purchase Order successfully.',
                'redirect' => route('supervisor.purchase-orders.show', $po)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request)
    {
        $this->authorize('export', Quotation::class);

        $filters = $request->only(['quotation_type', 'status', 'client_id', 'vendor_id', 'date_from', 'date_to']);

        return Excel::download(
            new QuotationsExport($filters),
            'quotations_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function print(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['lines.model', 'lines.charge', 'client', 'vendor']);

        return view('supervisor.quotations.print', compact('quotation'));
    }

    public function getModelPrice($modelId)
    {
        $model = TerminalModel::find($modelId);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Model not found'], 404);
        }

        return response()->json([
            'success' => true,
            'price' => $model->selling_price ?? 0,
            'description' => $model->model_name,
            'unit' => 'pcs'
        ]);
    }

    public function getChargePrice($chargeId)
    {
        $charge = ChargeCatalog::find($chargeId);
        if (!$charge) {
            return response()->json(['success' => false, 'message' => 'Charge not found'], 404);
        }

        return response()->json([
            'success' => true,
            'price' => $charge->unit_price ?? 0,
            'description' => $charge->charge_name,
            'unit' => $charge->unit ?? 'service'
        ]);
    }
}
