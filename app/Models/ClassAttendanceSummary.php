<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassAttendanceSummary extends Model
{
    use HasFactory;

    protected $table = 'class_attendance_summaries';

    protected $fillable = [
        'class_id',
        'student_id',
        'month',
        'year',
        'total_classes',
        'classes_attended',
        'classes_absent',
        'classes_late',
        'classes_excused',
        'attendance_percentage',
    ];

    protected $casts = [
        'total_classes' => 'integer',
        'classes_attended' => 'integer',
        'classes_absent' => 'integer',
        'classes_late' => 'integer',
        'classes_excused' => 'integer',
        'attendance_percentage' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)
                     ->where('year', $year);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeLowAttendance($query, $threshold = 75)
    {
        return $query->where('attendance_percentage', '<', $threshold);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function hasLowAttendance(float $threshold = 75): bool
    {
        return $this->attendance_percentage < $threshold;
    }

    public function calculatePercentage(): float
    {
        if ($this->total_classes <= 0) {
            return 0;
        }

        return round(($this->classes_attended / $this->total_classes) * 100, 2);
    }
}
