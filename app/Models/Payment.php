<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'student_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'screenshot_path',
        'qr_code',
        'bank_name',
        'processed_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function gatewayTransaction()
    {
        return $this->hasOne(PaymentGatewayTransaction::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('payment_date', now()->year)
                     ->whereMonth('payment_date', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('payment_date', now()->year);
    }

    public function scopeCash($query)
    {
        return $query->where('payment_method', 'cash');
    }

    public function scopeQr($query)
    {
        return $query->where('payment_method', 'qr');
    }

    public function scopeOnline($query)
    {
        return $query->where('payment_method', 'online');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'qr' => 'QR Payment',
            'online' => 'Online Gateway',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Generate unique payment number
     */
    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PAY{$year}{$month}";
        
        $lastPayment = static::where('payment_number', 'like', "{$prefix}%")
                            ->orderBy('payment_number', 'desc')
                            ->first();
        
        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}
