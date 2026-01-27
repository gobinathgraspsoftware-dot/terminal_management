<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SENT = 'sent';
    const STATUS_OPEN = 'open';
    const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    const STATUS_FULLY_RECEIVED = 'fully_received';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'po_no', 'po_date', 'vendor_id', 'quotation_id', 'reference', 'delivery_address',
        'delivery_date', 'receiving_depot_id', 'subtotal', 'tax_amount', 'discount_amount',
        'total_amount', 'currency', 'payment_terms', 'terms_conditions', 'notes', 'status',
        'sent_at', 'approved_at', 'approved_by', 'closed_at', 'closed_by',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function quotation() { return $this->belongsTo(Quotation::class); }
    public function receivingDepot() { return $this->belongsTo(Depot::class, 'receiving_depot_id'); }
    public function lines() { return $this->hasMany(PurchaseOrderLine::class); }
    public function grns() { return $this->hasMany(Grn::class); }
}
