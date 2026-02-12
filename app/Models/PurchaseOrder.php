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
        'sent_at', 'sent_by', 'approved_at', 'approved_by', 'closed_at', 'closed_by',
        'submitted_at', 'submitted_by', 'rejected_at', 'rejected_by', 'cancelled_at', 'cancelled_by',
        'approval_notes', 'rejection_reason', 'closure_reason', 'cancellation_reason',
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
            'submitted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function receivingDepot()
    {
        return $this->belongsTo(Depot::class, 'receiving_depot_id');
    }

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    // FIXED: Add user relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
