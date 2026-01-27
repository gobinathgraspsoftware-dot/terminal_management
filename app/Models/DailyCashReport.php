<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'opening_cash',
        'total_cash_received',
        'total_qr_received',
        'total_online_received',
        'total_pos_sales',
        'total_expenses',
        'total_cash_in_hand',
        'expected_cash',
        'actual_cash',
        'difference',
        'notes',
        'prepared_by',
        'verified_by',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'opening_cash' => 'decimal:2',
        'total_cash_received' => 'decimal:2',
        'total_qr_received' => 'decimal:2',
        'total_online_received' => 'decimal:2',
        'total_pos_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'total_cash_in_hand' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('report_date', $date);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('report_date', now()->year)
                     ->whereMonth('report_date', now()->month);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function hasDifference(): bool
    {
        return abs($this->difference) > 0.01; // Allow 1 cent tolerance
    }

    public function getTotalRevenueAttribute(): float
    {
        return $this->total_cash_received + $this->total_qr_received + $this->total_online_received + $this->total_pos_sales;
    }
}
