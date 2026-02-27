<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    const LOCATION_TYPE_DEPOT = 'depot';
    const LOCATION_TYPE_TECHNICIAN = 'technician';

    protected $fillable = [
        'model_id',
        'location_type',
        'location_id',
        'quantity_on_hand',
        'quantity_reserved',
        'last_movement_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'last_movement_date' => 'date',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function model()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    /**
     * Polymorphic relationship to location.
     */
    public function location()
    {
        return $this->morphTo(__FUNCTION__, 'location_type', 'location_id');
    }

    /**
     * Direct relationship to Depot (for depot-type balances).
     * FIX: Required by summary views that reference $item->depot.
     */
    public function depot()
    {
        return $this->belongsTo(Depot::class, 'location_id');
    }

    /**
     * Direct relationship to User/Technician (for technician-type balances).
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'location_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get available quantity (on hand - reserved).
     */
    public function getQuantityAvailableAttribute()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    /**
     * Get location name for display.
     */
    public function getLocationNameAttribute(): string
    {
        if (!$this->location_type || !$this->location_id) {
            return '-';
        }

        $model = match ($this->location_type) {
            'depot' => Depot::find($this->location_id),
            'technician' => User::find($this->location_id),
            default => null,
        };

        if (!$model) {
            return ucfirst($this->location_type) . " #{$this->location_id}";
        }

        return match ($this->location_type) {
            'depot' => $model->depot_name ?? $model->depot_code ?? "Depot #{$this->location_id}",
            'technician' => $model->name ?? "Technician #{$this->location_id}",
            default => ucfirst($this->location_type) . " #{$this->location_id}",
        };
    }

    /**
     * Check if stock is low.
     */
    public function getIsLowStockAttribute(): bool
    {
        $minStock = 5;
        return $this->quantity_available <= $minStock && $this->quantity_available > 0;
    }

    /**
     * Check if out of stock.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_available <= 0;
    }

    /**
     * Get stock status badge.
     */
    public function getStockStatusBadgeAttribute(): string
    {
        if ($this->is_out_of_stock) {
            return '<span class="badge bg-danger">Out of Stock</span>';
        }

        if ($this->is_low_stock) {
            return '<span class="badge bg-warning text-dark">Low Stock</span>';
        }

        return '<span class="badge bg-success">Normal</span>';
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeDepots($query)
    {
        return $query->where('location_type', self::LOCATION_TYPE_DEPOT);
    }

    public function scopeTechnicians($query)
    {
        return $query->where('location_type', self::LOCATION_TYPE_TECHNICIAN);
    }

    public function scopeForLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
                    ->where('location_id', $locationId);
    }

    public function scopeForModel($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity_on_hand', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 5')
                    ->whereRaw('(quantity_on_hand - quantity_reserved) > 0');
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity_on_hand - quantity_reserved) <= 0');
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    public static function getLocationTypeOptions(): array
    {
        return [
            self::LOCATION_TYPE_DEPOT => 'Depot',
            self::LOCATION_TYPE_TECHNICIAN => 'Technician',
        ];
    }
}
