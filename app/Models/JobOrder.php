<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * JobOrder Model
 *
 * Represents a job order in the Terminal Management System
 * This is a basic model stub - expand based on your needs
 */
class JobOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'job_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'job_number',
        'assigned_to',
        'status',
        'priority',
        'description',
        'scheduled_date',
        'completed_at',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user (technician) assigned to this job order.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this job order.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to get only pending jobs.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get only in-progress jobs.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get only completed jobs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get active jobs (pending or in progress).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }
}
