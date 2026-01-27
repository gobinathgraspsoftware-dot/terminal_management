<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpsTrack extends Model
{
    use HasFactory;

    const TYPE_TRAVEL = 'travel';
    const TYPE_JOB = 'job';

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'claim_line_id', 'job_order_id', 'technician_id', 'track_type', 'start_time',
        'end_time', 'start_latitude', 'start_longitude', 'end_latitude', 'end_longitude',
        'calculated_distance_km', 'route_data', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'start_latitude' => 'decimal:8',
            'start_longitude' => 'decimal:8',
            'end_latitude' => 'decimal:8',
            'end_longitude' => 'decimal:8',
            'calculated_distance_km' => 'decimal:2',
            'route_data' => 'array',
        ];
    }

    public function claimLine() { return $this->belongsTo(ClaimLine::class); }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}
