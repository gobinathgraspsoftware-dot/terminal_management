<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialView extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'student_id',
        'viewed_at',
        'duration_seconds',
        'device_info',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    public $timestamps = false;
    const CREATED_AT = 'viewed_at';
    const UPDATED_AT = null;

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('material_id', $materialId);
    }
}
