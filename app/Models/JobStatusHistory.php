<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'job_order_id', 'from_status', 'to_status', 'changed_by', 'remarks', 'created_at',
    ];

    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
