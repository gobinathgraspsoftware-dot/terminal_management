<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    const SUPERVISOR_TYPE_INTERNAL = 'internal';
    const SUPERVISOR_TYPE_EXTERNAL = 'external';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'supervisor_id',
        'supervisor_type',
        'coverage_states',
        'skill_tags',
        // Removed: 'default_rate_card_id' — rate_cards table dropped
        'mileage_rate',
        'address',
        'state_id',
        'city_id',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'ifsc_code',
        'branch_name',
        'date_of_birth',
        'gender',
        'emergency_contact_name',
        'emergency_contact_phone',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'coverage_states' => 'array',
            'date_of_birth' => 'date',
            'skill_tags' => 'array',
            'mileage_rate' => 'decimal:2',
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->employee_id)) {
                $user->employee_id = static::generateEmployeeId();
            }
        });
    }

    /**
     * Generate unique employee ID
     */
    protected static function generateEmployeeId(): string
    {
        $prefix = 'EMP';
        $lastUser = static::withTrashed()
            ->where('employee_id', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastUser) {
            $lastNumber = (int) substr($lastUser->employee_id, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */

    // Self-referencing relationship - Supervisor
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // Self-referencing relationship - Technicians under this supervisor
    public function technicians()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    // State relationship
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    // City relationship
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    // Removed: defaultRateCard() — rate_cards table dropped

    /**
     * Get stock balances for this technician
     */
    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'location_id')
            ->where('location_type', 'technician');
    }

    /**
     * Supervisor job pricing - prices mapped per job_category + job_type.
     */
    public function supervisorJobPricings()
    {
        return $this->hasMany(\App\Models\SupervisorJobPricing::class, 'supervisor_id');
    }

    /**
     * Get stock balance for technician's personal depot
     */
    public function technicianStockBalances()
    {
        return $this->hasMany(StockBalance::class, 'location_id')
            ->where('location_type', 'technician');
    }

    /**
     * Check if this supervisor is internal (has technician team).
     */
    public function isInternalSupervisor(): bool
    {
        return $this->hasRole('supervisor') && $this->supervisor_type === self::SUPERVISOR_TYPE_INTERNAL;
    }

    /**
     * Check if this supervisor is external (no technician team).
     */
    public function isExternalSupervisor(): bool
    {
        return $this->hasRole('supervisor') && $this->supervisor_type === self::SUPERVISOR_TYPE_EXTERNAL;
    }

    /**
     * Get the effective job pricing for a given category + type.
     */
    public function getJobPrice(int $jobCategoryId, int $jobTypeId): ?float
    {
        // Technician: inherit from supervisor
        if ($this->hasRole('technician') && $this->supervisor_id) {
            $pricing = \App\Models\SupervisorJobPricing::where('supervisor_id', $this->supervisor_id)
                ->where('job_category_id', $jobCategoryId)
                ->where('job_type_id', $jobTypeId)
                ->first();
            return $pricing ? (float) $pricing->price : null;
        }

        // Supervisor (internal or external): own pricing
        if ($this->hasRole('supervisor')) {
            $pricing = \App\Models\SupervisorJobPricing::where('supervisor_id', $this->id)
                ->where('job_category_id', $jobCategoryId)
                ->where('job_type_id', $jobTypeId)
                ->first();
            return $pricing ? (float) $pricing->price : null;
        }

        return null;
    }

    /**
     * Get the user's login histories.
     */
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // Jobs assigned as technician
    public function assignedJobs()
    {
        return $this->hasMany(JobOrder::class, 'technician_id');
    }

    // Jobs as supervisor
    public function supervisedJobs()
    {
        return $this->hasMany(JobOrder::class, 'supervisor_id');
    }

    // Job assignments (for multi-technician jobs)
    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class, 'technician_id');
    }

    // Stock issues
    public function stockIssues()
    {
        return $this->hasMany(StockIssue::class, 'to_technician_id');
    }

    // Claims
    public function claims()
    {
        return $this->hasMany(Claim::class, 'technician_id');
    }

    // Payout lines
    public function payoutLines()
    {
        return $this->hasMany(PayoutLine::class, 'technician_id');
    }

    // GPS tracks
    public function gpsTracks()
    {
        return $this->hasMany(GpsTrack::class, 'technician_id');
    }

    /**
     * Scopes
     */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeByRole($query, $role)
    {
        return $query->role($role);
    }

    public function scopeSupervisors($query)
    {
        return $query->role('supervisor');
    }

    public function scopeTechnicians($query)
    {
        return $query->role('technician');
    }

    public function scopeWithSupervisor($query)
    {
        return $query->whereNotNull('supervisor_id');
    }

    /**
     * Scope: Unassigned technicians (need supervisor assignment)
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('supervisor_id')->role('technician');
    }

    public function scopeInState($query, $state)
    {
        return $query->whereJsonContains('coverage_states', $state);
    }

    public function scopeByStateId($query, $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    public function scopeByCityId($query, $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Accessors
     */

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? 'No Role';
    }

    public function getIsSupervisorAttribute(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function getIsTechnicianAttribute(): bool
    {
        return $this->hasRole('technician');
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasRole('admin');
    }

    public function getTeamMembersAttribute()
    {
        if (!$this->is_supervisor) {
            return collect();
        }

        return $this->technicians()->active()->get();
    }

    public function getTeamMembersCountAttribute(): int
    {
        if (!$this->is_supervisor) {
            return 0;
        }

        return $this->technicians()->active()->count();
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar);
        }

        $initial = strtoupper(substr($this->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initial}&size=200&background=random";
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
            self::STATUS_INACTIVE => '<span class="badge bg-secondary">Inactive</span>',
            self::STATUS_SUSPENDED => '<span class="badge bg-danger">Suspended</span>',
            default => '<span class="badge bg-warning">Unknown</span>',
        };
    }

    /**
     * Get state name accessor
     */
    public function getStateNameAttribute(): ?string
    {
        return $this->state?->name;
    }

    /**
     * Get city name accessor
     */
    public function getCityNameAttribute(): ?string
    {
        return $this->city?->name;
    }

    /**
     * Get effective mileage rate.
     */
    public function getEffectiveMileageRateAttribute(): ?string
    {
        if ($this->is_technician && $this->supervisor_id) {
            return $this->supervisor?->mileage_rate;
        }

        return $this->mileage_rate;
    }

    /**
     * Helper Methods
     */

    public function canManageUser(User $user): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->hasRole('supervisor')) {
            return $user->supervisor_id === $this->id;
        }

        return $this->id === $user->id;
    }

    public function canViewJob(JobOrder $job): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->hasRole('supervisor')) {
            return $job->supervisor_id === $this->id
                || $this->technicians->contains($job->technician_id);
        }

        return $job->technician_id === $this->id;
    }

    public function getTeamJobsQuery()
    {
        if ($this->hasRole('admin')) {
            return JobOrder::query();
        }

        if ($this->hasRole('supervisor')) {
            $teamTechnicianIds = $this->technicians->pluck('id')->toArray();

            return JobOrder::where(function($query) use ($teamTechnicianIds) {
                $query->where('supervisor_id', $this->id)
                    ->orWhereIn('technician_id', $teamTechnicianIds);
            });
        }

        return JobOrder::where('technician_id', $this->id);
    }
}
