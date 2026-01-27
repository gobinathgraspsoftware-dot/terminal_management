<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const TYPE_SUPPLIER = 'supplier';
    const TYPE_SUBCON = 'subcon';
    const TYPE_COURIER = 'courier';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'vendor_code',
        'vendor_name',
        'vendor_type',
        'company_name',
        'registration_no',
        'tax_id',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'pic_name',
        'pic_email',
        'pic_phone',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'payment_terms',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $vendor->vendor_code = static::generateVendorCode();
            }
        });
    }

    protected static function generateVendorCode(): string
    {
        $prefix = 'VND';
        $lastVendor = static::withTrashed()
            ->where('vendor_code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVendor) {
            $lastNumber = (int) substr($lastVendor->vendor_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
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

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
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
}
