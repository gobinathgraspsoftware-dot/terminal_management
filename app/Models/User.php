<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'whatsapp_number',
        'status',
        'email_verified_at',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'last_login_ip',
        'employee_id',
        'supervisor_id',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
            'failed_login_attempts' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the student profile associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the parent profile associated with the user.
     */
    public function parent()
    {
        return $this->hasOne(Parents::class);
    }

    /**
     * Get the teacher profile associated with the user.
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Get the staff profile associated with the user.
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get all notifications for this user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all notification logs for this user.
     */
    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Get announcements created by this user.
     */
    public function createdAnnouncements()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * Get announcement reads for this user.
     */
    public function announcementReads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // ==========================================
    // TEAM MANAGEMENT RELATIONSHIPS - ADDED
    // ==========================================

    /**
     * Get the supervisor assigned to this technician.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get all technicians supervised by this supervisor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function technicians()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Get all job orders assigned to this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class, 'assigned_to');
    }

    /**
     * Get all activity logs for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to get only suspended users.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Scope to get only pending users.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if user is locked out.
     */
    public function isLockedOut(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get user's full profile based on role.
     */
    public function getProfile()
    {
        if ($this->hasRole('student')) {
            return $this->student;
        } elseif ($this->hasRole('parent')) {
            return $this->parent;
        } elseif ($this->hasRole('teacher')) {
            return $this->teacher;
        } elseif ($this->hasRole('staff')) {
            return $this->staff;
        }

        return null;
    }

    /**
     * Get user's display role.
     */
    public function getDisplayRole(): string
    {
        if ($this->hasRole('super-admin')) {
            return 'Super Admin';
        } elseif ($this->hasRole('admin')) {
            return 'Admin';
        } elseif ($this->hasRole('staff')) {
            return 'Staff';
        } elseif ($this->hasRole('teacher')) {
            return 'Teacher';
        } elseif ($this->hasRole('parent')) {
            return 'Parent';
        } elseif ($this->hasRole('student')) {
            return 'Student';
        }

        return 'Unknown';
    }
}
