<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'teacher_id',
        'status',
        'attendance_status',
        'teacher_feedback',
        'student_interest_level',
        'recommend_enrollment',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i:s',
        'duration_minutes' => 'integer',
        'recommend_enrollment' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'attended');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_date', '>=', today())
                     ->orderBy('scheduled_date')
                     ->orderBy('scheduled_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_date', today());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function hasAttended(): bool
    {
        return $this->attendance_status === 'attended';
    }

    public function isRecommendedForEnrollment(): bool
    {
        return $this->recommend_enrollment === true;
    }
}
