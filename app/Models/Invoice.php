<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'enrollment_id',
        'type',
        'billing_period_start',
        'billing_period_end',
        'subtotal',
        'online_fee',
        'discount',
        'discount_reason',
        'tax',
        'total_amount',
        'paid_amount',
        'due_date',
        'status',
        'reminder_count',
        'last_reminder_date',
        'notes',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'subtotal' => 'decimal:2',
        'online_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'reminder_count' => 'integer',
        'last_reminder_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function paymentReminders()
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function discountUsages()
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function gatewayTransactions()
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(function($q) {
                         $q->where('status', 'pending')
                           ->where('due_date', '<', now());
                     });
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partial', 'overdue']);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForEnrollment($query, $enrollmentId)
    {
        return $query->where('enrollment_id', $enrollmentId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('billing_period_start', now()->year)
                     ->whereMonth('billing_period_start', now()->month);
    }

    public function scopeDueWithin($query, $days = 7)
    {
        return $query->whereIn('status', ['pending', 'partial'])
                     ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function getBalanceAttribute(): float
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date < today();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function canReceivePayment(): bool
    {
        return in_array($this->status, ['pending', 'partial', 'overdue']) && $this->balance > 0;
    }

    public function getBillingPeriodAttribute(): string
    {
        return $this->billing_period_start->format('M Y') . ' - ' . $this->billing_period_end->format('M Y');
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV{$year}{$month}";
        
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}%")
                            ->orderBy('invoice_number', 'desc')
                            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    /**
     * Update invoice status based on payments
     */
    public function updateStatus(): void
    {
        if ($this->paid_amount <= 0) {
            $status = $this->isOverdue() ? 'overdue' : 'pending';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }
}
