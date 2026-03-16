<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOtherClaimRequest;
use App\Models\Claim;
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
    // Ticket Claims
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
                'actions'         => '<a href="' . route('technician.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json($result);
    }

    /**
     * Create Ticket Claim form — own completed tickets only
     */
    public function createTicketClaim()
    {
        $this->authorize('create', Claim::class);

        $user = Auth::user();

        $existingTicketIds = Claim::ticketClaims()->whereNotNull('ticket_id')->pluck('ticket_id')->toArray();

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_DONE_SUCCESS,
                Ticket::STATUS_DONE_FAIL,
                Ticket::STATUS_CLOSED,
            ])
            ->where('technician_id', $user->id)
            ->whereNotIn('id', $existingTicketIds)
            ->with(['vendor', 'supervisor', 'technician', 'jobType'])
            ->orderBy('completed_at', 'desc')
            ->get();

        return view('technician.claims.create-ticket-claim', compact('tickets'));
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
            $ticket = Ticket::where('technician_id', Auth::id())
                ->findOrFail($request->input('ticket_id'));

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
                'actions'         => '<a href="' . route('technician.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
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
}
