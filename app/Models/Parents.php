<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parents extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'ic_number',
        'occupation',
        'address',
        'emergency_contact',
        'relationship_to_student',
        'notes',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeWithActiveStudents($query)
    {
        return $query->whereHas('students', function($q) {
            $q->where('status', 'active');
        });
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function getFullNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown';
    }

    public function getWhatsappNumberAttribute(): ?string
    {
        return $this->user->whatsapp_number ?? $this->user->phone;
    }

    public function getActiveStudentsCount(): int
    {
        return $this->students()->where('status', 'active')->count();
    }
}
