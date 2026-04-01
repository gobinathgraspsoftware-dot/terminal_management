<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\SupervisorJobPricing;
use App\Models\Ticket;
use App\Models\User;
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
        $user               = auth()->user();
        $stats              = $this->ticketService->getStats($user);
        $slaBreachedTickets = $this->ticketService->getSlaBreachedTickets($user);

        return view('technician.tickets.index', compact('stats', 'slaBreachedTickets'));
    }

    public function datatable(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);
        try {
            $user   = auth()->user();
            $result = $this->ticketService->getDatatable($request->all(), $user);

            $result['data'] = $result['data']->map(function ($ticket) {
                return [
                    'id'             => $ticket->id,
                    'ticket_no'      => $ticket->ticket_no,
                    'vendor_name'    => $ticket->vendor?->vendor_name ?? '-',
                    'merchant_name'  => $ticket->merchant_name ?? '-',
                    'tid'            => $ticket->tid ?? '-',
                    'job_category'   => $ticket->jobCategory?->category_name ?? '-',
                    'job_type'       => $ticket->jobType?->job_title ?? '-',
                    'price'          => number_format($ticket->price ?? 0, 2),
                    'status'         => $ticket->status,
                    'status_badge'   => Ticket::getStatusBadge($ticket->status),
                    'priority'       => $ticket->priority,
                    'priority_badge' => Ticket::getPriorityBadge($ticket->priority),
                    'sla_deadline'   => $ticket->sla_deadline?->format('d M Y H:i'),
                    'sla_remaining'  => $ticket->sla_remaining,
                    'sla_breached'   => $ticket->isSlaBreach(),
                    'total_claim'    => number_format($ticket->total_claim_amount ?? 0, 2),
                    'created_at'     => $ticket->created_at->format('d M Y H:i'),
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Technician Ticket Datatable Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load tickets'], 500);
        }
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load([
            'vendor', 'vendorBranch', 'state', 'city', 'jobCategory',
            'supervisor', 'technician', 'jobType', 'creator',
            'accessoryItem',
            'comments.user', 'statusHistory.changedBy', 'statusHistory.proofs', 'proofs',
        ]);

        $allowedTransitions = Ticket::getTechnicianTransitions($ticket->status);
        $statuses           = Ticket::getStatuses();
        $isReplacement      = $ticket->jobType && $ticket->jobType->isReplacement();

        // FIX #4: Supervisor pricing for grand total
        $supervisorPrice = null;
        if ($ticket->supervisor_id && $ticket->job_category_id && $ticket->job_type_id) {
            $supervisorPrice = SupervisorJobPricing::where('supervisor_id', $ticket->supervisor_id)
                ->where('job_category_id', $ticket->job_category_id)
                ->where('job_type_id', $ticket->job_type_id)
                ->value('price');
        }

        // CHANGE #3: Check if old router ID can be updated (In Progress only)
        $canUpdateOldRouterId = $ticket->canUpdateOldRouterId();

        return view('technician.tickets.show', compact(
            'ticket', 'allowedTransitions', 'statuses', 'isReplacement',
            'supervisorPrice', 'canUpdateOldRouterId'
        ));
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #3: Guard old_terminal_id — only update when In Progress.
     * The technician physically visits the site during In Progress,
     * so the old router ID can only be verified at that point.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function changeStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('changeStatus', $ticket);
        $request->validate([
            'status'            => 'required|in:accepted,rejected,in_progress,scheduled,done_success,done_fail',
            'remarks'           => 'nullable|string|max:1000',
            'reschedule_reason' => 'nullable|required_if:status,scheduled|string|max:1000',
            'scheduled_date'    => 'nullable|required_if:status,scheduled|date',
            'old_terminal_id'   => 'nullable|string|max:100',
            'proof_files.*'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        try {
            // CHANGE #3: Only update old_terminal_id when ticket is In Progress
            if ($request->filled('old_terminal_id') && $ticket->canUpdateOldRouterId()) {
                $ticket->update(['old_terminal_id' => $request->old_terminal_id]);
            }

            $proofFiles = [];
            if ($request->hasFile('proof_files')) {
                foreach ($request->file('proof_files') as $proofType => $file) {
                    $proofFiles[$proofType] = $file;
                }
            }

            $scheduledDate = $request->filled('scheduled_date')
                ? \Carbon\Carbon::parse($request->scheduled_date)
                : null;

            $this->ticketService->changeStatus(
                $ticket,
                $request->status,
                $request->remarks,
                $request->reschedule_reason,
                $proofFiles,
                $scheduledDate
            );

            // After rejection, technician_id becomes null — technician loses view access
            // Return redirect URL so the frontend navigates away from the show page
            if ($request->status === 'rejected') {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Ticket rejected successfully.',
                    'redirect' => route('technician.tickets.index'),
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Ticket status updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function updateClaim(Request $request, Ticket $ticket)
    {
        $this->authorize('updateClaim', $ticket);
        $request->validate([
            'mileage'         => 'nullable|numeric|min:0',
            'mileage_remarks' => 'nullable|string|max:500',
            'toll'            => 'nullable|numeric|min:0',
            'standby_meal'    => 'nullable|numeric|min:0',
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
                    'id'         => $comment->id,
                    'comment'    => $comment->comment,
                    'user_name'  => $comment->user->name,
                    'user_role'  => $comment->user->roles->first()?->name ?? 'user',
                    'created_at' => $comment->created_at->format('d M Y H:i'),
                    'is_own'     => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to add comment.'], 500);
        }
    }
}
