<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCategory extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'category_name',
        'slug',
        'description',
        'status',
    ];

    // =========================================================
    // Relationships
    // =========================================================

    /**
     * Supervisor job pricing entries for this category.
     */
    public function supervisorJobPricings()
    {
        return $this->hasMany(SupervisorJobPricing::class, 'job_category_id');
    }

    // =========================================================
    // Scopes
    // =========================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    // =========================================================
    // Helpers
    // =========================================================

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
