<?php

namespace App\Http\Controllers\Supervisor;

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
        $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
        $teamIds[] = $user->id;

        $stats = [
            'ticket_total'     => Claim::ticketClaims()->whereIn('technician_id', $teamIds)->count(),
            'ticket_submitted' => Claim::ticketClaims()->submitted()->whereIn('technician_id', $teamIds)->count(),
            'other_total'      => Claim::otherClaims()->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)->orWhere('submitted_by', $user->id);
            })->count(),
            'other_submitted'  => Claim::otherClaims()->submitted()->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)->orWhere('submitted_by', $user->id);
            })->count(),
        ];

        $activeTab = $request->input('tab', 'ticket');

        return view('supervisor.claims.index', compact('stats', 'activeTab'));
    }

    // ══════════════════════════════════════════════
    // Ticket Claims (Create + View)
    // ══════════════════════════════════════════════

    public function ticketClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('supervisor.claims.ticket-claims');
    }

    public function ticketClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_TICKET, Auth::user(), 'team');

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'ticket_no'       => $claim->ticket->ticket_no ?? '-',
                'vendor'          => $claim->ticket->vendor->company_name ?? '-',
                'merchant_name'   => $claim->ticket->merchant_name ?? '-',
                'technician'      => $claim->technician->name ?? '-',
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'actions'         => '<a href="' . route('supervisor.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json($result);
    }

    /**
     * Create Ticket Claim form — select from team's completed tickets
     */
    public function createTicketClaim()
    {
        $this->authorize('create', Claim::class);

        $user = Auth::user();
        $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
        $teamIds[] = $user->id;

        $existingTicketIds = Claim::ticketClaims()->whereNotNull('ticket_id')->pluck('ticket_id')->toArray();

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_DONE_SUCCESS,
                Ticket::STATUS_DONE_FAIL,
                Ticket::STATUS_CLOSED,
            ])
            ->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)
                  ->orWhere('supervisor_id', $user->id);
            })
            ->whereNotIn('id', $existingTicketIds)
            ->with(['vendor', 'supervisor', 'technician', 'jobType'])
            ->orderBy('completed_at', 'desc')
            ->get();

        return view('supervisor.claims.create-ticket-claim', compact('tickets'));
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
    // Other Claims (Create + View)
    // ══════════════════════════════════════════════

    public function otherClaims()
    {
        $this->authorize('viewAny', Claim::class);
        return view('supervisor.claims.other-claims');
    }

    public function otherClaimsData(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $result = $this->service->getClaimsDataTable($request, Claim::CATEGORY_OTHER, Auth::user(), 'team');

        $result['data'] = $result['data']->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'claim_no'        => $claim->claim_no,
                'submitted_by'    => $claim->submitter->name ?? ($claim->technician->name ?? '-'),
                'claim_type'      => $claim->claim_type_label ?? 'Others',
                'description'     => \Illuminate\Support\Str::limit($claim->description, 40),
                'total_amount'    => number_format((float) $claim->total_amount, 2),
                'status'          => Claim::getStatusBadge($claim->status),
                'submitted_at'    => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'has_attachments' => $claim->attachments->isNotEmpty() ? '<i class="bi bi-paperclip text-primary"></i>' : '',
                'actions'         => '<a href="' . route('supervisor.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json($result);
    }

    public function create()
    {
        $this->authorize('create', Claim::class);

        $tickets = Ticket::visibleTo(Auth::user())
            ->whereIn('status', [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes = Claim::getOtherClaimTypes();

        return view('supervisor.claims.create', compact('tickets', 'claimTypes'));
    }

    public function store(StoreOtherClaimRequest $request)
    {
        $this->authorize('create', Claim::class);

        try {
            $files = $request->file('attachments', []);
            $claim = $this->service->createOtherClaim($request->validated(), is_array($files) ? $files : [$files]);
            return response()->json(['success' => true, 'message' => "Claim {$claim->claim_no} submitted successfully."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Show (View Only)
    // ══════════════════════════════════════════════

    public function show(Claim $claim)
    {
        $this->authorize('view', $claim);

        $claim->load([
            'technician', 'submitter', 'verifier', 'payer',
            'ticket.vendor', 'ticket.supervisor', 'ticket.jobType',
            'attachments.uploadedBy',
        ]);

        return view('supervisor.claims.show', compact('claim'));
    }
}
