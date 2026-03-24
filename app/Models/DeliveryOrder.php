<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_INSTALLATION = 'installation';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_COLLECTION = 'collection';
    const TYPE_REPLACEMENT = 'replacement';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_SIGNATURE = 'pending_signature';
    const STATUS_SIGNED = 'signed';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'do_no', 'do_date', 'do_type', 'job_order_id', 'client_id', 'site_id',
        'delivery_address', 'technician_id', 'total_items', 'remarks', 'customer_name',
        'customer_ic', 'signature_data', 'signed_at', 'status', 'posted_at',
        'posted_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'do_date' => 'date',
            'signed_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    // Removed: client(), site() — tables dropped
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function lines() { return $this->hasMany(DeliveryOrderLine::class); }
}
