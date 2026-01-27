<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayoutDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_line_id', 'job_order_id', 'job_type', 'job_date',
        'rate_card_id', 'commission_amount', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'job_date' => 'date',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function payoutLine() { return $this->belongsTo(PayoutLine::class); }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function rateCard() { return $this->belongsTo(RateCard::class); }
}
