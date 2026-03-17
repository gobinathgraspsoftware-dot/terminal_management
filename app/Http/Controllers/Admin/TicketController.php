<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\Vendor;
use App\Models\VendorBranch;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use App\Models\JobType;
use App\Models\ChargeCatalog;
use App\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected TicketService $ticketService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        $user = auth()->user();
        $stats = $this->ticketService->getStats($user);
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $supervisors = User::role('supervisor')->where('status', 'active')->orderBy('name')->get();
        $slaBreachedTickets = $this->ticketService->getSlaBreachedTickets($user);

        return view('admin.tickets.index', compact('stats', 'vendors', 'supervisors', 'slaBreachedTickets'));
    }

    public function datatable(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        try {
            $user = auth()->user();
            $result = $this->ticketService->getDatatable($request->all(), $user);

            $result['data'] = $result['data']->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_no' => $ticket->ticket_no,
                    'vendor_ticket_ref_no' => $ticket->vendor_ticket_ref_no ?? '-',
                    'vendor_name' => $ticket->vendor?->vendor_name ?? '-',
                    'branch_name' => $ticket->vendorBranch?->branch_name ?? '-',
                    'merchant_name' => $ticket->merchant_name ?? '-',
                    'tid' => $ticket->tid ?? '-',
                    'job_type' => $ticket->jobType?->job_title ?? '-',
                    'status' => $ticket->status,
                    'status_badge' => Ticket::getStatusBadge($ticket->status),
                    'priority' => $ticket->priority,
                    'priority_badge' => Ticket::getPriorityBadge($ticket->priority),
                    'supervisor_name' => $ticket->supervisor?->name ?? '-',
                    'technician_name' => $ticket->technician?->name ?? 'Unassigned',
                    'sla_deadline' => $ticket->sla_deadline?->format('d M Y H:i'),
                    'sla_remaining' => $ticket->sla_remaining,
                    'sla_breached' => $ticket->isSlaBreach(),
                    'total_claim' => number_format($ticket->total_claim_amount ?? 0, 2),
                    'created_at' => $ticket->created_at->format('d M Y H:i'),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Ticket Datatable Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load tickets'], 500);
        }
    }

    public function create()
    {
        $this->authorize('create', Ticket::class);
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $states = State::orderBy('name')->get();
        $jobTypes = JobType::where('status', 'active')->orderBy('job_title')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        return view('admin.tickets.create', compact('vendors', 'states', 'jobTypes', 'charges'));
    }

    public function store(StoreTicketRequest $request)
    {
        try {
            $ticket = $this->ticketService->create($request->validated());
            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_no} created successfully!",
                'redirect' => route('admin.tickets.show', $ticket->id),
            ]);
        } catch (\Exception $e) {
            Log::error('Ticket Create Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create ticket: ' . $e->getMessage()], 500);
        }
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load([
            'vendor', 'vendorBranch', 'state', 'city', 'charge',
            'supervisor', 'technician', 'jobType', 'creator', 'updater',
            'comments.user', 'statusHistory.changedBy', 'statusHistory.proofs', 'proofs',
        ]);

        $allowedTransitions = Ticket::getAllowedTransitions($ticket->status);
        $statuses = Ticket::getStatuses();
        $technicians = User::role('technician')->where('status', 'active')->orderBy('name')->get();

        return view('admin.tickets.show', compact('ticket', 'allowedTransitions', 'statuses', 'technicians'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        if (in_array($ticket->status, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL, Ticket::STATUS_CLOSED])) {
            return redirect()->route('admin.tickets.show', $ticket->id)
                ->with('warning', 'Cannot edit a completed or closed ticket.');
        }

        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $states = State::orderBy('name')->get();
        $cities = $ticket->state_id ? City::where('state_id', $ticket->state_id)->orderBy('name')->get() : collect();
        $branches = $ticket->vendor_id ? VendorBranch::where('vendor_id', $ticket->vendor_id)->where('status', 'active')->get() : collect();
        $jobTypes = JobType::where('status', 'active')->orderBy('job_title')->get();
        $charges = ChargeCatalog::where('status', 'active')->orderBy('charge_name')->get();

        // Load supervisors matching ticket's state/city
        $supervisorQuery = User::role('supervisor')->where('status', 'active');
        if ($ticket->state_id) {
            $supervisorQuery->where(function ($q) use ($ticket) {
                $q->where('state_id', $ticket->state_id)
                  ->orWhereJsonContains('coverage_states', (string) $ticket->state_id);
            });
        }
        $supervisors = $supervisorQuery->orderBy('name')->get();

        // Technicians for the selected supervisor
        $technicians = $ticket->supervisor_id
            ? User::where('supervisor_id', $ticket->supervisor_id)->where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('admin.tickets.edit', compact('ticket', 'vendors', 'states', 'cities', 'branches', 'supervisors', 'technicians', 'jobTypes', 'charges'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        try {
            $ticket = $this->ticketService->update($ticket, $request->validated());
            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_no} updated successfully!",
                'redirect' => route('admin.tickets.show', $ticket->id),
            ]);
        } catch (\Exception $e) {
            Log::error('Ticket Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update ticket.'], 500);
        }
    }

    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        try {
            $ticket->delete();
            return response()->json(['success' => true, 'message' => 'Ticket deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete ticket.'], 500);
        }
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('changeStatus', $ticket);

        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,scheduled,done_success,done_fail,closed',
            'remarks' => 'nullable|string|max:1000',
            'reschedule_reason' => 'nullable|required_if:status,scheduled|string|max:1000',
            'proof_files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        try {
            // Collect proof files keyed by proof type
            $proofFiles = [];
            if ($request->hasFile('proof_files')) {
                foreach ($request->file('proof_files') as $proofType => $file) {
                    $proofFiles[$proofType] = $file;
                }
            }

            $this->ticketService->changeStatus(
                $ticket,
                $request->status,
                $request->remarks,
                $request->reschedule_reason,
                $proofFiles
            );

            return response()->json(['success' => true, 'message' => 'Ticket status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorize('assign', $ticket);
        $request->validate([
            'technician_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $this->ticketService->assignTechnician($ticket, $request->technician_id, $request->remarks);
            return response()->json(['success' => true, 'message' => 'Technician assigned successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateClaim(Request $request, Ticket $ticket)
    {
        $this->authorize('updateClaim', $ticket);
        $request->validate([
            'mileage' => 'nullable|numeric|min:0',
            'mileage_remarks' => 'nullable|string|max:500',
            'toll' => 'nullable|numeric|min:0',
            'standby_meal' => 'nullable|numeric|min:0',
        ]);

        try {
            $this->ticketService->updateClaim($ticket, $request->all());
            return response()->json(['success' => true, 'message' => 'Claim updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $this->authorize('addComment', $ticket);
        $request->validate(['comment' => 'required|string|max:5000']);

        try {
            $comment = $this->ticketService->addComment($ticket, $request->comment);
            $comment->load('user');
            return response()->json([
                'success' => true,
                'message' => 'Comment added.',
                'comment' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $comment->user->name,
                    'user_role' => $comment->user->roles->first()?->name ?? 'user',
                    'created_at' => $comment->created_at->format('d M Y H:i'),
                    'is_own' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add comment.'], 500);
        }
    }

    // ── AJAX endpoints ──

    public function getVendorBranches(Request $request)
    {
        $branches = VendorBranch::where('vendor_id', $request->vendor_id)
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get(['id', 'branch_name', 'state_id', 'city_id']);
        return response()->json($branches);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->orderBy('name')->get(['id', 'name']);
        return response()->json($cities);
    }

    public function getTechnicians(Request $request)
    {
        $query = User::role('technician')->where('status', 'active');
        if ($request->supervisor_id) {
            $query->where('supervisor_id', $request->supervisor_id);
        }
        return response()->json($query->orderBy('name')->get(['id', 'name']));
    }

    /**
     * Get supervisors filtered by state_id and/or city_id.
     * Matches supervisors whose state_id matches OR whose coverage_states JSON contains the state.
     */
    public function getSupervisors(Request $request)
    {
        $query = User::whereHas('roles', fn($q) => $q->where('roles.name', 'supervisor'))
            ->where('status', 'active');

        if ($request->filled('state_id')) {
            $stateId = $request->state_id;
            $query->where(function ($q) use ($stateId) {
                $q->where('state_id', $stateId)
                  ->orWhereJsonContains('coverage_states', (string) $stateId);
            });
        }

        if ($request->filled('city_id')) {
            // If city_id provided, prefer supervisors matching that city, but still include state-level matches
            $cityId = $request->city_id;
            $query->orderByRaw('CASE WHEN city_id = ? THEN 0 ELSE 1 END', [$cityId]);
        }

        $supervisors = $query->orderBy('name')->get(['id', 'name', 'state_id', 'city_id', 'mileage_rate']);

        return response()->json($supervisors);
    }

    public function getSupervisorMileageRate(Request $request)
    {
        $supervisor = User::find($request->supervisor_id);
        return response()->json(['mileage_rate' => $supervisor?->mileage_rate ?? 0]);
    }

    public function getCharges(Request $request)
    {
        $query = ChargeCatalog::where('status', 'active');
        if ($request->job_type_id) {
            $query->where('job_type_id', $request->job_type_id);
        }
        return response()->json($query->orderBy('charge_name')->get(['id', 'charge_name', 'default_price']));
    }
}
