<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeminarParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'seminar_id',
        'student_id',
        'registration_date',
        'fee_amount',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'attendance_status',
        'attended_at',
        'certificate_issued',
        'notes',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'fee_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'attended_at' => 'datetime',
        'certificate_issued' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function seminar()
    {
        return $this->belongsTo(Seminar::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeRegistered($query)
    {
        return $query->where('payment_status', 'registered');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeRefunded($query)
    {
        return $query->where('payment_status', 'refunded');
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'attended');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function hasAttended(): bool
    {
        return $this->attendance_status === 'attended';
    }
}
