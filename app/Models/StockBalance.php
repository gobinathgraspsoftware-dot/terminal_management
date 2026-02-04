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
     * Polymorphic relationship to location
     * This allows querying the actual depot or user record
     */
    public function location()
    {
        return $this->morphTo(__FUNCTION__, 'location_type', 'location_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get available quantity (on hand - reserved)
     * Note: This is also a generated column in MySQL, but we define it here for clarity
     */
    public function getQuantityAvailableAttribute()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }

    /**
     * Get location name for display
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
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        $minStock = $this->model->min_stock_level ?? 5;
        return $this->quantity_available <= $minStock && $this->quantity_available > 0;
    }

    /**
     * Check if out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_available <= 0;
    }

    /**
     * Get stock status badge
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

    /**
     * Scope to depot locations only
     */
    public function scopeDepots($query)
    {
        return $query->where('location_type', self::LOCATION_TYPE_DEPOT);
    }

    /**
     * Scope to technician locations only
     */
    public function scopeTechnicians($query)
    {
        return $query->where('location_type', self::LOCATION_TYPE_TECHNICIAN);
    }

    /**
     * Scope to specific location
     */
    public function scopeForLocation($query, string $locationType, int $locationId)
    {
        return $query->where('location_type', $locationType)
                    ->where('location_id', $locationId);
    }

    /**
     * Scope to specific model
     */
    public function scopeForModel($query, int $modelId)
    {
        return $query->where('model_id', $modelId);
    }

    /**
     * Scope to items with stock
     */
    public function scopeWithStock($query)
    {
        return $query->where('quantity_on_hand', '>', 0);
    }

    /**
     * Scope to low stock items
     */
    public function scopeLowStock($query)
    {
        // This is a simplified version - actual implementation would need to join with models table
        return $query->where('quantity_available', '<=', 5)
                    ->where('quantity_available', '>', 0);
    }

    /**
     * Scope to out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_available', '<=', 0);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get all location type options
     */
    public static function getLocationTypeOptions(): array
    {
        return [
            self::LOCATION_TYPE_DEPOT => 'Depot',
            self::LOCATION_TYPE_TECHNICIAN => 'Technician',
        ];
    }
}
