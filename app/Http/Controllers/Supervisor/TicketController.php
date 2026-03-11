<?php

namespace App\Http\Controllers\Supervisor;

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
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    public function __construct(protected TicketService $ticketService) {}

    public function index()
    {
        $user = auth()->user();
        $stats = $this->ticketService->getStats($user);
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $slaBreachedTickets = $this->ticketService->getSlaBreachedTickets($user);

        return view('supervisor.tickets.index', compact('stats', 'vendors', 'slaBreachedTickets'));
    }

    public function datatable(Request $request)
    {
        try {
            $user = auth()->user();
            $result = $this->ticketService->getDatatable($request->all(), $user);

            $result['data'] = $result['data']->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_no' => $ticket->ticket_no,
                    'vendor_name' => $ticket->vendor?->vendor_name ?? '-',
                    'branch_name' => $ticket->vendorBranch?->branch_name ?? '-',
                    'job_type' => $ticket->jobType?->name ?? '-',
                    'status' => $ticket->status,
                    'status_badge' => Ticket::getStatusBadge($ticket->status),
                    'status_label' => Ticket::getStatuses()[$ticket->status] ?? $ticket->status,
                    'priority' => $ticket->priority,
                    'priority_badge' => Ticket::getPriorityBadge($ticket->priority),
                    'priority_label' => Ticket::getPriorities()[$ticket->priority] ?? $ticket->priority,
                    'supervisor_name' => $ticket->supervisor?->name ?? '-',
                    'technician_name' => $ticket->technician?->name ?? 'Unassigned',
                    'sla_deadline' => $ticket->sla_deadline?->format('d M Y H:i'),
                    'sla_remaining' => $ticket->sla_remaining,
                    'sla_breached' => $ticket->isSlaBreach(),
                    'created_at' => $ticket->created_at->format('d M Y H:i'),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Supervisor Ticket Datatable Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load tickets'], 500);
        }
    }

    public function create()
    {
        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $states = State::orderBy('name')->get();
        // Team technicians only
        $technicians = User::where('supervisor_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
        $jobTypes = JobType::where('status', 'active')->orderBy('name')->get();

        return view('supervisor.tickets.create', compact('vendors', 'states', 'technicians', 'jobTypes'));
    }

    public function store(StoreTicketRequest $request)
    {
        try {
            $data = $request->validated();
            $data['supervisor_id'] = auth()->id(); // Force to self
            $ticket = $this->ticketService->create($data);

            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_no} created successfully!",
                'redirect' => route('supervisor.tickets.show', $ticket->id),
            ]);
        } catch (\Exception $e) {
            Log::error('Supervisor Ticket Create Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create ticket.'], 500);
        }
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $ticket->load([
            'vendor', 'vendorBranch', 'state', 'city',
            'supervisor', 'technician', 'jobType', 'creator', 'updater',
            'comments.user', 'statusHistory.changedBy',
        ]);

        $allowedTransitions = Ticket::getAllowedTransitions($ticket->status);
        $statuses = Ticket::getStatuses();
        $technicians = User::where('supervisor_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('supervisor.tickets.show', compact('ticket', 'allowedTransitions', 'statuses', 'technicians'));
    }

    public function edit(Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        if (in_array($ticket->status, [Ticket::STATUS_COMPLETED, Ticket::STATUS_CLOSED])) {
            return redirect()->route('supervisor.tickets.show', $ticket->id)
                ->with('warning', 'Cannot edit a completed or closed ticket.');
        }

        $vendors = Vendor::where('status', 'active')->orderBy('vendor_name')->get();
        $states = State::orderBy('name')->get();
        $cities = $ticket->state_id ? City::where('state_id', $ticket->state_id)->orderBy('name')->get() : collect();
        $branches = $ticket->vendor_id ? VendorBranch::where('vendor_id', $ticket->vendor_id)->where('status', 'active')->get() : collect();
        $technicians = User::where('supervisor_id', auth()->id())->where('status', 'active')->orderBy('name')->get();
        $jobTypes = JobType::where('status', 'active')->orderBy('name')->get();

        return view('supervisor.tickets.edit', compact('ticket', 'vendors', 'states', 'cities', 'branches', 'technicians', 'jobTypes'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        try {
            $data = $request->validated();
            $data['supervisor_id'] = auth()->id();
            $ticket = $this->ticketService->update($ticket, $data);

            return response()->json([
                'success' => true,
                'message' => "Ticket {$ticket->ticket_no} updated successfully!",
                'redirect' => route('supervisor.tickets.show', $ticket->id),
            ]);
        } catch (\Exception $e) {
            Log::error('Supervisor Ticket Update Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update ticket.'], 500);
        }
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,rescheduled,completed,closed',
            'remarks' => 'nullable|string|max:1000',
            'reschedule_reason' => 'nullable|required_if:status,rescheduled|string|max:1000',
        ]);

        try {
            $this->ticketService->changeStatus($ticket, $request->status, $request->remarks, $request->reschedule_reason);
            return response()->json(['success' => true, 'message' => 'Ticket status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $request->validate([
            'technician_id' => 'required|exists:users,id',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // Ensure technician is in supervisor's team
        $teamIds = User::where('supervisor_id', auth()->id())->pluck('id')->toArray();
        if (!in_array($request->technician_id, $teamIds)) {
            return response()->json(['success' => false, 'message' => 'Technician is not in your team.'], 422);
        }

        try {
            $this->ticketService->assignTechnician($ticket, $request->technician_id, $request->remarks);
            return response()->json(['success' => true, 'message' => 'Technician assigned successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);
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
        $technicians = User::where('supervisor_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($technicians);
    }

    /**
     * Authorization helper - team scoped
     */
    protected function authorizeTicket(Ticket $ticket): void
    {
        $user = auth()->user();
        $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();

        if ($ticket->supervisor_id !== $user->id
            && !in_array($ticket->technician_id, $teamIds)
            && $ticket->created_by !== $user->id) {
            abort(403, 'You do not have access to this ticket.');
        }
    }
}
