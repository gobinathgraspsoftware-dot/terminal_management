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
        'employee_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'supervisor_id',
        'coverage_states',
        'skill_tags',
        'default_rate_card_id',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'status',
        'last_login_at',
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
            'password' => 'hashed',
            'coverage_states' => 'array',
            'skill_tags' => 'array',
        ];
    }

    // ==========================================
    // BOOT METHOD FOR MODEL EVENTS
    // ==========================================

    /**
     * Boot the model and attach observers.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate employee ID on creating
        static::creating(function ($user) {
            if (empty($user->employee_id)) {
                $user->employee_id = static::generateEmployeeId();
            }
        });

        // Attach observer
        static::observe(\App\Observers\UserObserver::class);
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the supervisor of this user (self-referencing relationship).
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get all technicians under this supervisor (self-referencing relationship).
     */
    public function technicians()
    {
        return $this->hasMany(User::class, 'supervisor_id')
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'technician');
                    });
    }

    /**
     * Get the default rate card for this user.
     */
    public function rateCard()
    {
        return $this->belongsTo(RateCard::class, 'default_rate_card_id');
    }

    /**
     * Get jobs assigned to this user as technician.
     */
    public function assignedJobs()
    {
        return $this->hasMany(JobOrder::class, 'technician_id');
    }

    /**
     * Get jobs where this user is the supervisor.
     */
    public function supervisedJobs()
    {
        return $this->hasMany(JobOrder::class, 'supervisor_id');
    }

    /**
     * Get claims submitted by this technician.
     */
    public function claims()
    {
        return $this->hasMany(Claim::class, 'technician_id');
    }

    /**
     * Get payout lines for this technician.
     */
    public function payoutLines()
    {
        return $this->hasMany(PayoutLine::class, 'technician_id');
    }

    /**
     * Get stock issues to this technician.
     */
    public function stockIssues()
    {
        return $this->hasMany(StockIssue::class, 'to_technician_id');
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
     * Scope to filter users by role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Scope to get users with their supervisor relationship loaded.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithSupervisor($query)
    {
        return $query->with('supervisor');
    }

    /**
     * Scope to get only technicians.
     */
    public function scopeTechnicians($query)
    {
        return $query->byRole('technician');
    }

    /**
     * Scope to get only supervisors.
     */
    public function scopeSupervisors($query)
    {
        return $query->byRole('supervisor');
    }

    /**
     * Scope to get only admins.
     */
    public function scopeAdmins($query)
    {
        return $query->byRole('admin');
    }

    /**
     * Scope to get independent technicians (without supervisor).
     */
    public function scopeIndependentTechnicians($query)
    {
        return $query->byRole('technician')
                    ->whereNull('supervisor_id');
    }

    /**
     * Scope to get technicians under a specific supervisor.
     */
    public function scopeTechniciansByState($query, $state)
    {
        return $query->byRole('technician')
                    ->whereJsonContains('coverage_states', $state);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get the user's full name (alias for name).
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get the user's primary role name.
     */
    public function getRoleNameAttribute(): string
    {
        $role = $this->roles->first();
        return $role ? $role->name : 'No Role';
    }

    /**
     * Get formatted role name for display.
     */
    public function getDisplayRoleAttribute(): string
    {
        $role = $this->role_name;
        return ucwords(str_replace('_', ' ', $role));
    }

    /**
     * Check if user is a supervisor.
     */
    public function getIsSupervisorAttribute(): bool
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Check if user is a technician.
     */
    public function getIsTechnicianAttribute(): bool
    {
        return $this->hasRole('technician');
    }

    /**
     * Check if user is an admin.
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if technician is independent (no supervisor).
     */
    public function getIsIndependentAttribute(): bool
    {
        return $this->is_technician && is_null($this->supervisor_id);
    }

    /**
     * Get all team members for this supervisor.
     * Returns collection of technicians under this supervisor.
     */
    public function getTeamMembersAttribute()
    {
        if (!$this->is_supervisor) {
            return collect([]);
        }

        return $this->technicians()->with('rateCard')->get();
    }

    /**
     * Get team member count for supervisor.
     */
    public function getTeamSizeAttribute(): int
    {
        if (!$this->is_supervisor) {
            return 0;
        }

        return $this->technicians()->count();
    }

    /**
     * Get supervisor name or 'Independent' for display.
     */
    public function getSupervisorNameAttribute(): string
    {
        return $this->supervisor ? $this->supervisor->name : 'Independent';
    }

    /**
     * Get coverage states as comma-separated string.
     */
    public function getCoverageStatesStringAttribute(): string
    {
        if (empty($this->coverage_states)) {
            return 'All States';
        }

        return is_array($this->coverage_states) 
            ? implode(', ', $this->coverage_states)
            : $this->coverage_states;
    }

    /**
     * Get skill tags as comma-separated string.
     */
    public function getSkillTagsStringAttribute(): string
    {
        if (empty($this->skill_tags)) {
            return 'No Skills';
        }

        return is_array($this->skill_tags)
            ? implode(', ', $this->skill_tags)
            : $this->skill_tags;
    }

    /**
     * Get status badge class for UI.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'badge bg-success',
            'inactive' => 'badge bg-secondary',
            'suspended' => 'badge bg-danger',
            default => 'badge bg-warning',
        };
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is locked out.
     */
    public function isLockedOut(): bool
    {
        // Implement lock logic if needed
        return false;
    }

    /**
     * Check if user has a specific skill.
     */
    public function hasSkill(string $skill): bool
    {
        if (empty($this->skill_tags)) {
            return false;
        }

        return in_array($skill, $this->skill_tags);
    }

    /**
     * Check if user covers a specific state.
     */
    public function coversState(string $state): bool
    {
        if (empty($this->coverage_states)) {
            return true; // If no states specified, covers all
        }

        return in_array($state, $this->coverage_states);
    }

    /**
     * Get all jobs visible to this user based on team scoping rules.
     * - Admin: sees all jobs
     * - Supervisor: sees own jobs + team technicians' jobs
     * - Technician: sees only self-assigned jobs
     */
    public function getVisibleJobsQuery()
    {
        if ($this->is_admin) {
            // Admin sees all jobs
            return JobOrder::query();
        }

        if ($this->is_supervisor) {
            // Supervisor sees own jobs + team jobs
            $teamTechnicianIds = $this->technicians()->pluck('id')->toArray();
            
            return JobOrder::where(function ($query) use ($teamTechnicianIds) {
                $query->where('supervisor_id', $this->id)
                      ->orWhereIn('technician_id', $teamTechnicianIds);
            });
        }

        // Technician sees only own jobs
        return JobOrder::where('technician_id', $this->id);
    }

    /**
     * Check if user can view a specific job (team scoping).
     */
    public function canViewJob(JobOrder $job): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($this->is_supervisor) {
            // Can view if job's supervisor is self, or job's technician is in team
            return $job->supervisor_id === $this->id 
                || $this->technicians()->where('id', $job->technician_id)->exists();
        }

        // Technician can only view own jobs
        return $job->technician_id === $this->id;
    }

    /**
     * Get pending claims count for this technician.
     */
    public function getPendingClaimsCountAttribute(): int
    {
        if (!$this->is_technician) {
            return 0;
        }

        return $this->claims()
                    ->whereIn('status', ['draft', 'submitted', 'pending_approval'])
                    ->count();
    }

    /**
     * Get total commission earned (current month).
     */
    public function getMonthlyCommissionAttribute(): float
    {
        if (!$this->is_technician) {
            return 0.00;
        }

        return $this->assignedJobs()
                    ->whereMonth('completed_at', now()->month)
                    ->whereYear('completed_at', now()->year)
                    ->where('status', 'completed')
                    ->sum('commission_amount') ?? 0.00;
    }

    /**
     * Generate unique employee ID.
     */
    public static function generateEmployeeId(): string
    {
        $year = date('y');
        $prefix = "EMP{$year}";
        
        $lastUser = static::where('employee_id', 'like', "{$prefix}%")
                         ->orderBy('employee_id', 'desc')
                         ->first();
        
        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->employee_id, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    /**
     * Assign to supervisor.
     */
    public function assignToSupervisor(?int $supervisorId): bool
    {
        if (!$this->is_technician) {
            return false;
        }

        if ($supervisorId) {
            $supervisor = static::find($supervisorId);
            if (!$supervisor || !$supervisor->is_supervisor) {
                return false;
            }
        }

        $this->update(['supervisor_id' => $supervisorId]);
        return true;
    }

    /**
     * Remove from supervisor (make independent).
     */
    public function makeIndependent(): bool
    {
        if (!$this->is_technician) {
            return false;
        }

        $this->update(['supervisor_id' => null]);
        return true;
    }

    /**
     * Update coverage states.
     */
    public function updateCoverageStates(array $states): bool
    {
        return $this->update(['coverage_states' => $states]);
    }

    /**
     * Update skill tags.
     */
    public function updateSkillTags(array $skills): bool
    {
        return $this->update(['skill_tags' => $skills]);
    }

    /**
     * Get avatar URL or default.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Generate default avatar with initials
        $initials = $this->getInitials();
        return "https://ui-avatars.com/api/?name={$initials}&background=random";
    }

    /**
     * Get user initials for avatar.
     */
    protected function getInitials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= strtoupper(substr($name, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }
}