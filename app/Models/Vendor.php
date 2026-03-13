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

    // Legacy constants kept for backward compatibility
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
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $vendor->vendor_code = static::generateVendorCode();
            }

            // Sync legacy vendor_type from vendor_type_id if set
            if ($vendor->vendor_type_id && empty($vendor->vendor_type)) {
                $vendorType = VendorType::find($vendor->vendor_type_id);
                if ($vendorType) {
                    $vendor->vendor_type = strtolower(str_replace(['-', ' '], '', $vendorType->title));
                }
            }
        });

        static::updating(function ($vendor) {
            // Sync legacy vendor_type from vendor_type_id if changed
            if ($vendor->isDirty('vendor_type_id') && $vendor->vendor_type_id) {
                $vendorType = VendorType::find($vendor->vendor_type_id);
                if ($vendorType) {
                    $vendor->vendor_type = strtolower(str_replace(['-', ' '], '', $vendorType->title));
                }
            }
        });
    }

    /**
     * Generate next sequential vendor code (fallback)
     */
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
     * Suggest vendor codes based on vendor name (Gmail-style suggestions)
     *
     * @param string $vendorName
     * @return array  Array of available code suggestions
     */
    public static function suggestVendorCodes(string $vendorName): array
    {
        $vendorName = trim($vendorName);
        if (empty($vendorName)) {
            return [];
        }

        $candidates = [];
        $words = preg_split('/[\s\-_]+/', strtoupper($vendorName));
        $words = array_filter($words, fn($w) => strlen($w) > 0);
        $words = array_values($words);

        // Strategy 1: First 3 chars of name (e.g., MAY for Maybank)
        if (strlen($vendorName) >= 3) {
            $candidates[] = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $vendorName), 0, 3));
        }

        // Strategy 2: First char + next two consonants (e.g., MYB for Maybank)
        $consonantCode = static::extractConsonantCode($vendorName);
        if ($consonantCode && !in_array($consonantCode, $candidates)) {
            $candidates[] = $consonantCode;
        }

        // Strategy 3: First 4 chars (e.g., MAYB)
        if (strlen($vendorName) >= 4) {
            $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $vendorName), 0, 4));
            if (!in_array($code, $candidates)) {
                $candidates[] = $code;
            }
        }

        // Strategy 4: Acronym from words (e.g., MB for May Bank, HLB for Hong Leong Bank)
        if (count($words) > 1) {
            $acronym = implode('', array_map(fn($w) => substr($w, 0, 1), $words));
            if (strlen($acronym) >= 2 && !in_array($acronym, $candidates)) {
                $candidates[] = $acronym;
            }
        }

        // Strategy 5: First 2 chars + sequential number (e.g., MA01)
        if (strlen($vendorName) >= 2) {
            $prefix2 = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $vendorName), 0, 2));
            for ($i = 1; $i <= 5; $i++) {
                $code = $prefix2 . str_pad($i, 2, '0', STR_PAD_LEFT);
                if (!in_array($code, $candidates)) {
                    $candidates[] = $code;
                    break;
                }
            }
        }

        // Filter out already-used codes
        if (empty($candidates)) {
            return [];
        }

        $existingCodes = static::withTrashed()
            ->whereIn('vendor_code', $candidates)
            ->pluck('vendor_code')
            ->toArray();

        $available = array_values(array_filter($candidates, fn($c) => !in_array($c, $existingCodes)));

        // If all taken, append numbers to first candidate
        if (empty($available) && !empty($candidates)) {
            $base = $candidates[0];
            for ($i = 1; $i <= 10; $i++) {
                $code = $base . $i;
                if (!static::withTrashed()->where('vendor_code', $code)->exists()) {
                    $available[] = $code;
                    if (count($available) >= 3) break;
                }
            }
        }

        return array_slice($available, 0, 5);
    }

    /**
     * Extract consonant-based code from name
     * E.g., "Maybank" → "MYB", "Samsung" → "SMS"
     */
    private static function extractConsonantCode(string $name): ?string
    {
        $clean = strtoupper(preg_replace('/[^a-zA-Z]/', '', $name));
        if (strlen($clean) < 2) return null;

        $vowels = ['A', 'E', 'I', 'O', 'U'];
        $code = $clean[0]; // Always start with first letter

        for ($i = 1; $i < strlen($clean) && strlen($code) < 3; $i++) {
            if (!in_array($clean[$i], $vowels)) {
                $code .= $clean[$i];
            }
        }

        // If still short, add vowels
        if (strlen($code) < 3) {
            for ($i = 1; $i < strlen($clean) && strlen($code) < 3; $i++) {
                if (in_array($clean[$i], $vowels) && strpos($code, $clean[$i]) === false) {
                    $code .= $clean[$i];
                }
            }
        }

        return strlen($code) >= 2 ? $code : null;
    }

    /**
     * Check if vendor code is available
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
    // RELATIONSHIPS
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

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }

    public function scopeByTypeId($query, $typeId)
    {
        return $query->where('vendor_type_id', $typeId);
    }

    // ==========================================
    // ACCESSORS
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

    /**
     * Get vendor type display name (from FK relationship)
     */
    public function getVendorTypeNameAttribute(): string
    {
        if ($this->relationLoaded('vendorType') && $this->vendorType) {
            return $this->vendorType->title;
        }

        if ($this->vendor_type_id) {
            return VendorType::find($this->vendor_type_id)?->title ?? ucfirst($this->vendor_type ?? 'Unknown');
        }

        return ucfirst($this->vendor_type ?? 'Unknown');
    }
}
