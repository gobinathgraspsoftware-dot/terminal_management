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

    // Relationships
    public function client() 
    { 
        return $this->belongsTo(Client::class); 
    }

    public function vendor() 
    { 
        return $this->belongsTo(Vendor::class); 
    }

    public function lines() 
    { 
        return $this->hasMany(QuotationLine::class)->orderBy('line_no'); 
    }

    public function purchaseOrder() 
    { 
        return $this->hasOne(PurchaseOrder::class); 
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper Methods
    public function getPartyAttribute()
    {
        return $this->quotation_type === self::TYPE_CUSTOMER 
            ? $this->client 
            : $this->vendor;
    }

    public function getPartyNameAttribute()
    {
        if ($this->quotation_type === self::TYPE_CUSTOMER && $this->client) {
            return $this->client->client_name;
        } elseif ($this->quotation_type === self::TYPE_VENDOR && $this->vendor) {
            return $this->vendor->vendor_name;
        }
        return 'N/A';
    }

    public function isCustomerQuotation(): bool
    {
        return $this->quotation_type === self::TYPE_CUSTOMER;
    }

    public function isVendorQuotation(): bool
    {
        return $this->quotation_type === self::TYPE_VENDOR;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING_APPROVAL,
        ]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeConverted(): bool
    {
        return $this->isVendorQuotation() 
            && $this->status === self::STATUS_ACCEPTED 
            && !$this->purchaseOrder;
    }

    public function isConvertedToPO(): bool
    {
        return $this->purchaseOrder !== null;
    }

    public function checkExpiry(): bool
    {
        if ($this->valid_until && $this->valid_until->isPast()) {
            if (!$this->isExpired() && !$this->isAccepted()) {
                $this->update(['status' => self::STATUS_EXPIRED]);
                return true;
            }
        }
        return false;
    }

    public function calculateTotals(): void
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($this->lines as $line) {
            $subtotal += $line->line_total;
            $taxAmount += $line->tax_amount;
        }

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total_amount = $subtotal + $taxAmount - $this->discount_amount;
    }

    public static function getStatusList(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SENT => 'Sent',
            self::STATUS_ACCEPTED => 'Accepted',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function getTypeList(): array
    {
        return [
            self::TYPE_CUSTOMER => 'Customer Quotation',
            self::TYPE_VENDOR => 'Vendor Quotation',
        ];
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_PENDING_APPROVAL => 'bg-warning',
            self::STATUS_APPROVED => 'bg-info',
            self::STATUS_SENT => 'bg-primary',
            self::STATUS_ACCEPTED => 'bg-success',
            self::STATUS_REJECTED => 'bg-danger',
            self::STATUS_EXPIRED => 'bg-dark',
            self::STATUS_CANCELLED => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabel(): string
    {
        return self::getStatusList()[$this->status] ?? 'Unknown';
    }

    public function getTypeLabel(): string
    {
        return self::getTypeList()[$this->quotation_type] ?? 'Unknown';
    }

    // Scopes
    public function scopeCustomerQuotations($query)
    {
        return $query->where('quotation_type', self::TYPE_CUSTOMER);
    }

    public function scopeVendorQuotations($query)
    {
        return $query->where('quotation_type', self::TYPE_VENDOR);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
                     ->orWhere(function($q) {
                         $q->where('valid_until', '<', now())
                           ->whereNotIn('status', [
                               self::STATUS_ACCEPTED,
                               self::STATUS_REJECTED,
                               self::STATUS_CANCELLED,
                               self::STATUS_EXPIRED
                           ]);
                     });
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_REJECTED,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED
        ]);
    }
}
