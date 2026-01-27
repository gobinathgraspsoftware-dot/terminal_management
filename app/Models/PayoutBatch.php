<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'batch_no', 'batch_date', 'period_from', 'period_to', 'description',
        'total_technicians', 'total_jobs', 'total_commission', 'total_claims',
        'total_deductions', 'total_payout', 'status', 'approved_at', 'approved_by',
        'paid_at', 'paid_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'batch_date' => 'date',
            'period_from' => 'date',
            'period_to' => 'date',
            'total_commission' => 'decimal:2',
            'total_claims' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_payout' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function lines() { return $this->hasMany(PayoutLine::class); }
}
