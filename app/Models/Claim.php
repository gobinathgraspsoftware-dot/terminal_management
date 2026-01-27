<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'claim_no', 'claim_date', 'technician_id', 'claim_period_from', 'claim_period_to',
        'total_mileage_km', 'total_mileage_amount', 'total_allowance_amount', 'total_amount',
        'remarks', 'status', 'submitted_at', 'approved_at', 'approved_by', 'rejected_at',
        'rejected_by', 'rejection_reason', 'paid_at', 'paid_by', 'payout_batch_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'claim_period_from' => 'date',
            'claim_period_to' => 'date',
            'total_mileage_km' => 'decimal:2',
            'total_mileage_amount' => 'decimal:2',
            'total_allowance_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function payoutBatch() { return $this->belongsTo(PayoutBatch::class); }
    public function lines() { return $this->hasMany(ClaimLine::class); }
    public function attachments() { return $this->hasMany(ClaimAttachment::class); }
    public function approvals() { return $this->hasMany(ClaimApproval::class); }
}
