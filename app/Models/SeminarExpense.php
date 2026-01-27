<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeminarExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'seminar_id',
        'category',
        'description',
        'amount',
        'expense_date',
        'receipt_number',
        'vendor',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function seminar()
    {
        return $this->belongsTo(Seminar::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeBySeminar($query, $seminarId)
    {
        return $query->where('seminar_id', $seminarId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('expense_date', now()->year)
                     ->whereMonth('expense_date', now()->month);
    }
}
