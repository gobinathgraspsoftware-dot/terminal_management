<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESCHEDULED = 'rescheduled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CLOSED = 'closed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const SLA_ON_TRACK = 'on_track';
    const SLA_AT_RISK = 'at_risk';
    const SLA_BREACHED = 'breached';

    protected $fillable = [
        'ticket_no', 'vendor_id', 'vendor_branch_id', 'state_id', 'city_id',
        'supervisor_id', 'technician_id', 'job_type_id', 'status', 'priority',
        'description', 'sla_hours', 'sla_deadline', 'sla_status',
        'assigned_at', 'started_at', 'completed_at', 'closed_at',
        'rescheduled_at', 'reschedule_reason',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sla_deadline' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'closed_at' => 'datetime',
            'rescheduled_at' => 'datetime',
        ];
    }

    /* ---- Relationships ---- */
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function vendorBranch() { return $this->belongsTo(VendorBranch::class); }
    public function state() { return $this->belongsTo(State::class); }
    public function city() { return $this->belongsTo(City::class); }
    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function jobType() { return $this->belongsTo(JobType::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function comments() { return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc'); }
    public function statusHistory() { return $this->hasMany(TicketStatusHistory::class)->orderBy('created_at', 'desc'); }

    /* ---- Scopes ---- */
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
        // technician
        return $query->where(function ($q) use ($user) {
            $q->where('technician_id', $user->id)
              ->orWhere('created_by', $user->id);
        });
    }

    public function scopeByStatus($query, $status) { return $query->where('status', $status); }
    public function scopeSlaBreach($query)
    {
        return $query->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CLOSED, self::STATUS_RESCHEDULED]);
    }

    /* ---- Helpers ---- */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESCHEDULED => 'Rescheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function getStatusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'primary',
            self::STATUS_ASSIGNED => 'info',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_RESCHEDULED => 'secondary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CLOSED => 'dark',
            default => 'light',
        };
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function getPriorityBadge(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'secondary',
            self::PRIORITY_NORMAL => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
            default => 'light',
        };
    }

    public function isSlaBreach(): bool
    {
        if (!$this->sla_deadline) return false;
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CLOSED, self::STATUS_RESCHEDULED])) return false;
        return now()->greaterThan($this->sla_deadline);
    }

    public function getSlaRemainingAttribute(): ?string
    {
        if (!$this->sla_deadline) return null;
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CLOSED])) return 'Done';
        if ($this->status === self::STATUS_RESCHEDULED) return 'Paused';
        $diff = now()->diff($this->sla_deadline);
        $prefix = now()->greaterThan($this->sla_deadline) ? '-' : '';
        return $prefix . $diff->format('%dd %hh %im');
    }

    /**
     * Allowed transitions per status
     */
    public static function getAllowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::STATUS_OPEN => [self::STATUS_ASSIGNED, self::STATUS_CLOSED],
            self::STATUS_ASSIGNED => [self::STATUS_IN_PROGRESS, self::STATUS_RESCHEDULED, self::STATUS_CLOSED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_RESCHEDULED, self::STATUS_CLOSED],
            self::STATUS_RESCHEDULED => [self::STATUS_OPEN, self::STATUS_ASSIGNED, self::STATUS_CLOSED],
            self::STATUS_COMPLETED => [self::STATUS_CLOSED],
            self::STATUS_CLOSED => [],
            default => [],
        };
    }
}
