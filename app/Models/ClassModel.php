<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'code',
        'subject_id',
        'teacher_id',
        'type',
        'mode',
        'capacity',
        'current_enrollment',
        'day',
        'start_time',
        'end_time',
        'room',
        'description',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'capacity' => 'integer',
        'current_enrollment' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'class_id');
    }

    public function sessions()
    {
        return $this->hasMany(ClassSession::class, 'class_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'class_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'class_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'target_class_id');
    }

    public function attendanceSummaries()
    {
        return $this->hasMany(ClassAttendanceSummary::class, 'class_id');
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

    public function scopeOnline($query)
    {
        return $query->where('mode', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('mode', 'offline');
    }

    public function scopeHybrid($query)
    {
        return $query->where('mode', 'hybrid');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDay($query, $day)
    {
        return $query->where('day', $day);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFull(): bool
    {
        return $this->current_enrollment >= $this->capacity;
    }

    public function hasAvailableSpace(): bool
    {
        return $this->current_enrollment < $this->capacity;
    }

    public function getAvailableSpaceAttribute(): int
    {
        return max(0, $this->capacity - $this->current_enrollment);
    }
}
