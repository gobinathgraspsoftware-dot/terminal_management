<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalMaterialCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'physical_material_id',
        'student_id',
        'collected_at',
        'collected_by_name',
        'staff_id',
        'notes',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    const UPDATED_AT = null;

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function physicalMaterial()
    {
        return $this->belongsTo(PhysicalMaterial::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('physical_material_id', $materialId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('collected_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('collected_at', today());
    }
}
