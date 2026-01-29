<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const JOB_INTAKE_MANUAL = 'manual';
    const JOB_INTAKE_IMPORT = 'import';
    const JOB_INTAKE_API = 'api';

    protected $fillable = [
        'partner_code',
        'partner_name',
        'pic_name',
        'pic_email',
        'pic_phone',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'sla_rules',
        'job_intake_method',
        'api_key',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sla_rules' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($partner) {
            if (empty($partner->partner_code)) {
                $partner->partner_code = static::generatePartnerCode();
            }
        });
    }

    /**
     * Generate a new partner code (PTN000001 format)
     * Changed from protected to PUBLIC so controller can call it
     */
    public static function generatePartnerCode(): string
    {
        $prefix = 'PTN';
        $lastPartner = static::withTrashed()
            ->where('partner_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPartner) {
            $lastNumber = (int) substr($lastPartner->partner_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function scopeWithClients($query)
    {
        return $query->with('clients');
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->postcode . ' ' . $this->city,
            $this->state,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
            self::STATUS_INACTIVE => '<span class="badge bg-secondary">Inactive</span>',
            default => '<span class="badge bg-warning">Unknown</span>',
        };
    }

    public function getJobIntakeMethodBadgeAttribute(): string
    {
        return match($this->job_intake_method) {
            self::JOB_INTAKE_MANUAL => '<span class="badge bg-primary">Manual</span>',
            self::JOB_INTAKE_IMPORT => '<span class="badge bg-info">Import</span>',
            self::JOB_INTAKE_API => '<span class="badge bg-success">API</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
}
