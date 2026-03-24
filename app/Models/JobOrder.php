<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrder extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_INSTALLATION = 'installation';
    const TYPE_SERVICE = 'service';
    const TYPE_REPAIR = 'repair';
    const TYPE_REPLACEMENT = 'replacement';
    const TYPE_TROUBLESHOOT = 'troubleshoot';
    const TYPE_COLLECTION = 'collection';
    const TYPE_OTHER = 'other';

    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_PENDING_ASSIGNMENT = 'pending_assignment';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_CONFIRMATION = 'pending_confirmation';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    const SLA_ON_TRACK = 'on_track';
    const SLA_AT_RISK = 'at_risk';
    const SLA_BREACHED = 'breached';

    protected $fillable = [
        'job_no', 'external_job_id', 'job_date', 'job_type', 'partner_id', 'client_id',
        'site_id', 'priority', 'sla_hours', 'sla_deadline', 'sla_status', 'supervisor_id',
        'technician_id', 'scheduled_date', 'scheduled_time', 'terminal_count', 'instructions',
        'findings', 'resolution', 'no_issue_found', 'started_at', 'completed_at', 'status',
        'failure_reason', 'rejection_reason', 'cancellation_reason', 'billable', 'billed',
        'invoice_id', 'commission_amount', 'commission_rate_card_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'job_date' => 'date',
            'scheduled_date' => 'date',
            'sla_deadline' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'no_issue_found' => 'boolean',
            'billable' => 'boolean',
            'billed' => 'boolean',
            'commission_amount' => 'decimal:2',
        ];
    }

    // Removed: partner(), client(), site(), rateCard() — tables dropped

    public function supervisor() { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }

    public function assignments() { return $this->hasMany(JobAssignment::class); }
    public function devices() { return $this->hasMany(JobDevice::class); }
    public function photos() { return $this->hasMany(JobPhoto::class); }
    public function notes() { return $this->hasMany(JobNote::class); }
    public function signatures() { return $this->hasMany(JobSignature::class); }
    public function statusHistory() { return $this->hasMany(JobStatusHistory::class); }
    public function checklists() { return $this->hasMany(JobChecklist::class); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class); }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('supervisor')) {
            $teamTechIds = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            return $query->where(function($q) use ($user, $teamTechIds) {
                $q->where('supervisor_id', $user->id)
                  ->orWhereIn('technician_id', $teamTechIds);
            });
        }

        return $query->where('technician_id', $user->id);
    }

    public function scopeByStatus($query, $status) { return $query->where('status', $status); }
    public function scopeByType($query, $type) { return $query->where('job_type', $type); }
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('sla_deadline')
            ->where('sla_deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }
    public function scopeTodayJobs($query) { return $query->whereDate('job_date', today()); }
}
