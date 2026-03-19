<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobType extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'job_title',
        'slug',
        'description',
        'status',
    ];

    // =========================================================
    // Relationships
    // =========================================================

    /**
     * Get job orders of this type.
     */
    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    /**
     * Supervisor job pricing entries for this type.
     * Note: Job types are shared across ALL job categories.
     * Pricing is determined by the combination:
     *   supervisor_id + job_category_id + job_type_id
     */
    public function supervisorJobPricings()
    {
        return $this->hasMany(SupervisorJobPricing::class, 'job_type_id');
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

    /**
     * Check if this job type is a replacement type.
     * Used to determine if old_terminal_id is required from technician.
     */
    public function isReplacement(): bool
    {
        return str_contains(strtolower($this->slug ?? ''), 'replacement')
            || str_contains(strtolower($this->job_title ?? ''), 'replacement');
    }
}
