<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAssignment extends Model
{
    use HasFactory;

    const STATUS_ASSIGNED = 'assigned';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'job_order_id', 'technician_id', 'is_lead', 'assigned_at',
        'assigned_by', 'status', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_lead' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
}
