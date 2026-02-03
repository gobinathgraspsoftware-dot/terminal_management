<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class InventorySerial extends Model
{
    use HasFactory, SoftDeletes;

    // =========================================================================
    // STATUS CONSTANTS
    // =========================================================================
    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_ISSUED_TO_TECH = 'issued_to_tech';
    const STATUS_INSTALLED = 'installed';
    const STATUS_UNDER_SERVICE = 'under_service';
    const STATUS_RETURNED_TO_VENDOR = 'returned_to_vendor';
    const STATUS_WASTED = 'wasted';
    const STATUS_RESERVED = 'reserved';

    // =========================================================================
    // LOCATION TYPE CONSTANTS
    // =========================================================================
    const LOCATION_TYPE_DEPOT = 'depot';
    const LOCATION_TYPE_TECHNICIAN = 'technician';
    const LOCATION_TYPE_SITE = 'site';
    const LOCATION_TYPE_VENDOR = 'vendor';

    // =========================================================================
    // STATUS LABELS & BADGES
    // =========================================================================
    const STATUS_OPTIONS = [
        self::STATUS_IN_STOCK           => 'In Stock',
        self::STATUS_ISSUED_TO_TECH     => 'Issued to Technician',
        self::STATUS_INSTALLED           => 'Installed',
        self::STATUS_UNDER_SERVICE      => 'Under Service',
        self::STATUS_RETURNED_TO_VENDOR => 'Returned to Vendor',
        self::STATUS_WASTED             => 'Wasted',
        self::STATUS_RESERVED           => 'Reserved',
    ];

    const STATUS_BADGES = [
        self::STATUS_IN_STOCK           => 'success',
        self::STATUS_ISSUED_TO_TECH     => 'info',
        self::STATUS_INSTALLED           => 'primary',
        self::STATUS_UNDER_SERVICE      => 'warning',
        self::STATUS_RETURNED_TO_VENDOR => 'secondary',
        self::STATUS_WASTED             => 'danger',
        self::STATUS_RESERVED           => 'dark',
    ];

    const LOCATION_TYPE_OPTIONS = [
        self::LOCATION_TYPE_DEPOT      => 'Depot',
        self::LOCATION_TYPE_TECHNICIAN => 'Technician',
        self::LOCATION_TYPE_SITE       => 'Site',
        self::LOCATION_TYPE_VENDOR     => 'Vendor',
    ];

    // Statuses that indicate the item is trackable / not deletable
    const TRACKED_STATUSES = [
        self::STATUS_ISSUED_TO_TECH,
        self::STATUS_INSTALLED,
        self::STATUS_UNDER_SERVICE,
    ];

    // =========================================================================
    // FILLABLE
    // =========================================================================
    protected $fillable = [
        'serial_no', 'model_id', 'hardware_type', 'device_type', 'telco',
        'sim_quota', 'current_status', 'current_location_type', 'current_location_id',
        'grn_id', 'grn_date', 'po_id', 'warranty_start', 'warranty_end',
        'purchase_price', 'remarks', 'created_by', 'updated_by',
    ];

    // =========================================================================
    // CASTS
    // =========================================================================
    protected function casts(): array
    {
        return [
            'grn_date'       => 'date',
            'warranty_start' => 'date',
            'warranty_end'   => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    // =========================================================================
    // BOOT — Prevent deletion of tracked items
    // =========================================================================
    protected static function boot()
    {
        parent::boot();

        // Prevent deletion of items that are currently tracked (issued/installed/under service)
        static::deleting(function ($serial) {
            if (in_array($serial->current_status, self::TRACKED_STATUSES)) {
                throw new \Exception(
                    "Cannot delete serial [{$serial->serial_no}]. " .
                    "Current status is [{$serial->current_status}]. " .
                    "Item must be returned to stock or marked as wasted before deletion."
                );
            }

            // Check if serial has any stock ledger entries
            if ($serial->stockLedgers()->exists()) {
                // Soft delete only — hard delete prevented
                if (!$serial->isForceDeleting()) {
                    return true;
                }
                throw new \Exception(
                    "Cannot permanently delete serial [{$serial->serial_no}]. " .
                    "It has stock movement history."
                );
            }
        });

        // Auto-set created_by and updated_by
        static::creating(function ($serial) {
            if (auth()->check()) {
                $serial->created_by = $serial->created_by ?? auth()->id();
                $serial->updated_by = auth()->id();
            }
        });

        static::updating(function ($serial) {
            if (auth()->check()) {
                $serial->updated_by = auth()->id();
            }
        });
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Terminal model this serial belongs to
     */
    public function terminalModel()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    /**
     * Alias for backward compatibility
     */
    public function model()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    /**
     * GRN that received this serial
     */
    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    /**
     * Purchase order this serial was procured under
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    /**
     * Polymorphic current location (depot/user/site/vendor)
     */
    public function currentLocation()
    {
        return $this->morphTo(__FUNCTION__, 'current_location_type', 'current_location_id');
    }

    /**
     * Get the actual location model based on location type
     */
    public function getLocationModel()
    {
        return match ($this->current_location_type) {
            self::LOCATION_TYPE_DEPOT      => Depot::find($this->current_location_id),
            self::LOCATION_TYPE_TECHNICIAN => User::find($this->current_location_id),
            self::LOCATION_TYPE_SITE       => Site::find($this->current_location_id),
            self::LOCATION_TYPE_VENDOR     => Vendor::find($this->current_location_id),
            default => null,
        };
    }

    /**
     * Stock ledger entries for this serial
     */
    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class, 'serial_id');
    }

    /**
     * Site asset record if installed
     */
    public function siteAsset()
    {
        return $this->hasOne(SiteAsset::class, 'serial_id');
    }

    /**
     * Active site asset (currently installed)
     */
    public function activeSiteAsset()
    {
        return $this->hasOne(SiteAsset::class, 'serial_id')
                     ->where('status', SiteAsset::STATUS_ACTIVE);
    }

    /**
     * All site assets (installation history)
     */
    public function siteAssets()
    {
        return $this->hasMany(SiteAsset::class, 'serial_id');
    }

    /**
     * GRN serial record
     */
    public function grnSerial()
    {
        return $this->hasOne(GrnSerial::class, 'serial_id');
    }

    /**
     * Audit trail for this serial
     */
    public function auditTrails()
    {
        return $this->morphMany(AuditTrail::class, 'auditable');
    }

    /**
     * Created by user
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updated by user
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    /**
     * Filter by status at a specific depot
     */
    public function scopeByStatusAtDepot($query, string $status, int $depotId)
    {
        return $query->where('current_status', $status)
                     ->where('current_location_type', self::LOCATION_TYPE_DEPOT)
                     ->where('current_location_id', $depotId);
    }

    /**
     * Filter serials held by a specific technician
     */
    public function scopeWithTechnician($query, int $technicianId)
    {
        return $query->where('current_location_type', self::LOCATION_TYPE_TECHNICIAN)
                     ->where('current_location_id', $technicianId);
    }

    /**
     * Filter serials installed at a specific site
     */
    public function scopeAtSite($query, int $siteId)
    {
        return $query->where('current_location_type', self::LOCATION_TYPE_SITE)
                     ->where('current_location_id', $siteId);
    }

    /**
     * Available serials: in_stock and not reserved
     */
    public function scopeAvailable($query)
    {
        return $query->where('current_status', self::STATUS_IN_STOCK);
    }

    /**
     * In stock at a specific depot (alias)
     */
    public function scopeInStock($query)
    {
        return $query->where('current_status', self::STATUS_IN_STOCK);
    }

    /**
     * At a specific depot
     */
    public function scopeAtDepot($query, int $depotId)
    {
        return $query->where('current_location_type', self::LOCATION_TYPE_DEPOT)
                     ->where('current_location_id', $depotId);
    }

    /**
     * Filter by terminal model
     */
    public function scopeByModel($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    /**
     * Under warranty
     */
    public function scopeUnderWarranty($query)
    {
        return $query->where('warranty_end', '>=', now()->toDateString());
    }

    /**
     * Warranty expired
     */
    public function scopeWarrantyExpired($query)
    {
        return $query->where('warranty_end', '<', now()->toDateString())
                     ->whereNotNull('warranty_end');
    }

    /**
     * Scope for team-based data filtering (supervisor)
     */
    public function scopeForTeam($query, $teamTechnicianIds)
    {
        return $query->where(function ($q) use ($teamTechnicianIds) {
            $q->where(function ($sub) use ($teamTechnicianIds) {
                $sub->where('current_location_type', self::LOCATION_TYPE_TECHNICIAN)
                    ->whereIn('current_location_id', $teamTechnicianIds);
            });
        });
    }

    /**
     * Search scope for quick lookup
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('serial_no', 'LIKE', "%{$term}%")
              ->orWhere('hardware_type', 'LIKE', "%{$term}%")
              ->orWhere('device_type', 'LIKE', "%{$term}%")
              ->orWhere('telco', 'LIKE', "%{$term}%")
              ->orWhereHas('terminalModel', function ($mq) use ($term) {
                  $mq->where('model_name', 'LIKE', "%{$term}%")
                     ->orWhere('model_code', 'LIKE', "%{$term}%");
              });
        });
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable location name
     */
    public function getLocationNameAttribute(): string
    {
        $location = $this->getLocationModel();

        if (!$location) {
            return 'Unknown';
        }

        return match ($this->current_location_type) {
            self::LOCATION_TYPE_DEPOT      => $location->depot_name ?? $location->depot_code ?? 'Depot #' . $location->id,
            self::LOCATION_TYPE_TECHNICIAN => $location->name ?? 'Technician #' . $location->id,
            self::LOCATION_TYPE_SITE       => $location->site_name ?? $location->site_code ?? 'Site #' . $location->id,
            self::LOCATION_TYPE_VENDOR     => $location->vendor_name ?? $location->company_name ?? 'Vendor #' . $location->id,
            default => 'Unknown',
        };
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $label = self::STATUS_OPTIONS[$this->current_status] ?? ucfirst(str_replace('_', ' ', $this->current_status));
        $color = self::STATUS_BADGES[$this->current_status] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . e($label) . '</span>';
    }

    /**
     * Check if serial is available (in stock + not reserved)
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->current_status === self::STATUS_IN_STOCK;
    }

    /**
     * Get warranty status
     */
    public function getWarrantyStatusAttribute(): string
    {
        if (!$this->warranty_end) {
            return '<span class="badge bg-secondary">No Warranty</span>';
        }

        $now = Carbon::now();
        $warrantyEnd = Carbon::parse($this->warranty_end);

        if ($warrantyEnd->isPast()) {
            return '<span class="badge bg-danger">Expired</span>';
        }

        $daysLeft = $now->diffInDays($warrantyEnd);

        if ($daysLeft <= 30) {
            return '<span class="badge bg-warning">Expiring (' . $daysLeft . ' days)</span>';
        }

        return '<span class="badge bg-success">Active (' . $daysLeft . ' days left)</span>';
    }

    /**
     * Get warranty status text (non-HTML)
     */
    public function getWarrantyStatusTextAttribute(): string
    {
        if (!$this->warranty_end) {
            return 'No Warranty';
        }

        $warrantyEnd = Carbon::parse($this->warranty_end);

        if ($warrantyEnd->isPast()) {
            return 'Expired';
        }

        $daysLeft = Carbon::now()->diffInDays($warrantyEnd);
        return "Active ({$daysLeft} days left)";
    }

    /**
     * Get location type badge HTML
     */
    public function getLocationTypeBadgeAttribute(): string
    {
        $colors = [
            self::LOCATION_TYPE_DEPOT      => 'primary',
            self::LOCATION_TYPE_TECHNICIAN => 'info',
            self::LOCATION_TYPE_SITE       => 'success',
            self::LOCATION_TYPE_VENDOR     => 'warning',
        ];

        $label = self::LOCATION_TYPE_OPTIONS[$this->current_location_type] ?? 'Unknown';
        $color = $colors[$this->current_location_type] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . e($label) . '</span>';
    }

    /**
     * Get age since GRN
     */
    public function getAgeSinceGrnAttribute(): ?string
    {
        if (!$this->grn_date) {
            return null;
        }

        return Carbon::parse($this->grn_date)->diffForHumans();
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Update serial location and status
     */
    public function updateLocation(string $type, int $id, string $status): bool
    {
        return $this->update([
            'current_location_type' => $type,
            'current_location_id'   => $id,
            'current_status'        => $status,
        ]);
    }

    /**
     * Reserve this serial
     */
    public function reserve(): bool
    {
        if ($this->current_status !== self::STATUS_IN_STOCK) {
            throw new \Exception(
                "Cannot reserve serial [{$this->serial_no}]. Current status is [{$this->current_status}]. Only in_stock items can be reserved."
            );
        }

        return $this->update(['current_status' => self::STATUS_RESERVED]);
    }

    /**
     * Unreserve this serial (return to in_stock)
     */
    public function unreserve(): bool
    {
        if ($this->current_status !== self::STATUS_RESERVED) {
            throw new \Exception(
                "Cannot unreserve serial [{$this->serial_no}]. Current status is [{$this->current_status}]. Only reserved items can be unreserved."
            );
        }

        return $this->update(['current_status' => self::STATUS_IN_STOCK]);
    }

    /**
     * Check if serial can be issued
     */
    public function canBeIssued(): bool
    {
        return in_array($this->current_status, [
            self::STATUS_IN_STOCK,
            self::STATUS_RESERVED,
        ]);
    }

    /**
     * Check if serial can be returned
     */
    public function canBeReturned(): bool
    {
        return in_array($this->current_status, [
            self::STATUS_ISSUED_TO_TECH,
            self::STATUS_UNDER_SERVICE,
        ]);
    }

    /**
     * Get complete movement history
     */
    public function getMovementHistory()
    {
        return $this->stockLedgers()
                     ->with(['model', 'createdBy'])
                     ->orderBy('transaction_date', 'desc')
                     ->orderBy('created_at', 'desc')
                     ->get();
    }

    /**
     * Get installation history
     */
    public function getInstallationHistory()
    {
        return $this->siteAssets()
                     ->with(['site', 'installedBy', 'events'])
                     ->orderBy('installed_date', 'desc')
                     ->get();
    }

    /**
     * Get service history via asset events
     */
    public function getServiceHistory()
    {
        return AssetEvent::whereHas('siteAsset', function ($q) {
            $q->where('serial_id', $this->id);
        })
        ->with(['siteAsset.site', 'jobOrder', 'performedBy'])
        ->orderBy('event_date', 'desc')
        ->get();
    }

    /**
     * Get all statuses as options for dropdown
     */
    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    /**
     * Get all location types as options for dropdown
     */
    public static function getLocationTypeOptions(): array
    {
        return self::LOCATION_TYPE_OPTIONS;
    }
}
