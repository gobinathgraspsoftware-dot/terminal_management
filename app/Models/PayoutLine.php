<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutLine extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payout_batch_id', 'technician_id', 'job_count', 'commission_amount',
        'claims_amount', 'deductions_amount', 'net_payout', 'bank_name',
        'bank_account_no', 'bank_account_name', 'payment_reference',
        'payment_date', 'status', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'claims_amount' => 'decimal:2',
            'deductions_amount' => 'decimal:2',
            'net_payout' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function payoutBatch() { return $this->belongsTo(PayoutBatch::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function details() { return $this->hasMany(PayoutDetail::class); }
}
