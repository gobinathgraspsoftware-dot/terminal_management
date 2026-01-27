<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'class_id',
        'subject_id',
        'rating',
        'review_text',
        'review_date',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'review_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeLowRated($query, $maxRating = 2)
    {
        return $query->where('rating', '<=', $maxRating);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }
}
