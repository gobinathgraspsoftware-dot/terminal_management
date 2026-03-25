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
        'vendor_type_id',
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
            'vendor_type_id' => 'integer',
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

        // Auto-sync legacy vendor_type enum from vendor_type_id FK
        static::saving(function ($vendor) {
            if ($vendor->vendor_type_id) {
                $vendorType = VendorType::find($vendor->vendor_type_id);
                if ($vendorType) {
                    $slug = strtolower($vendorType->title);
                    $typeMap = [
                        'supplier' => self::TYPE_SUPPLIER,
                        'sub-contractor' => self::TYPE_SUBCON,
                        'subcontractor' => self::TYPE_SUBCON,
                        'subcon' => self::TYPE_SUBCON,
                        'courier' => self::TYPE_COURIER,
                    ];
                    $vendor->vendor_type = $typeMap[$slug] ?? self::TYPE_OTHER;
                }
            }
        });
    }

    public static function generateVendorCode(): string
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

    /**
     * Suggest vendor codes based on name (Gmail-style)
     * Returns up to 5 unique, available codes
     */
    public static function suggestVendorCodes(string $name): array
    {
        $name = trim($name);
        if (empty($name)) {
            return [];
        }

        $suggestions = [];
        $cleanName = strtoupper(preg_replace('/[^a-zA-Z0-9\s]/', '', $name));
        $words = preg_split('/\s+/', $cleanName);

        // Strategy 1: First 3 chars of name
        if (strlen($cleanName) >= 3) {
            $suggestions[] = substr(str_replace(' ', '', $cleanName), 0, 3);
        }

        // Strategy 2: Consonant extraction
        $consonants = strtoupper(preg_replace('/[AEIOU\s]/i', '', $cleanName));
        if (strlen($consonants) >= 3) {
            $suggestions[] = substr($consonants, 0, 3);
        }

        // Strategy 3: First 4 chars
        if (strlen($cleanName) >= 4) {
            $suggestions[] = substr(str_replace(' ', '', $cleanName), 0, 4);
        }

        // Strategy 4: Acronym from words
        if (count($words) >= 2) {
            $acronym = '';
            foreach ($words as $word) {
                if (!empty($word)) {
                    $acronym .= $word[0];
                }
            }
            if (strlen($acronym) >= 2) {
                $suggestions[] = $acronym;
            }
        }

        // Strategy 5: 2-char prefix + sequential number
        $prefix2 = substr(str_replace(' ', '', $cleanName), 0, 2);
        if (strlen($prefix2) >= 2) {
            for ($i = 1; $i <= 5; $i++) {
                $suggestions[] = $prefix2 . str_pad($i, 2, '0', STR_PAD_LEFT);
            }
        }

        // Filter out duplicates and already-used codes
        $unique = [];
        foreach ($suggestions as $code) {
            $code = strtoupper($code);
            if (!in_array($code, $unique) && static::isCodeAvailable($code)) {
                $unique[] = $code;
            }
            if (count($unique) >= 5) {
                break;
            }
        }

        return $unique;
    }

    /**
     * Check if a vendor code is available
     */
    public static function isCodeAvailable(string $code, ?int $excludeId = null): bool
    {
        $query = static::withTrashed()->where('vendor_code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return !$query->exists();
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function vendorType()
    {
        return $this->belongsTo(VendorType::class, 'vendor_type_id');
    }

    public function branches()
    {
        return $this->hasMany(VendorBranch::class);
    }

    public function activeBranches()
    {
        return $this->hasMany(VendorBranch::class)->where('status', 'active');
    }

    public function primaryBranch()
    {
        return $this->hasOne(VendorBranch::class)->where('is_primary', true);
    }

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

    // quotations() relationship removed — Quotation module removed

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }

    public function scopeByTypeId($query, int $typeId)
    {
        return $query->where('vendor_type_id', $typeId);
    }

    // ==========================================
    // Accessors
    // ==========================================

    public function getFullAddressAttribute(): string
    {
        $primaryBranch = $this->relationLoaded('primaryBranch')
            ? $this->primaryBranch
            : $this->primaryBranch()->first();

        if ($primaryBranch) {
            return $primaryBranch->full_address;
        }

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
