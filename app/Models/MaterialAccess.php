<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialAccess extends Model
{
    use HasFactory;

    protected $table = 'material_access';

    protected $fillable = [
        'material_id',
        'user_id',
        'student_id',
        'class_id',
        'enrollment_id',
        'access_granted_at',
        'access_expires_at',
        'granted_by',
    ];

    protected $casts = [
        'access_granted_at' => 'datetime',
        'access_expires_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('access_expires_at')
              ->orWhere('access_expires_at', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('access_expires_at')
                     ->where('access_expires_at', '<', now());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return !$this->access_expires_at || $this->access_expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->access_expires_at && $this->access_expires_at->isPast();
    }
}
