<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisorJobPricing extends Model
{
    use HasFactory;

    protected $table = 'supervisor_job_pricing';

    protected $fillable = [
        'supervisor_id',
        'job_category_id',
        'job_type_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    // =========================================================
    // Relationships
    // =========================================================

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    // =========================================================
    // Scopes
    // =========================================================

    public function scopeForSupervisor($query, int $supervisorId)
    {
        return $query->where('supervisor_id', $supervisorId);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('job_category_id', $categoryId);
    }
}
