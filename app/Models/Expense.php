<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'category_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'receipt_number',
        'vendor',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('expense_date', now()->year)
                     ->whereMonth('expense_date', now()->month);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('expense_date', now()->year);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Generate unique expense number
     */
    public static function generateExpenseNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "EXP{$year}{$month}";
        
        $lastExpense = static::where('expense_number', 'like', "{$prefix}%")
                            ->orderBy('expense_number', 'desc')
                            ->first();
        
        if ($lastExpense) {
            $lastNumber = (int) substr($lastExpense->expense_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}
