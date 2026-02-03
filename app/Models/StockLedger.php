<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory;

    // =========================================================================
    // TRANSACTION TYPE CONSTANTS
    // =========================================================================
    const TYPE_GRN_IN           = 'grn_in';
    const TYPE_ISSUE_TO_TECH    = 'issue_to_tech';
    const TYPE_RETURN_FROM_TECH = 'return_from_tech';
    const TYPE_TRANSFER         = 'transfer';
    const TYPE_INSTALL          = 'install';
    const TYPE_REPLACEMENT_OUT  = 'replacement_out';
    const TYPE_REPLACEMENT_IN   = 'replacement_in';
    const TYPE_WASTAGE          = 'wastage';
    const TYPE_RETURN_TO_VENDOR = 'return_to_vendor';
    const TYPE_ADJUSTMENT       = 'adjustment';

    // =========================================================================
    // MOVEMENT TYPE METADATA
    // =========================================================================
    const TYPE_OPTIONS = [
        self::TYPE_GRN_IN           => 'GRN In (Received)',
        self::TYPE_ISSUE_TO_TECH    => 'Issue to Technician',
        self::TYPE_RETURN_FROM_TECH => 'Return from Technician',
        self::TYPE_TRANSFER         => 'Transfer (Depot to Depot)',
        self::TYPE_INSTALL          => 'Install to Site',
        self::TYPE_REPLACEMENT_OUT  => 'Replacement Out',
        self::TYPE_REPLACEMENT_IN   => 'Replacement In',
        self::TYPE_WASTAGE          => 'Wastage / Scrap',
        self::TYPE_RETURN_TO_VENDOR => 'Return to Vendor',
        self::TYPE_ADJUSTMENT       => 'Stock Adjustment',
    ];

    const TYPE_ICONS = [
        self::TYPE_GRN_IN           => 'bi-box-arrow-in-down',
        self::TYPE_ISSUE_TO_TECH    => 'bi-person-plus',
        self::TYPE_RETURN_FROM_TECH => 'bi-person-dash',
        self::TYPE_TRANSFER         => 'bi-arrow-left-right',
        self::TYPE_INSTALL          => 'bi-building-check',
        self::TYPE_REPLACEMENT_OUT  => 'bi-arrow-up-circle',
        self::TYPE_REPLACEMENT_IN   => 'bi-arrow-down-circle',
        self::TYPE_WASTAGE          => 'bi-trash',
        self::TYPE_RETURN_TO_VENDOR => 'bi-truck',
        self::TYPE_ADJUSTMENT       => 'bi-sliders',
    ];

    const TYPE_COLORS = [
        self::TYPE_GRN_IN           => 'success',
        self::TYPE_ISSUE_TO_TECH    => 'info',
        self::TYPE_RETURN_FROM_TECH => 'primary',
        self::TYPE_TRANSFER         => 'warning',
        self::TYPE_INSTALL          => 'success',
        self::TYPE_REPLACEMENT_OUT  => 'danger',
        self::TYPE_REPLACEMENT_IN   => 'success',
        self::TYPE_WASTAGE          => 'danger',
        self::TYPE_RETURN_TO_VENDOR => 'secondary',
        self::TYPE_ADJUSTMENT       => 'dark',
    ];

    /**
     * Reversible movement types and their reversal counterparts.
     */
    const REVERSIBLE_TYPES = [
        self::TYPE_ISSUE_TO_TECH    => self::TYPE_RETURN_FROM_TECH,
        self::TYPE_RETURN_FROM_TECH => self::TYPE_ISSUE_TO_TECH,
        self::TYPE_TRANSFER         => self::TYPE_TRANSFER,
        self::TYPE_INSTALL          => self::TYPE_REPLACEMENT_OUT,
        self::TYPE_REPLACEMENT_OUT  => self::TYPE_REPLACEMENT_IN,
        self::TYPE_REPLACEMENT_IN   => self::TYPE_REPLACEMENT_OUT,
        self::TYPE_GRN_IN           => self::TYPE_RETURN_TO_VENDOR,
    ];

    // =========================================================================
    // TABLE & FILLABLE
    // =========================================================================
    protected $table = 'stock_ledger';

    protected $fillable = [
        'transaction_date', 'transaction_no', 'transaction_type', 'reference_type',
        'reference_id', 'serial_id', 'serial_no', 'model_id', 'quantity',
        'from_location_type', 'from_location_id', 'to_location_type', 'to_location_id',
        'unit_cost', 'total_cost', 'remarks', 'created_by',
        // Reversal fields
        'is_reversed', 'reversed_by_id', 'reversal_of_id',
        'reversed_at', 'reversed_by_user_id',
    ];

    // =========================================================================
    // CASTS
    // =========================================================================
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity'         => 'decimal:4',
            'unit_cost'        => 'decimal:2',
            'total_cost'       => 'decimal:2',
            'is_reversed'      => 'boolean',
            'reversed_at'      => 'datetime',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function serial()
    {
        return $this->belongsTo(InventorySerial::class);
    }

    public function model()
    {
        return $this->belongsTo(TerminalModel::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The ledger entry that reversed this movement.
     */
    public function reversedByEntry()
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    /**
     * If this is a reversal, the original movement it reverses.
     */
    public function reversalOfEntry()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * The user who performed the reversal.
     */
    public function reversedByUser()
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeBySerial($query, int $serialId)
    {
        return $query->where('serial_id', $serialId);
    }

    public function scopeBySerialNo($query, string $serialNo)
    {
        return $query->where('serial_no', $serialNo);
    }

    public function scopeForSerial($query, int $serialId, ?string $serialNo = null)
    {
        return $query->where(function ($q) use ($serialId, $serialNo) {
            $q->where('serial_id', $serialId);
            if ($serialNo) {
                $q->orWhere('serial_no', $serialNo);
            }
        });
    }

    public function scopeByLocation($query, string $locationType, int $locationId)
    {
        return $query->where(function ($q) use ($locationType, $locationId) {
            $q->where(function ($sub) use ($locationType, $locationId) {
                $sub->where('from_location_type', $locationType)
                    ->where('from_location_id', $locationId);
            })->orWhere(function ($sub) use ($locationType, $locationId) {
                $sub->where('to_location_type', $locationType)
                    ->where('to_location_id', $locationId);
            });
        });
    }

    public function scopeNotReversed($query)
    {
        return $query->where('is_reversed', false);
    }

    public function scopeReversalsOnly($query)
    {
        return $query->whereNotNull('reversal_of_id');
    }

    public function scopeActiveMovements($query)
    {
        return $query->where('is_reversed', false)->whereNull('reversal_of_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable movement type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->transaction_type] ?? ucfirst(str_replace('_', ' ', $this->transaction_type));
    }

    /**
     * Get icon class for the movement type.
     */
    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->transaction_type] ?? 'bi-arrow-left-right';
    }

    /**
     * Get color class for the movement type.
     */
    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->transaction_type] ?? 'secondary';
    }

    /**
     * Get badge HTML for the movement type.
     */
    public function getTypeBadgeAttribute(): string
    {
        $label = $this->type_label;
        $color = $this->type_color;
        $icon  = $this->type_icon;

        return '<span class="badge bg-' . $color . '"><i class="bi ' . $icon . ' me-1"></i>' . e($label) . '</span>';
    }

    /**
     * Get human-readable from location name.
     */
    public function getFromLocationNameAttribute(): string
    {
        return $this->resolveLocationName($this->from_location_type, $this->from_location_id);
    }

    /**
     * Get human-readable to location name.
     */
    public function getToLocationNameAttribute(): string
    {
        return $this->resolveLocationName($this->to_location_type, $this->to_location_id);
    }

    /**
     * Get the reference label for display.
     */
    public function getReferenceLabelAttribute(): string
    {
        if (!$this->reference_type) {
            return '-';
        }

        $typeMap = [
            'grn'            => 'GRN',
            'stock_issue'    => 'Stock Issue',
            'stock_transfer' => 'Transfer',
            'job'            => 'Job Order',
            'delivery_order' => 'Delivery Order',
            'do'             => 'Delivery Order',
            'adjustment'     => 'Adjustment',
        ];

        $prefix = $typeMap[$this->reference_type] ?? ucfirst(str_replace('_', ' ', $this->reference_type));

        return $prefix . ($this->reference_id ? " #{$this->reference_id}" : '');
    }

    /**
     * Get the URL for the reference document (clickable link).
     */
    public function getReferenceUrlAttribute(): ?string
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $role = auth()->user()?->roles->first()?->name ?? 'admin';

        try {
            return match ($this->reference_type) {
                'grn'            => route("{$role}.grns.show", $this->reference_id, false),
                'stock_issue'    => route("{$role}.stock-issues.show", $this->reference_id, false),
                'stock_transfer' => route("{$role}.stock-transfers.show", $this->reference_id, false),
                'job'            => route("{$role}.jobs.show", $this->reference_id, false),
                'delivery_order',
                'do'             => route("{$role}.delivery-orders.show", $this->reference_id, false),
                default          => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if this movement is reversible.
     */
    public function getIsReversibleAttribute(): bool
    {
        return !$this->is_reversed
            && is_null($this->reversal_of_id)
            && array_key_exists($this->transaction_type, self::REVERSIBLE_TYPES);
    }

    /**
     * Get reversal status badge.
     */
    public function getReversalStatusBadgeAttribute(): string
    {
        if ($this->is_reversed) {
            return '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Reversed</span>';
        }

        if ($this->reversal_of_id) {
            return '<span class="badge bg-warning text-dark"><i class="bi bi-arrow-counterclockwise me-1"></i>Reversal</span>';
        }

        return '';
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Resolve location name from type and ID.
     */
    protected function resolveLocationName(?string $type, ?int $id): string
    {
        if (!$type || !$id) {
            return '-';
        }

        $model = match ($type) {
            'depot'      => Depot::find($id),
            'technician' => User::find($id),
            'site'       => Site::find($id),
            'vendor'     => Vendor::find($id),
            default      => null,
        };

        if (!$model) {
            return ucfirst($type) . " #{$id}";
        }

        return match ($type) {
            'depot'      => $model->depot_name ?? $model->depot_code ?? "Depot #{$id}",
            'technician' => $model->name ?? "Technician #{$id}",
            'site'       => $model->site_name ?? $model->site_code ?? "Site #{$id}",
            'vendor'     => $model->vendor_name ?? $model->company_name ?? "Vendor #{$id}",
            default      => ucfirst($type) . " #{$id}",
        };
    }

    /**
     * Get all movement type options for dropdown.
     */
    public static function getTypeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }
}
