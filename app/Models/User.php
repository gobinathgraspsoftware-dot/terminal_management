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
            'date_of_birth' => 'date',
            'skill_tags' => 'array',
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

    // Default rate card
    public function defaultRateCard()
    {
        return $this->belongsTo(RateCard::class, 'default_rate_card_id');
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

    public function scopeIndependent($query)
    {
        return $query->whereNull('supervisor_id')->role('technician');
    }

    public function scopeInState($query, $state)
    {
        return $query->whereJsonContains('coverage_states', $state);
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

    public function getIsIndependentAttribute(): bool
    {
        return $this->is_technician && is_null($this->supervisor_id);
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
            return asset('storage/' . $this->avatar);
        }

        // Default avatar based on first letter
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
     * Helper Methods
     */

    public function canManageUser(User $user): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->hasRole('supervisor')) {
            // Can manage own team members
            return $user->supervisor_id === $this->id;
        }

        // Technicians can only view self
        return $this->id === $user->id;
    }

    public function canViewJob(JobOrder $job): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        if ($this->hasRole('supervisor')) {
            // Can view jobs where they are supervisor or jobs of their team
            return $job->supervisor_id === $this->id
                || $this->technicians->contains($job->technician_id);
        }

        // Technician can only view own jobs
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

        // Technician - only own jobs
        return JobOrder::where('technician_id', $this->id);
    }
}
