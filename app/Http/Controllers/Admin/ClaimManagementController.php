<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPaymentRequest;
use App\Http\Requests\StoreOtherClaimRequest;
use App\Http\Requests\VerifyClaimRequest;
use App\Models\Claim;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ClaimManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimManagementController extends Controller
{
    use AuthorizesRequests;

    protected ClaimManagementService $service;

    public function __construct(ClaimManagementService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════
    // Landing Page
    // ══════════════════════════════════════════════

    /**
     * Claim Management landing page (choose category)
     */
    public function index()
    {
        $this->authorize('viewAny', Claim::class);

        $stats = [
            'ticket_submitted'  => Claim::ticketClaims()->submitted()->count(),
            'ticket_verified'   => Claim::ticketClaims()->verified()->count(),
            'other_submitted'   => Claim::otherClaims()->submitted()->count(),
            'other_verified'    => Claim::otherClaims()->verified()->count(),
            'pending_payment'   => Claim::pendingPayment()->count(),
        ];

        return view('admin.claims.index', compact('stats'));
    }

    // ══════════════════════════════════════════════
    // Ticket Claims
    // ══════════════════════════════════════════════

    /**
     * Ticket Claims listing
     */
    public function ticketClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('admin.claims.ticket-claims');
    }

    /**
     * DataTable AJAX for ticket claims
     */
    public function ticketClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_TICKET);

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'ticket_no'       => $claim->ticket->ticket_no ?? '-',
                'vendor'          => $claim->ticket->vendor->company_name ?? '-',
                'merchant_name'   => $claim->ticket->merchant_name ?? '-',
                'supervisor'      => $claim->ticket->supervisor->name ?? '-',
                'technician'      => $claim->technician->name ?? '-',
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'actions'         => $this->getActionButtons($claim, 'ticket'),
            ];
        });

        return response()->json($result);
    }

    /**
     * Create Ticket Claim form - select from completed tickets
     */
    public function createTicketClaim()
    {
        $this->authorize('create', Claim::class);

        // Get completed tickets that don't already have a ticket claim
        $existingTicketIds = Claim::ticketClaims()->whereNotNull('ticket_id')->pluck('ticket_id')->toArray();

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_DONE_SUCCESS,
                Ticket::STATUS_DONE_FAIL,
                Ticket::STATUS_CLOSED,
            ])
            ->whereNotIn('id', $existingTicketIds)
            ->with(['vendor', 'supervisor', 'technician', 'jobType'])
            ->orderBy('completed_at', 'desc')
            ->get();

        return view('admin.claims.create-ticket-claim', compact('tickets'));
    }

    /**
     * Get ticket details via AJAX
     */
    public function getTicketDetails(Ticket $ticket)
    {
        $ticket->load(['vendor', 'supervisor', 'technician', 'jobType']);

        return response()->json([
            'success'          => true,
            'ticket_no'        => $ticket->ticket_no,
            'vendor'           => $ticket->vendor->company_name ?? '-',
            'merchant_name'    => $ticket->merchant_name ?? '-',
            'supervisor'       => $ticket->supervisor->name ?? '-',
            'technician'       => $ticket->technician->name ?? '-',
            'technician_id'    => $ticket->technician_id,
            'job_type'         => $ticket->jobType->name ?? '-',
            'mileage'          => $ticket->mileage ?? 0,
            'mileage_amount'   => $ticket->mileage_amount ?? 0,
            'toll'             => $ticket->toll ?? 0,
            'standby_meal'     => $ticket->standby_meal ?? 0,
            'total_claim'      => $ticket->total_claim_amount ?? 0,
            'mileage_remarks'  => $ticket->mileage_remarks ?? '',
        ]);
    }

    /**
     * Store Ticket Claim
     */
    public function storeTicketClaim(Request $request)
    {
        $this->authorize('create', Claim::class);

        $request->validate([
            'ticket_id'          => 'required|integer|exists:tickets,id',
            'total_claim_amount' => 'required|numeric|min:0',
            'mileage'            => 'nullable|numeric|min:0',
            'mileage_amount'     => 'nullable|numeric|min:0',
            'toll'               => 'nullable|numeric|min:0',
            'standby_meal'       => 'nullable|numeric|min:0',
            'remarks'            => 'nullable|string|max:2000',
        ]);

        try {
            $ticket = Ticket::findOrFail($request->input('ticket_id'));

            // Check duplicate
            $exists = Claim::ticketClaims()->where('ticket_id', $ticket->id)->exists();
            if ($exists) {
                return response()->json(['success' => false, 'message' => 'A ticket claim already exists for this ticket.'], 422);
            }

            $claim = $this->service->createTicketClaim($ticket, $request->all());
            return response()->json(['success' => true, 'message' => "Ticket Claim {$claim->claim_no} created successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Other Claims
    // ══════════════════════════════════════════════

    /**
     * Other Claims listing
     */
    public function otherClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('admin.claims.other-claims');
    }

    /**
     * DataTable AJAX for other claims
     */
    public function otherClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_OTHER);

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'submitted_by'    => $claim->submitter->name ?? ($claim->technician->name ?? '-'),
                'claim_type'      => $claim->claim_type_label ?? 'Others',
                'description'     => \Illuminate\Support\Str::limit($claim->description, 50),
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'ticket_no'       => $claim->ticket->ticket_no ?? '-',
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'has_attachments' => $claim->attachments->isNotEmpty() ? '<i class="bi bi-paperclip text-primary"></i>' : '',
                'actions'         => $this->getActionButtons($claim, 'other'),
            ];
        });

        return response()->json($result);
    }

    /**
     * Create Other Claim form
     */
    public function createOtherClaim()
    {
        $this->authorize('create', Claim::class);

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_DONE_SUCCESS,
                Ticket::STATUS_DONE_FAIL,
                Ticket::STATUS_CLOSED,
            ])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes  = Claim::getOtherClaimTypes();
        $technicians = User::whereHas('roles', fn($q) => $q->where('roles.name', 'technician'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.claims.create-other-claim', compact('tickets', 'claimTypes', 'technicians'));
    }

    /**
     * Store Other Claim
     */
    public function storeOtherClaim(StoreOtherClaimRequest $request)
    {
        $this->authorize('create', Claim::class);

        try {
            $files = $request->file('attachments', []);
            $claim = $this->service->createOtherClaim($request->validated(), is_array($files) ? $files : [$files]);
            return response()->json(['success' => true, 'message' => "Other Claim {$claim->claim_no} created successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Show / Verify / Actions
    // ══════════════════════════════════════════════

    /**
     * Show claim detail (both categories)
     */
    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load([
            'technician', 'submitter', 'verifier', 'payer',
            'ticket.vendor', 'ticket.supervisor', 'ticket.jobType',
            'ticket.proofs', 'attachments.uploadedBy',
        ]);

        return view('admin.claims.show', compact('claim'));
    }

    /**
     * Verify a claim
     */
    public function verify(VerifyClaimRequest $request, Claim $claim)
    {
        $this->authorize('verify', $claim);

        try {
            $this->service->verifyClaim($claim, $request->validated());
            return response()->json(['success' => true, 'message' => 'Claim verified successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark claim as non-claimable
     */
    public function markNonClaimable(Request $request, Claim $claim)
    {
        $this->authorize('verify', $claim);

        $request->validate(['admin_remarks' => 'required|string|max:2000']);

        try {
            $this->service->markNonClaimable($claim, $request->only('admin_remarks'));
            return response()->json(['success' => true, 'message' => 'Claim marked as Non-Claimable.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update claim amount (AJAX)
     */
    public function updateAmount(Request $request, Claim $claim)
    {
        $this->authorize('verify', $claim);

        $request->validate([
            'total_amount'  => 'required|numeric|min:0',
            'admin_remarks' => 'nullable|string|max:2000',
        ]);

        try {
            $this->service->updateClaimAmount(
                $claim,
                (float) $request->input('total_amount'),
                $request->input('admin_remarks')
            );
            return response()->json(['success' => true, 'message' => 'Claim amount updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Bulk Payment
    // ══════════════════════════════════════════════

    /**
     * Bulk Payment page
     */
    public function bulkPayment(Request $request)
    {
        $this->authorize('bulkPay', Claim::class);

        $category = $request->input('category', 'all');

        $query = Claim::verified()->with(['technician', 'submitter', 'ticket']);

        if ($category === 'ticket') {
            $query->ticketClaims();
        } elseif ($category === 'other') {
            $query->otherClaims();
        }

        $verifiedClaims = $query->orderBy('submitted_at', 'asc')->get();

        return view('admin.claims.bulk-payment', compact('verifiedClaims', 'category'));
    }

    /**
     * Process bulk payment (AJAX)
     */
    public function processBulkPayment(BulkPaymentRequest $request)
    {
        $this->authorize('bulkPay', Claim::class);

        try {
            $result = $this->service->processBulkPayment($request->input('claim_ids'));
            return response()->json([
                'success' => true,
                'message' => "{$result['processed']} claim(s) marked as Pending Payment. Total: RM " . number_format($result['total_amount'], 2),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark selected claims as paid (AJAX)
     */
    public function markPaid(Request $request)
    {
        $this->authorize('bulkPay', Claim::class);

        $request->validate(['claim_ids' => 'required|array|min:1', 'claim_ids.*' => 'integer|exists:claims,id']);

        try {
            $count = $this->service->markAsPaid($request->input('claim_ids'));
            return response()->json(['success' => true, 'message' => "{$count} claim(s) marked as Paid."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Export
    // ══════════════════════════════════════════════

    /**
     * Export claims
     */
    public function export(Request $request)
    {
        $this->authorize('export', Claim::class);

        $category = $request->input('category', 'all');
        $status   = $request->input('status');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ClaimsManagementExport($category, $status),
            'claims_' . $category . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // ══════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════

    /**
     * Generate action buttons for DataTable
     */
    protected function getActionButtons(Claim $claim, string $type): string
    {
        $showUrl = route('admin.claims.show', $claim->id);
        $html = '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
        return $html;
    }
}
