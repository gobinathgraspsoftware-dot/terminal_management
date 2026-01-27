<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'student_id',
        'ic_number',
        'date_of_birth',
        'gender',
        'address',
        'school_name',
        'grade_level',
        'registration_type',
        'registration_date',
        'referred_by',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'registration_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    public function referredBy()
    {
        return $this->belongsTo(Student::class, 'referred_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function attendance()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function trialClasses()
    {
        return $this->hasMany(TrialClass::class);
    }

    public function reviews()
    {
        return $this->hasMany(StudentReview::class);
    }

    public function referralsGiven()
    {
        return $this->hasMany(Referral::class, 'referrer_student_id');
    }

    public function referralsReceived()
    {
        return $this->hasMany(Referral::class, 'referred_student_id');
    }

    public function vouchers()
    {
        return $this->hasMany(ReferralVoucher::class);
    }

    public function physicalMaterialCollections()
    {
        return $this->hasMany(PhysicalMaterialCollection::class);
    }

    public function materialViews()
    {
        return $this->hasMany(MaterialView::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    public function seminarParticipations()
    {
        return $this->hasMany(SeminarParticipant::class);
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

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', 'rejected');
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByGradeLevel($query, $grade)
    {
        return $query->where('grade_level', $grade);
    }

    public function scopeByRegistrationType($query, $type)
    {
        return $query->where('registration_type', $type);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function getFullNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown';
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }
}
