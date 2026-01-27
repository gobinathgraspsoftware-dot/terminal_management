<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPayslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_number',
        'teacher_id',
        'month',
        'year',
        'basic_salary',
        'hourly_rate',
        'hours_worked',
        'per_class_rate',
        'classes_conducted',
        'gross_salary',
        'epf_employee',
        'epf_employer',
        'socso_employee',
        'socso_employer',
        'tax',
        'other_deductions',
        'bonus',
        'allowances',
        'net_salary',
        'payment_date',
        'payment_method',
        'status',
        'notes',
        'generated_by',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'per_class_rate' => 'decimal:2',
        'classes_conducted' => 'integer',
        'gross_salary' => 'decimal:2',
        'epf_employee' => 'decimal:2',
        'epf_employer' => 'decimal:2',
        'socso_employee' => 'decimal:2',
        'socso_employer' => 'decimal:2',
        'tax' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'bonus' => 'decimal:2',
        'allowances' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)
                     ->where('year', $year);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function getTotalDeductionsAttribute(): float
    {
        return $this->epf_employee + $this->socso_employee + $this->tax + $this->other_deductions;
    }

    public function getTotalAdditionsAttribute(): float
    {
        return $this->bonus + $this->allowances;
    }

    /**
     * Generate unique payslip number
     */
    public static function generatePayslipNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PAY{$year}{$month}";
        
        $lastPayslip = static::where('payslip_number', 'like', "{$prefix}%")
                            ->orderBy('payslip_number', 'desc')
                            ->first();
        
        if ($lastPayslip) {
            $lastNumber = (int) substr($lastPayslip->payslip_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}
