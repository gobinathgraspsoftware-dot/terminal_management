<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        'user_id',
        'staff_id',
        'ic_number',
        'address',
        'position',
        'department',
        'join_date',
        'employment_type',
        'monthly_salary',
        'bank_name',
        'bank_account',
        'documents',
        'status',
    ];

    protected $casts = [
        'join_date' => 'date',
        'monthly_salary' => 'decimal:2',
        'documents' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function physicalMaterialCollections()
    {
        return $this->hasMany(PhysicalMaterialCollection::class);
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

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', $position);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeFullTime($query)
    {
        return $query->where('employment_type', 'full-time');
    }

    public function scopePartTime($query)
    {
        return $query->where('employment_type', 'part-time');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getFullNameAttribute(): string
    {
        return $this->user->name ?? 'Unknown';
    }
}
