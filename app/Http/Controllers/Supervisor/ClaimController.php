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

    /**
     * My Claims & Team Claims listing
     */
    public function index()
    {
        $this->authorize('viewAny', Claim::class);
        return view('supervisor.claims.index');
    }

    /**
     * DataTable AJAX
     */
    public function data(Request $request)
    {
        $this->authorize('viewAny', Claim::class);
        $user = Auth::user();

        // Combine both categories for supervisor
        $draw        = (int) $request->input('draw', 1);
        $start       = (int) $request->input('start', 0);
        $length      = (int) $request->input('length', 10);
        $searchValue = $request->input('search.value', '');
        $orderDir    = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
        $teamIds[] = $user->id;

        $query = Claim::with(['technician', 'submitter', 'ticket'])
            ->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)
                  ->orWhere('submitted_by', $user->id)
                  ->orWhere('created_by', $user->id);
            });

        if ($request->filled('category')) {
            $query->where('claim_category', $request->input('category'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $recordsTotal = $query->count();

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('claim_no', 'like', "%{$searchValue}%")
                  ->orWhere('description', 'like', "%{$searchValue}%")
                  ->orWhereHas('technician', fn($tq) => $tq->where('name', 'like', "%{$searchValue}%"));
            });
        }

        $recordsFiltered = $query->count();
        $data = $query->orderBy('submitted_at', $orderDir)->skip($start)->take($length)->get();

        $rows = $data->map(function ($claim) {
            return [
                'id'           => $claim->id,
                'claim_no'     => $claim->claim_no,
                'category'     => $claim->claim_category === 'ticket'
                    ? '<span class="badge bg-info">Ticket</span>'
                    : '<span class="badge bg-secondary">Other</span>',
                'ticket_no'    => $claim->ticket->ticket_no ?? '-',
                'submitted_by' => $claim->submitter->name ?? ($claim->technician->name ?? '-'),
                'description'  => \Illuminate\Support\Str::limit($claim->description, 40),
                'total_amount' => number_format((float) $claim->total_amount, 2),
                'status'       => Claim::getStatusBadge($claim->status),
                'submitted_at' => $claim->submitted_at ? $claim->submitted_at->format('d/m/Y H:i') : '-',
                'actions'      => '<a href="' . route('supervisor.claims.show', $claim->id) . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>',
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ]);
    }

    /**
     * Create Other Claim form
     */
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

    /**
     * Store Other Claim
     */
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

    /**
     * Show claim detail (view only)
     */
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
