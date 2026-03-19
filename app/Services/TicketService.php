<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\SupervisorJobPricing;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketProof;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketService
{
    /**
     * Server-side DataTable data
     */
    public function getDatatable(array $params, $user): array
    {
        $query = Ticket::with([
            'vendor', 'vendorBranch', 'state', 'city',
            'supervisor', 'technician', 'jobCategory', 'jobType', 'creator',
        ])->visibleTo($user);

        if (!empty($params['status']))         $query->where('status', $params['status']);
        if (!empty($params['priority']))       $query->where('priority', $params['priority']);
        if (!empty($params['vendor_id']))      $query->where('vendor_id', $params['vendor_id']);
        if (!empty($params['supervisor_id']))  $query->where('supervisor_id', $params['supervisor_id']);
        if (!empty($params['job_category_id'])) $query->where('job_category_id', $params['job_category_id']);
        if (!empty($params['date_from']))      $query->whereDate('created_at', '>=', $params['date_from']);
        if (!empty($params['date_to']))        $query->whereDate('created_at', '<=', $params['date_to']);

        if (!empty($params['sla_breach']) && $params['sla_breach'] === 'yes') {
            $query->slaBreach();
        }

        $totalRecords = Ticket::visibleTo($user)->count();

        $search = $params['search']['value'] ?? '';
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_no', 'like', "%{$search}%")
                  ->orWhere('vendor_ticket_ref_no', 'like', "%{$search}%")
                  ->orWhere('merchant_name', 'like', "%{$search}%")
                  ->orWhere('terminal_id', 'like', "%{$search}%")
                  ->orWhere('router_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn($q2) => $q2->where('vendor_name', 'like', "%{$search}%"))
                  ->orWhereHas('supervisor', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('technician', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $filteredRecords = $query->count();

        $orderColumn = $params['order'][0]['column'] ?? 0;
        $orderDir = $params['order'][0]['dir'] ?? 'desc';
        $columns = ['ticket_no', 'vendor_id', 'merchant_name', 'status', 'priority', 'supervisor_id', 'technician_id', 'sla_deadline', 'created_at'];
        $sortBy = $columns[$orderColumn] ?? 'created_at';
        $query->orderBy($sortBy, $orderDir);

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
     * Create a new ticket with vendor-based ticket ID.
     * NOTE: SLA is NOT set at creation. It is calculated when technician ACCEPTS the ticket.
     */
    public function create(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $data['ticket_no'] = Ticket::generateVendorTicketNo($data['vendor_id']);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // NO SLA at creation — SLA starts when ticket is accepted

            // Lookup price from SupervisorJobPricing
            if (!empty($data['supervisor_id']) && !empty($data['job_category_id']) && !empty($data['job_type_id'])) {
                $pricing = SupervisorJobPricing::where('supervisor_id', $data['supervisor_id'])
                    ->where('job_category_id', $data['job_category_id'])
                    ->where('job_type_id', $data['job_type_id'])
                    ->first();
                $data['price'] = $pricing ? $pricing->price : 0;
            }

            // Pull mileage_rate from supervisor
            if (!empty($data['supervisor_id'])) {
                $supervisor = User::find($data['supervisor_id']);
                $data['mileage_rate'] = $supervisor?->mileage_rate ?? 0;
            }

            // Calculate claim
            $data['mileage_amount'] = ($data['mileage'] ?? 0) * ($data['mileage_rate'] ?? 0);
            $data['total_claim_amount'] = ($data['mileage_amount'] ?? 0) + ($data['toll'] ?? 0) + ($data['standby_meal'] ?? 0);

            // Auto-set status based on assignment
            $supervisor = !empty($data['supervisor_id']) ? User::find($data['supervisor_id']) : null;

            if (!empty($data['technician_id']) && $supervisor && $supervisor->isInternalSupervisor()) {
                $data['status'] = Ticket::STATUS_ASSIGNED;
                $data['assigned_at'] = now();
            } elseif ($supervisor && $supervisor->isExternalSupervisor()) {
                $data['technician_id'] = null;
                $data['status'] = Ticket::STATUS_ASSIGNED;
                $data['assigned_at'] = now();
            } else {
                $data['status'] = Ticket::STATUS_OPEN;
            }

            $ticket = Ticket::create($data);

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
     * Update ticket
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldStatus = $ticket->status;
            $data['updated_by'] = Auth::id();

            // Lookup price from SupervisorJobPricing
            if (!empty($data['supervisor_id']) && !empty($data['job_category_id']) && !empty($data['job_type_id'])) {
                $pricing = SupervisorJobPricing::where('supervisor_id', $data['supervisor_id'])
                    ->where('job_category_id', $data['job_category_id'])
                    ->where('job_type_id', $data['job_type_id'])
                    ->first();
                $data['price'] = $pricing ? $pricing->price : 0;
            }

            // Recalculate mileage if supervisor changed
            if (!empty($data['supervisor_id'])) {
                $supervisor = User::find($data['supervisor_id']);
                $data['mileage_rate'] = $supervisor?->mileage_rate ?? 0;
            }

            // Recalculate claim
            $data['mileage_amount'] = ($data['mileage'] ?? $ticket->mileage ?? 0) * ($data['mileage_rate'] ?? $ticket->mileage_rate ?? 0);
            $data['total_claim_amount'] = ($data['mileage_amount'] ?? 0) + ($data['toll'] ?? $ticket->toll ?? 0) + ($data['standby_meal'] ?? $ticket->standby_meal ?? 0);

            // Auto-assign status if technician first assigned
            if (!empty($data['technician_id']) && !$ticket->technician_id && $ticket->status === Ticket::STATUS_OPEN) {
                $data['status'] = Ticket::STATUS_ASSIGNED;
                $data['assigned_at'] = now();
            }

            $ticket->update($data);

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
     * Change ticket status with proof handling.
     * SLA is calculated HERE when status changes to ACCEPTED (24 hours from accept time).
     */
    public function changeStatus(Ticket $ticket, string $newStatus, ?string $remarks = null, ?string $rescheduleReason = null, array $proofFiles = []): Ticket
    {
        $user = Auth::user();

        // Determine allowed transitions based on role
        if ($user->hasRole('technician')) {
            $allowed = Ticket::getTechnicianTransitions($ticket->status);
        } elseif ($user->hasRole('supervisor') && $user->isExternalSupervisor()) {
            $allowed = Ticket::getExternalSupervisorTransitions($ticket->status);
        } else {
            $allowed = Ticket::getAllowedTransitions($ticket->status);
        }

        if (!in_array($newStatus, $allowed)) {
            throw new \Exception("Cannot transition from '{$ticket->status}' to '{$newStatus}'");
        }

        return DB::transaction(function () use ($ticket, $newStatus, $remarks, $rescheduleReason, $proofFiles) {
            $oldStatus = $ticket->status;
            $updateData = ['status' => $newStatus, 'updated_by' => Auth::id()];

            // ── ACCEPTED: Start SLA countdown (24 hours from now) ──
            if ($newStatus === Ticket::STATUS_ACCEPTED) {
                $updateData['accepted_at'] = now();
                $updateData['sla_hours'] = 24;
                $updateData['sla_deadline'] = now()->addHours(24);
                $updateData['sla_status'] = Ticket::SLA_ON_TRACK;
            }

            if ($newStatus === Ticket::STATUS_REJECTED) {
                $updateData['rejected_at'] = now();
                $updateData['technician_id'] = null;
                // Reset SLA since ticket goes back to pool
                $updateData['sla_hours'] = null;
                $updateData['sla_deadline'] = null;
                $updateData['sla_status'] = null;
            }

            if ($newStatus === Ticket::STATUS_IN_PROGRESS && !$ticket->started_at) {
                $updateData['started_at'] = now();
            }
            if (in_array($newStatus, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL])) {
                $updateData['completed_at'] = now();
            }
            if ($newStatus === Ticket::STATUS_CLOSED) {
                $updateData['closed_at'] = now();
            }
            if ($newStatus === Ticket::STATUS_SCHEDULED) {
                $updateData['rescheduled_at'] = now();
                $updateData['reschedule_reason'] = $rescheduleReason;
            }

            $ticket->update($updateData);

            // Log status history
            $history = TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => Auth::id(),
                'remarks' => $remarks,
                'reschedule_reason' => $rescheduleReason,
                'created_at' => now(),
            ]);

            // Upload proof files
            if (!empty($proofFiles)) {
                $this->uploadProofs($ticket, $history, $proofFiles);
            }

            // Auto-create Ticket Claim on completion
            if (in_array($newStatus, [Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL])) {
                $this->autoCreateTicketClaim($ticket);
            }

            return $ticket->fresh();
        });
    }

    /**
     * Auto-create a ticket claim when ticket is completed.
     * Claims are only for external supervisors.
     */
    protected function autoCreateTicketClaim(Ticket $ticket): void
    {
        try {
            $totalClaim = (float) ($ticket->total_claim_amount ?? 0);
            if ($totalClaim <= 0) return;

            if ($ticket->supervisor_id) {
                $supervisor = User::find($ticket->supervisor_id);
                if ($supervisor && $supervisor->isInternalSupervisor()) return;
            }

            $exists = Claim::ticketClaims()->where('ticket_id', $ticket->id)->exists();
            if ($exists) return;

            $claimService = app(ClaimManagementService::class);
            $claimService->createTicketClaim($ticket);
            Log::info("Auto-created ticket claim for Ticket #{$ticket->ticket_no}");
        } catch (\Exception $e) {
            Log::error("Failed to auto-create ticket claim for Ticket #{$ticket->ticket_no}: " . $e->getMessage());
        }
    }

    /**
     * Upload proof files
     */
    public function uploadProofs(Ticket $ticket, TicketStatusHistory $history, array $proofFiles): void
    {
        foreach ($proofFiles as $proofType => $files) {
            if (!is_array($files)) $files = [$files];

            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) continue;

                $fileName = $file->getClientOriginalName();
                $filePath = 'ticket-proofs/' . $ticket->id;
                $fileSize = $file->getSize() ?: 0;
                $mimeType = $file->getClientMimeType() ?: null;

                $destinationPath = $_SERVER['DOCUMENT_ROOT'] . '/storage/' . $filePath;
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $storedName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $file->move($destinationPath, $storedName);

                TicketProof::create([
                    'ticket_id' => $ticket->id,
                    'ticket_status_history_id' => $history->id,
                    'proof_type' => $proofType,
                    'file_name' => $fileName,
                    'file_path' => $filePath . '/' . $storedName,
                    'file_size' => $fileSize,
                    'mime_type' => $mimeType,
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Update claim fields on ticket
     */
    public function updateClaim(Ticket $ticket, array $data): Ticket
    {
        $mileageRate = $ticket->mileage_rate;
        if ($ticket->supervisor_id) {
            $supervisor = User::find($ticket->supervisor_id);
            $mileageRate = $supervisor?->mileage_rate ?? 0;
        }

        $mileage = $data['mileage'] ?? 0;
        $toll = $data['toll'] ?? 0;
        $standbyMeal = $data['standby_meal'] ?? 0;
        $mileageAmount = $mileage * $mileageRate;
        $totalClaim = $mileageAmount + $toll + $standbyMeal;

        $ticket->update([
            'mileage' => $mileage,
            'mileage_remarks' => $data['mileage_remarks'] ?? null,
            'mileage_rate' => $mileageRate,
            'mileage_amount' => $mileageAmount,
            'toll' => $toll,
            'standby_meal' => $standbyMeal,
            'total_claim_amount' => $totalClaim,
            'updated_by' => Auth::id(),
        ]);

        return $ticket->fresh();
    }

    /**
     * Assign technician
     */
    public function assignTechnician(Ticket $ticket, int $technicianId, ?string $remarks = null): Ticket
    {
        $supervisor = $ticket->supervisor_id ? User::find($ticket->supervisor_id) : null;
        if ($supervisor && $supervisor->isExternalSupervisor()) {
            throw new \Exception('External supervisors cannot assign technicians to tickets.');
        }

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
     * Reassign technician — resets SLA since new technician must accept again
     */
    public function reassignTechnician(Ticket $ticket, int $technicianId, ?string $remarks = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $technicianId, $remarks) {
            $oldTechId = $ticket->technician_id;
            $oldTech = $oldTechId ? User::find($oldTechId) : null;
            $newTech = User::find($technicianId);

            $ticket->update([
                'technician_id' => $technicianId,
                'status' => Ticket::STATUS_ASSIGNED,
                'assigned_at' => now(),
                'accepted_at' => null,
                // Reset SLA — new technician must accept, SLA restarts then
                'sla_hours' => null,
                'sla_deadline' => null,
                'sla_status' => null,
                'updated_by' => Auth::id(),
            ]);

            TicketStatusHistory::create([
                'ticket_id' => $ticket->id,
                'from_status' => $ticket->getOriginal('status'),
                'to_status' => Ticket::STATUS_ASSIGNED,
                'changed_by' => Auth::id(),
                'remarks' => $remarks ?? sprintf(
                    'Reassigned from %s to %s',
                    $oldTech?->name ?? 'Unassigned',
                    $newTech?->name ?? 'Unknown'
                ),
                'created_at' => now(),
            ]);

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
     * Get price for a supervisor + job_category + job_type combination
     */
    public function getPrice(int $supervisorId, int $jobCategoryId, int $jobTypeId): float
    {
        $pricing = SupervisorJobPricing::where('supervisor_id', $supervisorId)
            ->where('job_category_id', $jobCategoryId)
            ->where('job_type_id', $jobTypeId)
            ->first();

        return $pricing ? (float) $pricing->price : 0;
    }

    /**
     * Dashboard stats
     */
    public function getStats($user): array
    {
        $base = Ticket::visibleTo($user);

        return [
            'total'        => (clone $base)->count(),
            'open'         => (clone $base)->where('status', Ticket::STATUS_OPEN)->count(),
            'assigned'     => (clone $base)->where('status', Ticket::STATUS_ASSIGNED)->count(),
            'accepted'     => (clone $base)->where('status', Ticket::STATUS_ACCEPTED)->count(),
            'rejected'     => (clone $base)->where('status', Ticket::STATUS_REJECTED)->count(),
            'in_progress'  => (clone $base)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'scheduled'    => (clone $base)->where('status', Ticket::STATUS_SCHEDULED)->count(),
            'done_success' => (clone $base)->where('status', Ticket::STATUS_DONE_SUCCESS)->count(),
            'done_fail'    => (clone $base)->where('status', Ticket::STATUS_DONE_FAIL)->count(),
            'closed'       => (clone $base)->where('status', Ticket::STATUS_CLOSED)->count(),
            'sla_breached' => (clone $base)->slaBreach()->count(),
        ];
    }

    /**
     * SLA breached tickets
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
     * Update SLA statuses (called by scheduler)
     */
    public function updateSlaStatuses(): int
    {
        $count = 0;
        $activeTickets = Ticket::whereNotIn('status', [
            Ticket::STATUS_DONE_SUCCESS, Ticket::STATUS_DONE_FAIL,
            Ticket::STATUS_CLOSED, Ticket::STATUS_SCHEDULED, Ticket::STATUS_REJECTED,
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
}
