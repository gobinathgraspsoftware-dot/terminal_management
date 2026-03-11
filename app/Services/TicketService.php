<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;
use App\Models\NumberSeries;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * Get paginated tickets for DataTable (server-side)
     */
    public function getDatatable(array $params, $user): array
    {
        $query = Ticket::with(['vendor', 'vendorBranch', 'state', 'city', 'supervisor', 'technician', 'jobType', 'creator'])
            ->visibleTo($user);

        // Status filter
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Priority filter
        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        // SLA breach filter
        if (!empty($params['sla_breach']) && $params['sla_breach'] === 'yes') {
            $query->slaBreach();
        }

        // Vendor filter
        if (!empty($params['vendor_id'])) {
            $query->where('vendor_id', $params['vendor_id']);
        }

        // Supervisor filter
        if (!empty($params['supervisor_id'])) {
            $query->where('supervisor_id', $params['supervisor_id']);
        }

        // Date range
        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }
        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        // Search
        $totalRecords = Ticket::visibleTo($user)->count();
        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($q2) => $q2->where('vendor_name', 'like', "%{$search}%"))
                  ->orWhereHas('supervisor', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('technician', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumn = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $columns = ['ticket_no', 'vendor_id', 'status', 'priority', 'supervisor_id', 'technician_id', 'sla_deadline', 'created_at'];
        $sortBy = $columns[$orderColumn] ?? 'created_at';
        $query->orderBy($sortBy, $orderDir);

        // Pagination
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 25;
        $tickets = $query->skip($start)->take($length)->get();

        return [
            'draw' => intval($params['draw'] ?? 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $tickets,
        ];
    }

    /**
     * Create a new ticket
     */
    public function create(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            // Generate ticket number
            $data['ticket_no'] = $this->generateTicketNumber();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // SLA deadline
            $slaHours = $data['sla_hours'] ?? 24;
            $data['sla_hours'] = $slaHours;
            $data['sla_deadline'] = now()->addHours($slaHours);
            $data['sla_status'] = Ticket::SLA_ON_TRACK;

            // Auto-set status based on assignee
            if (!empty($data['technician_id'])) {
                $data['status'] = Ticket::STATUS_ASSIGNED;
                $data['assigned_at'] = now();
            } else {
                $data['status'] = Ticket::STATUS_OPEN;
            }

            $ticket = Ticket::create($data);

            // Log status history
            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => $ticket->status,
                'changed_by' => Auth::id(),
                'remarks' => 'Ticket created',
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Update a ticket
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldStatus = $ticket->status;
            $data['updated_by'] = Auth::id();

            // If technician assigned and was open, move to assigned
            if (!empty($data['technician_id']) && !$ticket->technician_id && $ticket->status === Ticket::STATUS_OPEN) {
                $data['status'] = Ticket::STATUS_ASSIGNED;
                $data['assigned_at'] = now();
            }

            $ticket->update($data);

            // Log status change if changed
            if ($ticket->status !== $oldStatus) {
                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'from_status' => $oldStatus,
                    'to_status' => $ticket->status,
                    'changed_by' => Auth::id(),
                    'remarks' => $data['status_remarks'] ?? null,
                    'created_at' => now(),
                ]);
            }

            return $ticket->fresh();
        });
    }

    /**
     * Change ticket status
     */
    public function changeStatus(Ticket $ticket, string $newStatus, ?string $remarks = null, ?string $rescheduleReason = null): Ticket
    {
        $allowed = Ticket::getAllowedTransitions($ticket->status);
        if (!in_array($newStatus, $allowed)) {
            throw new \Exception("Cannot transition from '{$ticket->status}' to '{$newStatus}'");
        }

        return DB::transaction(function () use ($ticket, $newStatus, $remarks, $rescheduleReason) {
            $oldStatus = $ticket->status;
            $updateData = ['status' => $newStatus, 'updated_by' => Auth::id()];

            if ($newStatus === Ticket::STATUS_IN_PROGRESS && !$ticket->started_at) {
                $updateData['started_at'] = now();
            }
            if ($newStatus === Ticket::STATUS_COMPLETED) {
                $updateData['completed_at'] = now();
            }
            if ($newStatus === Ticket::STATUS_CLOSED) {
                $updateData['closed_at'] = now();
            }
            if ($newStatus === Ticket::STATUS_RESCHEDULED) {
                $updateData['rescheduled_at'] = now();
                $updateData['reschedule_reason'] = $rescheduleReason;
            }

            $ticket->update($updateData);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => Auth::id(),
                'remarks' => $remarks,
                'created_at' => now(),
            ]);

            return $ticket->fresh();
        });
    }

    /**
     * Assign technician
     */
    public function assignTechnician(Ticket $ticket, int $technicianId, ?string $remarks = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $technicianId, $remarks) {
            $oldStatus = $ticket->status;
            $ticket->update([
                'technician_id' => $technicianId,
                'status' => Ticket::STATUS_ASSIGNED,
                'assigned_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            if ($oldStatus !== Ticket::STATUS_ASSIGNED) {
                TicketStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'from_status' => $oldStatus,
                    'to_status' => Ticket::STATUS_ASSIGNED,
                    'changed_by' => Auth::id(),
                    'remarks' => $remarks ?? 'Technician assigned',
                    'created_at' => now(),
                ]);
            }

            return $ticket->fresh();
        });
    }

    /**
     * Add comment
     */
    public function addComment(Ticket $ticket, string $comment): TicketComment
    {
        return TicketComment::create([
            'ticket_id' => $ticket->id,
            'comment' => $comment,
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function getStats($user): array
    {
        $base = Ticket::visibleTo($user);

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->where('status', Ticket::STATUS_OPEN)->count(),
            'assigned' => (clone $base)->where('status', Ticket::STATUS_ASSIGNED)->count(),
            'in_progress' => (clone $base)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'rescheduled' => (clone $base)->where('status', Ticket::STATUS_RESCHEDULED)->count(),
            'completed' => (clone $base)->where('status', Ticket::STATUS_COMPLETED)->count(),
            'closed' => (clone $base)->where('status', Ticket::STATUS_CLOSED)->count(),
            'sla_breached' => (clone $base)->slaBreach()->count(),
        ];
    }

    /**
     * Get SLA breached tickets for reminders
     */
    public function getSlaBreachedTickets($user)
    {
        return Ticket::with(['vendor', 'supervisor', 'technician'])
            ->visibleTo($user)
            ->slaBreach()
            ->orderBy('sla_deadline', 'asc')
            ->get();
    }

    /**
     * Update SLA status for all active tickets
     */
    public function updateSlaStatuses(): int
    {
        $count = 0;
        $activeTickets = Ticket::whereNotIn('status', [
            Ticket::STATUS_COMPLETED,
            Ticket::STATUS_CLOSED,
            Ticket::STATUS_RESCHEDULED,
        ])->whereNotNull('sla_deadline')->get();

        foreach ($activeTickets as $ticket) {
            $hoursLeft = now()->diffInHours($ticket->sla_deadline, false);
            $newStatus = match (true) {
                $hoursLeft < 0 => Ticket::SLA_BREACHED,
                $hoursLeft <= 4 => Ticket::SLA_AT_RISK,
                default => Ticket::SLA_ON_TRACK,
            };

            if ($ticket->sla_status !== $newStatus) {
                $ticket->update(['sla_status' => $newStatus]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Generate ticket number
     */
    protected function generateTicketNumber(): string
    {
        try {
            return NumberSeries::getNextNumber('ticket');
        } catch (\Exception $e) {
            // Fallback: TKT-YYYYMMDD-XXXX
            $today = now()->format('Ymd');
            $count = Ticket::whereDate('created_at', today())->count() + 1;
            return 'TKT-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        }
    }
}
