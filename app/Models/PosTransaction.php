<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'payment_method',
        'payment_reference',
        'customer_name',
        'customer_phone',
        'cashier_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function items()
    {
        return $this->hasMany(PosTransactionItem::class, 'transaction_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
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

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('transaction_date', now()->year)
                     ->whereMonth('transaction_date', now()->month);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Generate unique transaction number
     */
    public static function generateTransactionNumber(): string
    {
        $date = date('Ymd');
        $prefix = "POS{$date}";
        
        $lastTransaction = static::where('transaction_number', 'like', "{$prefix}%")
                                 ->orderBy('transaction_number', 'desc')
                                 ->first();
        
        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}
