<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enrollment_number',
        'student_id',
        'package_id',
        'class_id',
        'enrollment_date',
        'start_date',
        'end_date',
        'monthly_fee',
        'online_fee',
        'payment_cycle_day',
        'status',
        'notes',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'online_fee' => 'decimal:2',
        'payment_cycle_day' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function feeHistory()
    {
        return $this->hasMany(EnrollmentFeeHistory::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->where('status', 'active')
                     ->whereNotNull('end_date')
                     ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getTotalFeeAttribute(): float
    {
        return $this->monthly_fee + ($this->online_fee ?? 0);
    }

    /**
     * Generate unique enrollment number
     */
    public static function generateEnrollmentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "ENR{$year}{$month}";
        
        $lastEnrollment = static::where('enrollment_number', 'like', "{$prefix}%")
                                ->orderBy('enrollment_number', 'desc')
                                ->first();
        
        if ($lastEnrollment) {
            $lastNumber = (int) substr($lastEnrollment->enrollment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}
