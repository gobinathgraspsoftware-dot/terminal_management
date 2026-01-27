<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateCard extends Model
{
    use HasFactory;

    const JOB_TYPE_ALL = 'all';
    const JOB_TYPE_INSTALLATION = 'installation';
    const JOB_TYPE_SERVICE = 'service';
    const JOB_TYPE_REPAIR = 'repair';
    const JOB_TYPE_REPLACEMENT = 'replacement';
    const JOB_TYPE_COLLECTION = 'collection';

    const CALC_TYPE_FLAT = 'flat';
    const CALC_TYPE_PER_TERMINAL = 'per_terminal';
    const CALC_TYPE_PERCENTAGE = 'percentage';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'rate_card_code', 'rate_card_name', 'description', 'job_type',
        'model_id', 'state', 'calculation_type', 'rate_amount',
        'min_amount', 'max_amount', 'effective_from', 'effective_to', 'status',
    ];

    protected function casts(): array
    {
        return [
            'rate_amount' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeEffective($query, $date = null)
    {
        $date = $date ?? now();
        return $query->where('effective_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            });
    }
}
