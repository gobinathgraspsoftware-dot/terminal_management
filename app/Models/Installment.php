<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_amount',
        'paid_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function getBalanceAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
