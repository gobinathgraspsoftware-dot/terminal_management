<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id', 'checklist_item', 'is_checked', 'checked_at',
        'checked_by', 'remarks', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_checked' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function checkedBy() { return $this->belongsTo(User::class, 'checked_by'); }
}
