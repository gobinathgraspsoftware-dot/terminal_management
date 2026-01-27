<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobNote extends Model
{
    use HasFactory;

    const TYPE_INTERNAL = 'internal';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_TECHNICIAN = 'technician';

    protected $fillable = [
        'job_order_id', 'note_type', 'note', 'created_by',
    ];

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
