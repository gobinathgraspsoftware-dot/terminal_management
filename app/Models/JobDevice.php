<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDevice extends Model
{
    use HasFactory;

    const ACTION_INSTALLED = 'installed';
    const ACTION_REPLACED = 'replaced';
    const ACTION_REMOVED = 'removed';
    const ACTION_REPAIRED = 'repaired';
    const ACTION_COLLECTED = 'collected';

    const CONDITION_NEW = 'new';
    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_DEFECTIVE = 'defective';

    protected $fillable = [
        'job_order_id', 'action_type', 'serial_id', 'serial_no', 'model_id',
        'old_serial_id', 'old_serial_no', 'replacement_reason', 'condition',
        'wasted', 'wastage_reason', 'accessories', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'wasted' => 'boolean',
            'accessories' => 'array',
        ];
    }

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function oldSerial() { return $this->belongsTo(InventorySerial::class, 'old_serial_id'); }
}
