<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_CUSTOMER = 'customer';
    const TYPE_VENDOR = 'vendor';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SENT = 'sent';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'quotation_no', 'quotation_date', 'quotation_type', 'client_id', 'vendor_id',
        'reference', 'valid_until', 'subtotal', 'tax_amount', 'discount_amount',
        'total_amount', 'currency', 'terms_conditions', 'notes', 'status',
        'sent_at', 'approved_at', 'approved_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function lines() { return $this->hasMany(QuotationLine::class); }
    public function purchaseOrder() { return $this->hasOne(PurchaseOrder::class); }
}
