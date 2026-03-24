<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Models\User;
use App\Models\TerminalModel;
use App\Models\ChargeCatalog;
use App\Services\QuotationService;
use App\Services\QuotationPdfService;
use App\Mail\QuotationEmail;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Requests\ApproveQuotationRequest;
use App\Exports\QuotationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

// Removed: Client model import — table dropped

class QuotationController extends Controller
{
    use AuthorizesRequests;

    protected $quotationService;
    protected $pdfService;

    public function __construct(
        QuotationService $quotationService,
        QuotationPdfService $pdfService
    ) {
        $this->quotationService = $quotationService;
        $this->pdfService = $pdfService;
    }

    /**
     * Get team member IDs
     */
    protected function getTeamMemberIds()
    {
        $teamMemberIds = User::where('supervisor_id', Auth::id())->pluck('id')->toArray();
        $teamMemberIds[] = Auth::id();
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

        // Removed: $clients — table dropped
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $statuses = Quotation::getStatusList();
        $types = Quotation::getTypeList();

        return view('supervisor.quotations.index', compact('vendors', 'statuses', 'types'));
    }

    /**
     * DataTables AJAX endpoint - Team scoped
     */
    protected function datatable(Request $request)
    {
        $query = Quotation::with(['vendor', 'createdBy', 'approvedBy'])
            ->whereIn('created_by', $this->getTeamMemberIds())
            ->select('quotations.*');

        if ($request->filled('type')) {
            $query->where('quotation_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Removed: client_id filter — table dropped

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
            ->addColumn('actions', function ($q) {
                $actions = '<div class="btn-group btn-group-sm">';
                $actions .= '<a href="' . route('supervisor.quotations.show', $q) . '" class="btn btn-info" title="View"><i class="bi bi-eye"></i></a>';

                if (auth()->user()->can('update', $q)) {
                    $actions .= '<a href="' . route('supervisor.quotations.edit', $q) . '" class="btn btn-primary" title="Edit"><i class="bi bi-pencil"></i></a>';
                }

                $actions .= '<a href="' . route('supervisor.quotations.pdf.download', $q) . '" class="btn btn-danger" title="Download PDF"><i class="bi bi-file-pdf"></i></a>';

                if (auth()->user()->can('delete', $q)) {
                    $actions .= '<button type="button" class="btn btn-danger delete-btn" data-id="' . $q->id . '" title="Delete"><i class="bi bi-trash"></i></button>';
                }

                $actions .= '</div>';
                return $actions;
            })
            ->editColumn('quotation_date', fn($q) => $q->quotation_date->format('d M Y'))
            ->editColumn('valid_until', function($q) {
                if ($q->valid_until) {
                    if ($q->valid_until->isPast()) {
                        return '<span class="text-danger fw-bold">' . $q->valid_until->format('d M Y') . '</span>';
                    }
                    return $q->valid_until->format('d M Y');
                }
                return '<span class="text-muted">N/A</span>';
            })
            ->editColumn('total_amount', fn($q) => $q->currency . ' ' . number_format($q->total_amount, 2))
            ->rawColumns(['type_badge', 'status_badge', 'actions', 'valid_until'])
            ->make(true);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $this->authorize('create', Quotation::class);

        // Removed: $clients — table dropped
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        return view('supervisor.quotations.create', compact('vendors', 'models', 'charges'));
    }

    /**
     * Store quotation
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

        $quotation->load(['lines.model', 'lines.charge', 'vendor', 'createdBy', 'approvedBy', 'purchaseOrder']);

        return view('supervisor.quotations.show', compact('quotation'));
    }

    /**
     * Show edit form
     */
    public function edit(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        $quotation->load(['lines.model', 'lines.charge']);

        // Removed: $clients — table dropped
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $models = TerminalModel::where('status', 'active')->orderBy('model_name')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        return view('supervisor.quotations.edit', compact('quotation', 'vendors', 'models', 'charges'));
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
            $this->quotationService->deleteQuotation($quotation);

            return response()->json([
                'success' => true,
                'message' => 'Quotation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Approve quotation
     */
    public function approve(ApproveQuotationRequest $request, Quotation $quotation)
    {
        try {
            $this->quotationService->approveQuotation($quotation, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Quotation approved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reject quotation
     */
    public function reject(Request $request, Quotation $quotation)
    {
        $this->authorize('reject', $quotation);

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->quotationService->rejectQuotation($quotation, $request->rejection_reason);
            return response()->json(['success' => true, 'message' => 'Quotation rejected.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Send quotation
     */
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

    /**
     * Accept quotation
     */
    public function accept(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        try {
            $this->quotationService->acceptQuotation($quotation);

            return response()->json([
                'success' => true,
                'message' => 'Quotation accepted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel quotation
     */
    public function cancel(Quotation $quotation)
    {
        $this->authorize('update', $quotation);

        try {
            $this->quotationService->cancelQuotation($quotation);

            return response()->json([
                'success' => true,
                'message' => 'Quotation cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Convert to PO
     */
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
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Duplicate quotation
     */
    public function duplicate(Quotation $quotation)
    {
        $this->authorize('create', Quotation::class);

        try {
            $newQuotation = $this->quotationService->duplicateQuotation($quotation);

            return response()->json([
                'success' => true,
                'message' => 'Quotation duplicated successfully.',
                'redirect' => route('supervisor.quotations.edit', $newQuotation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Export to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('export', Quotation::class);

        $filters = $request->only(['quotation_type', 'status', 'vendor_id', 'date_from', 'date_to']);
        $filters['team_member_ids'] = $this->getTeamMemberIds();

        return Excel::download(
            new QuotationsExport($filters),
            'quotations_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Print quotation (HTML preview)
     */
    public function print(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        $quotation->load(['lines.model', 'lines.charge', 'vendor']);

        return view('supervisor.quotations.print', compact('quotation'));
    }

    /**
     * Download quotation as PDF
     */
    public function downloadPdf(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        try {
            $this->pdfService->validateQuotation($quotation);
            $watermark = $this->pdfService->getWatermark($quotation);

            return $this->pdfService->downloadPdf($quotation, [
                'watermark' => $watermark
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Preview quotation PDF in browser
     */
    public function previewPdf(Quotation $quotation)
    {
        $this->authorize('view', $quotation);

        try {
            $this->pdfService->validateQuotation($quotation);
            $watermark = $this->pdfService->getWatermark($quotation);

            return $this->pdfService->streamPdf($quotation, [
                'watermark' => $watermark
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Email quotation with PDF
     */
    public function emailPdf(Request $request, Quotation $quotation)
    {
        $this->authorize('send', $quotation);

        $request->validate([
            'email' => 'required|email',
            'subject' => 'nullable|string|max:255',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        try {
            $this->pdfService->validateQuotation($quotation);

            $recipientEmail = $request->email;

            $emailOptions = [
                'subject' => $request->subject,
                'custom_message' => $request->custom_message,
            ];

            Mail::to($recipientEmail)->send(new QuotationEmail($quotation, $emailOptions));

            if ($quotation->status === Quotation::STATUS_APPROVED) {
                $this->quotationService->sendQuotation($quotation);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quotation emailed successfully to ' . $recipientEmail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get model price (AJAX)
     */
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

    /**
     * Get charge price (AJAX)
     */
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
