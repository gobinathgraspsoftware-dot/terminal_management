<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClaimRequest;
use App\Models\Claim;
use App\Models\ClaimAttachment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ClaimManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimController extends Controller
{
    use AuthorizesRequests;

    protected ClaimManagementService $service;

    public function __construct(ClaimManagementService $service)
    {
        $this->service = $service;
    }

    // ══════════════════════════════════════════════
    // Landing Page (tabbed: Ticket Claims / Other Claims)
    // ══════════════════════════════════════════════

    public function index(Request $request)
    {
        $this->authorize('viewAny', Claim::class);

        $user = Auth::user();

        $stats = [
            'ticket_total'     => Claim::ticketClaims()->where('technician_id', $user->id)->count(),
            'ticket_submitted' => Claim::ticketClaims()->submitted()->where('technician_id', $user->id)->count(),
            'other_total'      => Claim::otherClaims()->where(function ($q) use ($user) {
                $q->where('technician_id', $user->id)->orWhere('submitted_by', $user->id);
            })->count(),
            'other_submitted'  => Claim::otherClaims()->submitted()->where(function ($q) use ($user) {
                $q->where('technician_id', $user->id)->orWhere('submitted_by', $user->id);
            })->count(),
        ];

        $activeTab = $request->input('tab', 'ticket');

        return view('technician.claims.index', compact('stats', 'activeTab'));
    }

    // ══════════════════════════════════════════════
    // Ticket Claims (View Only — auto-created on ticket completion)
    // ══════════════════════════════════════════════

    public function ticketClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('technician.claims.ticket-claims');
    }

    public function ticketClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_TICKET, Auth::user(), 'own');

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'ticket_no'       => $claim->ticket->ticket_no ?? '-',
                'vendor'          => $claim->ticket->vendor->company_name ?? '-',
                'merchant_name'   => $claim->ticket->merchant_name ?? '-',
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'actions'         => $this->getTicketClaimActions($claim),
            ];
        });

        return response()->json($result);
    }

    // ══════════════════════════════════════════════
    // Other Claims (Create + Edit + View)
    // ══════════════════════════════════════════════

    public function otherClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('technician.claims.other-claims');
    }

    public function otherClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_OTHER, Auth::user(), 'own');

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'claim_type'      => $claim->claim_type_label ?? 'Others',
                'description'     => \Illuminate\Support\Str::limit($claim->description, 40),
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'has_attachments' => $claim->attachments->isNotEmpty() ? '<i class="bi bi-paperclip text-primary"></i>' : '',
                'actions'         => $this->getOtherClaimActions($claim),
            ];
        });

        return response()->json($result);
    }

    /**
     * Create Other Claim form
     */
    public function create()
    {
        $this->authorize('create', Claim::class);

        $user = Auth::user();

        $tickets = Ticket::where('technician_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes = Claim::getOtherClaimTypes();

        return view('technician.claims.create', compact('tickets', 'claimTypes'));
    }

    /**
     * Store Other Claim
     */
    public function store(Request $request)
    {
        $this->authorize('create', Claim::class);

        $request->validate([
            'claim_type_label' => 'required|string|max:100',
            'description'      => 'required|string|max:2000',
            'claim_amount'     => 'required|numeric|min:0.01',
            'ticket_id'        => 'nullable|integer|exists:tickets,id',
            'remarks'          => 'nullable|string|max:2000',
            'attachments'      => 'nullable|array|max:5',
            'attachments.*'    => 'file|mimes:pdf,png,jpg,jpeg|max:5120',
        ]);

        try {
            // Force technician_id to self
            $data = $request->all();
            $data['technician_id'] = Auth::id();

            $files = $request->file('attachments', []);
            $claim = $this->service->createOtherClaim($data, is_array($files) ? $files : [$files]);
            return response()->json(['success' => true, 'message' => "Claim {$claim->claim_no} submitted successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Edit Other Claim form (technician can edit own claims in draft/submitted status)
     */
    public function edit(Claim $claim)
    {
        $this->authorize('update', $claim);

        // Only other claims can be edited by technician
        if ($claim->claim_category !== Claim::CATEGORY_OTHER) {
            return redirect()->route('technician.claims.show', $claim->id)
                ->with('error', 'Ticket claims cannot be edited directly.');
        }

        $user = Auth::user();

        $claim->load(['attachments.uploadedBy']);

        $tickets = Ticket::where('technician_id', $user->id)
            ->whereIn('status', [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes = Claim::getOtherClaimTypes();

        return view('technician.claims.edit', compact('claim', 'tickets', 'claimTypes'));
    }

    /**
     * Update Other Claim (technician can update own claims in draft/submitted status)
     */
    public function update(UpdateClaimRequest $request, Claim $claim)
    {
        $this->authorize('update', $claim);

        // Only other claims can be updated by technician
        if ($claim->claim_category !== Claim::CATEGORY_OTHER) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket claims cannot be edited directly.',
            ], 403);
        }

        try {
            $files = $request->file('attachments', []);
            $updated = $this->service->updateOtherClaim($claim, $request->validated(), is_array($files) ? $files : [$files]);
            return response()->json([
                'success' => true,
                'message' => "Claim {$updated->claim_no} updated successfully.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an attachment from a claim (AJAX)
     */
    public function deleteAttachment(Claim $claim, ClaimAttachment $attachment)
    {
        $this->authorize('update', $claim);

        if ($attachment->claim_id !== $claim->id) {
            return response()->json(['success' => false, 'message' => 'Attachment does not belong to this claim.'], 403);
        }

        try {
            $this->service->deleteAttachment($attachment);
            return response()->json(['success' => true, 'message' => 'Attachment deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Show
    // ══════════════════════════════════════════════

    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load([
            'technician', 'submitter', 'verifier', 'payer',
            'ticket.vendor', 'ticket.supervisor', 'ticket.jobType',
            'attachments.uploadedBy',
        ]);

        return view('technician.claims.show', compact('claim'));
    }

    // ══════════════════════════════════════════════
    // Action Button Helpers
    // ══════════════════════════════════════════════

    /**
     * Generate action buttons for ticket claims DataTable
     */
    protected function getTicketClaimActions(Claim $claim): string
    {
        $showUrl = route('technician.claims.show', $claim->id);
        return '<a href="' . $showUrl . '" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';
    }

    /**
     * Generate action buttons for other claims DataTable
     */
    protected function getOtherClaimActions(Claim $claim): string
    {
        $showUrl = route('technician.claims.show', $claim->id);
        $html = '<div class="btn-group btn-group-sm">';
        $html .= '<a href="' . $showUrl . '" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>';

        // Show edit button only for editable claims (draft/submitted)
        if ($claim->isEditable() && (
            $claim->technician_id === Auth::id() || $claim->submitted_by === Auth::id()
        )) {
            $editUrl = route('technician.claims.edit', $claim->id);
            $html .= '<a href="' . $editUrl . '" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        $html .= '</div>';
        return $html;
    }
}
