<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'marks_obtained',
        'grade',
        'remarks',
        'status',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isPassed(): bool
    {
        return $this->marks_obtained >= $this->exam->passing_marks;
    }

    public function getPercentageAttribute(): float
    {
        if ($this->exam->max_marks <= 0) {
            return 0;
        }

        return ($this->marks_obtained / $this->exam->max_marks) * 100;
    }
}
