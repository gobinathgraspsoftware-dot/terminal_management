<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'client_code',
        'client_name',
        'company_name',
        'registration_no',
        'tax_id',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'payment_terms',
        'credit_limit',
        'pic_name',
        'pic_email',
        'pic_phone',
        'partner_id',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => 'integer',
            'credit_limit' => 'decimal:2',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($client) {
            if (empty($client->client_code)) {
                $client->client_code = static::generateClientCode();
            }
        });
    }

    protected static function generateClientCode(): string
    {
        $prefix = 'CLT';
        $lastClient = static::withTrashed()
            ->where('client_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastClient) {
            $lastNumber = (int) substr($lastClient->client_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
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

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    public function scopeByPartner($query, $partnerId)
    {
        return $query->where('partner_id', $partnerId);
    }

    public function scopeWithOutstanding($query)
    {
        return $query->whereHas('invoices', function($q) {
            $q->where('payment_status', '!=', Invoice::PAYMENT_STATUS_PAID);
        });
    }

    // Accessors
    public function getTotalOutstandingAttribute(): float
    {
        return $this->invoices()
            ->where('invoice_type', Invoice::TYPE_AR)
            ->where('payment_status', '!=', Invoice::PAYMENT_STATUS_PAID)
            ->sum('outstanding_amount');
    }

    public function getSiteCountAttribute(): int
    {
        return $this->sites()->count();
    }

    public function getActiveJobsCountAttribute(): int
    {
        return $this->jobOrders()
            ->whereIn('status', [
                JobOrder::STATUS_PENDING_ASSIGNMENT,
                JobOrder::STATUS_ASSIGNED,
                JobOrder::STATUS_IN_PROGRESS,
                JobOrder::STATUS_PENDING_CONFIRMATION
            ])
            ->count();
    }

    public function getFullBillingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->billing_postcode . ' ' . $this->billing_city,
            $this->billing_state,
            $this->billing_country,
        ]);

        return implode(', ', $parts);
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
}
