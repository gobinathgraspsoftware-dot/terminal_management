<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_AR = 'ar';
    const TYPE_AP = 'ap';

    const PAYMENT_STATUS_UNPAID = 'unpaid';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_OVERDUE = 'overdue';
    const PAYMENT_STATUS_VOID = 'void';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_SENT = 'sent';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_no', 'invoice_date', 'invoice_type', 'client_id', 'vendor_id', 'partner_id',
        'reference', 'billing_address', 'due_date', 'subtotal', 'tax_amount', 'discount_amount',
        'total_amount', 'paid_amount', 'currency', 'payment_terms', 'terms_conditions', 'notes',
        'payment_status', 'status', 'approved_at', 'approved_by', 'sent_at', 'last_reminder_at',
        'reminder_count', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_reminder_at' => 'datetime',
        ];
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function partner() { return $this->belongsTo(Partner::class); }
    public function lines() { return $this->hasMany(InvoiceLine::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function creditNotes() { return $this->hasMany(CreditNote::class); }
    public function jobOrders() { return $this->hasMany(JobOrder::class); }

    public function getOutstandingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->payment_status === self::PAYMENT_STATUS_PAID) {
            return 0;
        }
        return max(0, now()->diffInDays($this->due_date, false));
    }
}
