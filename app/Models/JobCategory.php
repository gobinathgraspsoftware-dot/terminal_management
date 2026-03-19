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

    // Slug constants for dynamic field logic
    const SLUG_TERMINAL = 'terminal';
    const SLUG_ROUTER = 'router';
    const SLUG_PROJECT = 'project';
    const SLUG_ACCESSORIES = 'accessories';

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
     * Job types belonging to this category.
     */
    public function jobTypes()
    {
        return $this->hasMany(JobType::class, 'job_category_id');
    }

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

    /**
     * Whether terminal_id field is required for this category.
     */
    public function requiresTerminalId(): bool
    {
        return $this->slug === self::SLUG_TERMINAL;
    }

    /**
     * Whether router_id field is required for this category.
     */
    public function requiresRouterId(): bool
    {
        return $this->slug === self::SLUG_ROUTER;
    }

    /**
     * Whether terminal_id and router_id are both shown (optional).
     */
    public function showsBothDeviceIds(): bool
    {
        return $this->slug === self::SLUG_PROJECT;
    }

    /**
     * Whether device ID fields should be hidden entirely.
     */
    public function hidesDeviceIds(): bool
    {
        return $this->slug === self::SLUG_ACCESSORIES;
    }
}
