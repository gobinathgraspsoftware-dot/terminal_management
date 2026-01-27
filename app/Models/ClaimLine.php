<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimLine extends Model
{
    use HasFactory;

    const TYPE_MILEAGE = 'mileage';
    const TYPE_PARKING = 'parking';
    const TYPE_TOLL = 'toll';
    const TYPE_MEAL = 'meal';
    const TYPE_DAILY_ALLOWANCE = 'daily_allowance';
    const TYPE_OVERNIGHT = 'overnight';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'claim_id', 'line_no', 'claim_type', 'claim_date', 'job_order_id',
        'from_location', 'to_location', 'distance_km', 'rate_per_km',
        'description', 'amount', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'distance_km' => 'decimal:2',
            'rate_per_km' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function claim() { return $this->belongsTo(Claim::class); }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
}
