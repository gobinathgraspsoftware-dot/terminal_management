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

        $isInternal = $user->isInternalSupervisor();

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

        return view('supervisor.claims.index', compact('stats', 'activeTab', 'isInternal'));
    }

    // ══════════════════════════════════════════════
    // Ticket Claims (View Only — auto-created on ticket completion)
    // ══════════════════════════════════════════════

    public function ticketClaims()
    {
        $this->authorize('viewAny', Claim::class);

        $isInternal = Auth::user()->isInternalSupervisor();

        return view('supervisor.claims.ticket-claims', compact('isInternal'));
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

    // ══════════════════════════════════════════════
    // Other Claims (Create + View)
    // ══════════════════════════════════════════════

    public function otherClaims()
    {
        $this->authorize('viewAny', Claim::class);

        $isInternal = Auth::user()->isInternalSupervisor();

        return view('supervisor.claims.other-claims', compact('isInternal'));
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

    /**
     * Create Other Claim form — external supervisors only
     */
    public function create()
    {
        $this->authorize('create', Claim::class);

        $user = Auth::user();

        // Double-check: internal supervisors should not reach here
        if ($user->isInternalSupervisor()) {
            return redirect()->route('supervisor.claims.index')
                ->with('error', 'Internal supervisors cannot submit claims.');
        }

        $tickets = Ticket::visibleTo($user)
            ->whereIn('status', [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED])
            ->orderBy('ticket_no', 'desc')
            ->get(['id', 'ticket_no', 'merchant_name']);

        $claimTypes = Claim::getOtherClaimTypes();

        return view('supervisor.claims.create', compact('tickets', 'claimTypes'));
    }

    /**
     * Store Other Claim — external supervisors only
     */
    public function store(StoreOtherClaimRequest $request)
    {
        $this->authorize('create', Claim::class);

        $user = Auth::user();

        // Double-check: internal supervisors should not reach here
        if ($user->isInternalSupervisor()) {
            return response()->json([
                'success' => false,
                'message' => 'Internal supervisors cannot submit claims.',
            ], 403);
        }

        try {
            // Force technician_id to self for external supervisors
            $data = $request->validated();
            $data['technician_id'] = $user->id;

            $files = $request->file('attachments', []);
            $claim = $this->service->createOtherClaim($data, is_array($files) ? $files : [$files]);
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

        $isInternal = Auth::user()->isInternalSupervisor();

        return view('supervisor.claims.show', compact('claim', 'isInternal'));
    }
}
