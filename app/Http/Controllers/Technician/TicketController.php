<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
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
        $slaBreachedTickets = $this->ticketService->getSlaBreachedTickets($user);

        return view('technician.tickets.index', compact('stats', 'slaBreachedTickets'));
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
                    'job_type' => $ticket->jobType?->name ?? '-',
                    'status' => $ticket->status,
                    'status_badge' => Ticket::getStatusBadge($ticket->status),
                    'status_label' => Ticket::getStatuses()[$ticket->status] ?? $ticket->status,
                    'priority' => $ticket->priority,
                    'priority_badge' => Ticket::getPriorityBadge($ticket->priority),
                    'priority_label' => Ticket::getPriorities()[$ticket->priority] ?? $ticket->priority,
                    'supervisor_name' => $ticket->supervisor?->name ?? '-',
                    'sla_deadline' => $ticket->sla_deadline?->format('d M Y H:i'),
                    'sla_remaining' => $ticket->sla_remaining,
                    'sla_breached' => $ticket->isSlaBreach(),
                    'created_at' => $ticket->created_at->format('d M Y H:i'),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Tech Ticket Datatable Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load tickets'], 500);
        }
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $ticket->load([
            'vendor', 'vendorBranch', 'state', 'city',
            'supervisor', 'technician', 'jobType', 'creator',
            'comments.user', 'statusHistory.changedBy',
        ]);

        // Technician can only: start (assigned→in_progress), complete (in_progress→completed)
        $allowedTransitions = [];
        if ($ticket->technician_id === auth()->id()) {
            $all = Ticket::getAllowedTransitions($ticket->status);
            $techAllowed = [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_COMPLETED];
            $allowedTransitions = array_intersect($all, $techAllowed);
        }

        $statuses = Ticket::getStatuses();

        return view('technician.tickets.show', compact('ticket', 'allowedTransitions', 'statuses'));
    }

    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorizeTicket($ticket);

        $request->validate([
            'status' => 'required|in:in_progress,completed',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $this->ticketService->changeStatus($ticket, $request->status, $request->remarks);
            return response()->json(['success' => true, 'message' => 'Ticket status updated successfully.']);
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

    protected function authorizeTicket(Ticket $ticket): void
    {
        $user = auth()->user();
        if ($ticket->technician_id !== $user->id && $ticket->created_by !== $user->id) {
            abort(403, 'You do not have access to this ticket.');
        }
    }
}
