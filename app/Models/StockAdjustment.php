<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    const TYPE_COUNT = 'count';
    const TYPE_CORRECTION = 'correction';
    const TYPE_WRITE_OFF = 'write_off';
    const TYPE_OTHER = 'other';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'adjustment_no', 'adjustment_date', 'adjustment_type', 'depot_id', 'reason',
        'total_items', 'status', 'approved_at', 'approved_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function depot() { return $this->belongsTo(Depot::class); }
    public function lines() { return $this->hasMany(StockAdjustmentLine::class); }
}
