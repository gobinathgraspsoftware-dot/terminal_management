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

    /**
     * Get job orders belonging to this category.
     */
    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    /**
     * Scope: Active only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Check if job category is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
