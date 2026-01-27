<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSignature extends Model
{
    use HasFactory;

    const TYPE_CUSTOMER = 'customer';
    const TYPE_TECHNICIAN = 'technician';

    protected $fillable = [
        'job_order_id', 'signature_type', 'signatory_name', 'signatory_title',
        'signatory_ic', 'signature_data', 'signed_at', 'ip_address',
    ];

    protected function casts(): array { return ['signed_at' => 'datetime']; }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
}
