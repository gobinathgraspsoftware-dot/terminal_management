<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    // ── Status constants ──
    const STATUS_OPEN         = 'open';
    const STATUS_ASSIGNED     = 'assigned';
    const STATUS_ACCEPTED     = 'accepted';
    const STATUS_REJECTED     = 'rejected';
    const STATUS_IN_PROGRESS  = 'in_progress';
    const STATUS_SCHEDULED    = 'scheduled';
    const STATUS_DONE_SUCCESS = 'done_success';
    const STATUS_DONE_FAIL    = 'done_fail';
    const STATUS_CLOSED       = 'closed';

    // ── Priority constants ──
    const PRIORITY_LOW    = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH   = 'high';
    const PRIORITY_URGENT = 'urgent';

    // ── SLA constants ──
    const SLA_ON_TRACK = 'on_track';
    const SLA_AT_RISK  = 'at_risk';
    const SLA_BREACHED = 'breached';

    protected $fillable = [
        'ticket_no', 'vendor_ticket_ref_no',
        'vendor_id', 'vendor_branch_id', 'state_id', 'city_id',
        'tid', 'terminal_id', 'router_id', 'router_ids', 'old_terminal_id', 'old_router_ids',
        'serial_number', // ← NEW: Optional serial number for reference
        // Accessory inventory fields
        'accessory_type_selected', 'accessory_item_id', 'accessory_qty',
        'merchant_name', 'merchant_address', 'contact_number',
        'supervisor_id', 'technician_id',
        'job_category_id', 'job_type_id', 'price',
        'priority', 'description',
        'expected_start_date', 'expected_end_date',
        'sla_hours', 'sla_deadline', 'sla_status',
        'status',
        'started_at', 'completed_at', 'closed_at',
        'assigned_at', 'accepted_at', 'rejected_at',
        'rescheduled_at', 'reschedule_reason',
        'scheduled_date',      // FIX #3: target reschedule date/time
        // Claim fields
        'mileage', 'mileage_remarks', 'mileage_rate', 'mileage_amount',
        'toll', 'standby_meal', 'total_claim_amount',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sla_deadline'        => 'datetime',
            'started_at'          => 'datetime',
            'completed_at'        => 'datetime',
            'closed_at'           => 'datetime',
            'assigned_at'         => 'datetime',
            'accepted_at'         => 'datetime',
            'rejected_at'         => 'datetime',
            'rescheduled_at'      => 'datetime',
            'scheduled_date'      => 'datetime',   // FIX #3
            'expected_start_date' => 'date',
            'expected_end_date'   => 'date',
            'mileage'             => 'decimal:2',
            'mileage_rate'        => 'decimal:2',
            'mileage_amount'      => 'decimal:2',
            'toll'                => 'decimal:2',
            'standby_meal'        => 'decimal:2',
            'total_claim_amount'  => 'decimal:2',
            'price'               => 'decimal:2',
            'sla_hours'           => 'integer',
            'accessory_qty'       => 'integer',
            'router_ids'          => 'array',
            'old_router_ids'      => 'array',
        ];
    }

    // ══════════════════════════════════════
    // Relationships
    // ══════════════════════════════════════

    public function vendor()       { return $this->belongsTo(Vendor::class); }
    public function vendorBranch() { return $this->belongsTo(VendorBranch::class); }
    public function state()        { return $this->belongsTo(State::class); }
    public function city()         { return $this->belongsTo(City::class); }
    public function supervisor()   { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function technician()   { return $this->belongsTo(User::class, 'technician_id'); }
    public function jobCategory()  { return $this->belongsTo(JobCategory::class); }
    public function jobType()      { return $this->belongsTo(JobType::class); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()      { return $this->belongsTo(User::class, 'updated_by'); }

    public function accessoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'accessory_item_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'desc');
    }

    public function statusHistory()
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function proofs()
    {
        return $this->hasMany(TicketProof::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'ticket_id');
    }

    // ══════════════════════════════════════
    // Scopes
    // ══════════════════════════════════════

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }
        if ($user->hasRole('supervisor')) {
            $teamIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            return $query->where(function ($q) use ($user, $teamIds) {
                $q->where('supervisor_id', $user->id)
                  ->orWhereIn('technician_id', $teamIds)
                  ->orWhere('created_by', $user->id);
            });
        }
        return $query->where('technician_id', $user->id);
    }

    public function scopeSlaBreach($query)
    {
        return $query->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->whereNotIn('status', [
                self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL,
                self::STATUS_CLOSED, self::STATUS_SCHEDULED, self::STATUS_REJECTED,
            ]);
    }

    // ══════════════════════════════════════
    // Static helpers
    // ══════════════════════════════════════

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN         => 'Open',
            self::STATUS_ASSIGNED     => 'Assigned',
            self::STATUS_ACCEPTED     => 'Accepted',
            self::STATUS_REJECTED     => 'Rejected',
            self::STATUS_IN_PROGRESS  => 'In Progress',
            self::STATUS_SCHEDULED    => 'Scheduled',
            self::STATUS_DONE_SUCCESS => 'Done / Success',
            self::STATUS_DONE_FAIL    => 'Done / Fail',
            self::STATUS_CLOSED       => 'Closed',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW    => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH   => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #1: Admin transitions — REMOVED accept/reject
     * Admin should NOT accept/reject tickets. Only Close or Reassign.
     * Accept/Reject is exclusively for Technician & External Supervisor.
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_OPEN         => [self::STATUS_ASSIGNED, self::STATUS_CLOSED],
            self::STATUS_ASSIGNED     => [self::STATUS_CLOSED],
            self::STATUS_ACCEPTED     => [self::STATUS_IN_PROGRESS, self::STATUS_SCHEDULED, self::STATUS_CLOSED],
            self::STATUS_REJECTED     => [self::STATUS_OPEN, self::STATUS_ASSIGNED, self::STATUS_CLOSED],
            self::STATUS_IN_PROGRESS  => [self::STATUS_SCHEDULED, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_SCHEDULED    => [self::STATUS_IN_PROGRESS, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_DONE_SUCCESS => [self::STATUS_CLOSED],
            self::STATUS_DONE_FAIL    => [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS],
            self::STATUS_CLOSED       => [],
            default                   => [],
        };
    }

    public static function getTechnicianTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_ASSIGNED    => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED    => [self::STATUS_IN_PROGRESS],
            self::STATUS_IN_PROGRESS => [self::STATUS_SCHEDULED, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_SCHEDULED   => [self::STATUS_IN_PROGRESS, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            default                  => [],
        };
    }

    public static function getExternalSupervisorTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_ASSIGNED    => [self::STATUS_ACCEPTED, self::STATUS_REJECTED],
            self::STATUS_ACCEPTED    => [self::STATUS_IN_PROGRESS, self::STATUS_SCHEDULED],
            self::STATUS_IN_PROGRESS => [self::STATUS_SCHEDULED, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_SCHEDULED   => [self::STATUS_IN_PROGRESS, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_DONE_SUCCESS => [self::STATUS_CLOSED],
            self::STATUS_DONE_FAIL    => [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS],
            default                  => [],
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #1: Internal supervisor transitions — REMOVED accept/reject
     * Internal supervisors do NOT accept/reject tickets.
     * They can only manage workflow from Accepted onwards + reassign techs.
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function getInternalSupervisorTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_ASSIGNED     => [],
            self::STATUS_ACCEPTED     => [self::STATUS_IN_PROGRESS, self::STATUS_SCHEDULED, self::STATUS_CLOSED],
            self::STATUS_IN_PROGRESS  => [self::STATUS_SCHEDULED, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_SCHEDULED    => [self::STATUS_IN_PROGRESS, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL],
            self::STATUS_DONE_SUCCESS => [self::STATUS_CLOSED],
            self::STATUS_DONE_FAIL    => [self::STATUS_CLOSED, self::STATUS_IN_PROGRESS],
            default                   => [],
        };
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #2: Check if ticket allows supervisor reassignment.
     * Blocked once status reaches 'accepted' or beyond.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function canReassignSupervisor(): bool
    {
        return in_array($this->status, [
            self::STATUS_OPEN,
            self::STATUS_ASSIGNED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * CHANGE #3: Check if Old Router ID can be updated.
     * Only allowed when ticket is at In Progress status.
     * ═══════════════════════════════════════════════════════════════════
     */
    public function canUpdateOldRouterId(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public static function getStatusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN         => '<span class="badge bg-secondary">Open</span>',
            self::STATUS_ASSIGNED     => '<span class="badge bg-info">Assigned</span>',
            self::STATUS_ACCEPTED     => '<span class="badge bg-primary">Accepted</span>',
            self::STATUS_REJECTED     => '<span class="badge bg-danger">Rejected</span>',
            self::STATUS_IN_PROGRESS  => '<span class="badge bg-primary">In Progress</span>',
            self::STATUS_SCHEDULED    => '<span class="badge bg-warning text-dark">Scheduled</span>',
            self::STATUS_DONE_SUCCESS => '<span class="badge bg-success">Done / Success</span>',
            self::STATUS_DONE_FAIL    => '<span class="badge bg-danger">Done / Fail</span>',
            self::STATUS_CLOSED       => '<span class="badge bg-dark">Closed</span>',
            default                   => '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>',
        };
    }

    public static function getPriorityBadge(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW    => '<span class="badge bg-secondary">Low</span>',
            self::PRIORITY_NORMAL => '<span class="badge bg-info">Normal</span>',
            self::PRIORITY_HIGH   => '<span class="badge bg-warning text-dark">High</span>',
            self::PRIORITY_URGENT => '<span class="badge bg-danger">Urgent</span>',
            default               => '<span class="badge bg-light text-dark">' . ucfirst($priority) . '</span>',
        };
    }

    // ══════════════════════════════════════
    // Vendor-based ticket ID generation
    // ══════════════════════════════════════

    public static function generateVendorTicketNo(int $vendorId): string
    {
        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            throw new \Exception('Vendor not found for ticket ID generation.');
        }

        $prefix = strtoupper($vendor->vendor_code);

        $lastTicket = static::withTrashed()
            ->where('ticket_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(ticket_no, ?) AS UNSIGNED) DESC', [strlen($prefix) + 1])
            ->first();

        $nextNum = 1;
        if ($lastTicket) {
            $numPart = substr($lastTicket->ticket_no, strlen($prefix));
            $nextNum = ((int) $numPart) + 1;
        }

        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    // ══════════════════════════════════════
    // Accessors
    // ══════════════════════════════════════

    public function isSlaBreach(): bool
    {
        if (!$this->sla_deadline) return false;
        if (in_array($this->status, [
            self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL,
            self::STATUS_CLOSED, self::STATUS_SCHEDULED, self::STATUS_REJECTED,
        ])) {
            return false;
        }
        return now()->gt($this->sla_deadline);
    }

    public function getSlaRemainingAttribute(): ?string
    {
        if (!$this->sla_deadline) return null;
        if (in_array($this->status, [self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL, self::STATUS_CLOSED])) {
            return 'Completed';
        }
        if ($this->status === self::STATUS_SCHEDULED) return 'Rescheduled';
        if ($this->status === self::STATUS_REJECTED)  return 'Rejected';

        if (now()->gt($this->sla_deadline)) {
            $diff = now()->diff($this->sla_deadline);
            return '-' . $diff->h . 'h ' . $diff->i . 'm (Breached)';
        }
        $diff  = now()->diff($this->sla_deadline);
        $hours = ($diff->days * 24) + $diff->h;
        return $hours . 'h ' . $diff->i . 'm';
    }

    /**
     * FIX #4: Grand total = job price + claim amount.
     */
    public function getGrandTotalAttribute(): float
    {
        return (float) ($this->price ?? 0) + (float) ($this->total_claim_amount ?? 0);
    }

    public function calculateClaim(): void
    {
        $this->mileage_amount     = ($this->mileage ?? 0) * ($this->mileage_rate ?? 0);
        $this->total_claim_amount = $this->mileage_amount + ($this->toll ?? 0) + ($this->standby_meal ?? 0);
    }

    public function isClaimApplicable(): bool
    {
        if (!$this->supervisor_id) return false;
        $supervisor = $this->supervisor ?? User::find($this->supervisor_id);
        if (!$supervisor) return false;
        return $supervisor->isExternalSupervisor();
    }

    /**
     * ═══════════════════════════════════════════════════════════════════
     * Check if the ticket's claim amount can still be edited.
     * Blocked when:
     *   1. Ticket is completed (done_success / done_fail / closed)
     *   2. Linked claim has been verified / approved / paid / non-claimable
     * ═══════════════════════════════════════════════════════════════════
     */
    public function isClaimEditable(): bool
    {
        // Block if ticket is completed or closed — no role can edit
        if (in_array($this->status, [
            self::STATUS_DONE_SUCCESS,
            self::STATUS_DONE_FAIL,
            self::STATUS_CLOSED,
        ])) {
            return false;
        }

        $claim = \App\Models\Claim::where('claim_category', \App\Models\Claim::CATEGORY_TICKET)
            ->where('ticket_id', $this->id)
            ->first();

        // No claim exists yet — allow editing (claim will be created on completion)
        if (!$claim) return true;

        // Delegate to Claim model's own isEditable() — true only for draft/submitted
        return $claim->isEditable();
    }

    public static function statusRequiresProof(string $status): bool
    {
        return in_array($status, [self::STATUS_SCHEDULED, self::STATUS_DONE_SUCCESS, self::STATUS_DONE_FAIL]);
    }

    public static function getRequiredProofTypes(string $status): array
    {
        return match ($status) {
            self::STATUS_SCHEDULED    => ['whatsapp_screenshot', 'call_log_screenshot'],
            self::STATUS_DONE_SUCCESS => ['test_slip'],
            self::STATUS_DONE_FAIL    => ['service_form'],
            default                   => [],
        };
    }

    public static function getProofTypeLabels(): array
    {
        return [
            'whatsapp_screenshot' => 'WhatsApp Screenshot',
            'call_log_screenshot' => 'Call Log Screenshot',
            'test_slip'           => 'Test Slip Image',
            'service_form'        => 'Service Form Image',
            'other'               => 'Other',
        ];
    }

    // ══════════════════════════════════════
    // Inventory Integration Helpers
    // ══════════════════════════════════════

    public function getRouterIdsDisplay(): string
    {
        $ids = $this->router_ids;
        if (empty($ids)) return '-';
        if (is_string($ids)) $ids = json_decode($ids, true);
        return is_array($ids) ? implode(', ', $ids) : '-';
    }

    public function getOldRouterIdsDisplay(): string
    {
        $ids = $this->old_router_ids;
        if (empty($ids)) return '-';
        if (is_string($ids)) $ids = json_decode($ids, true);
        return is_array($ids) ? implode(', ', $ids) : '-';
    }

    public function getAccessoryTypeLabel(): string
    {
        return match ($this->accessory_type_selected) {
            'sim_card' => 'SIM Card',
            'antenna'  => 'Antenna',
            default    => '-',
        };
    }

    public function isInstallationJob(): bool
    {
        $jobType = $this->jobType;
        if (!$jobType) return false;
        return str_contains(strtolower($jobType->slug ?? ''), 'installation')
            || str_contains(strtolower($jobType->job_title ?? ''), 'installation');
    }

    public function isReplacementJob(): bool
    {
        $jobType = $this->jobType;
        if (!$jobType) return false;
        return str_contains(strtolower($jobType->slug ?? ''), 'replacement')
            || str_contains(strtolower($jobType->job_title ?? ''), 'replacement');
    }
}
