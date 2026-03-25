<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_items';

    // ── Item Type Constants ──
    const TYPE_ROUTER = 'router';
    const TYPE_ACCESSORY = 'accessory';

    // ── Accessory Type Constants ──
    const ACCESSORY_SIM_CARD = 'sim_card';
    const ACCESSORY_ANTENNA = 'antenna';

    // ── Status Constants ──
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'item_code',
        'item_name',
        'item_type',
        'accessory_type',
        'description',
        'unit',
        'brand',
        'model',
        'reorder_level',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reorder_level' => 'integer',
        ];
    }

    // ══════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════

    /**
     * Stock balance records for this item.
     */
    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'inventory_item_id');
    }

    /**
     * Stock movements for this item.
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id');
    }

    /**
     * Stock adjustments for this item.
     */
    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'inventory_item_id');
    }

    /**
     * Creator user.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Updater user.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ══════════════════════════════════════
    // SCOPES
    // ══════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeRouters($query)
    {
        return $query->where('item_type', self::TYPE_ROUTER);
    }

    public function scopeAccessories($query)
    {
        return $query->where('item_type', self::TYPE_ACCESSORY);
    }

    public function scopeSimCards($query)
    {
        return $query->where('item_type', self::TYPE_ACCESSORY)
                     ->where('accessory_type', self::ACCESSORY_SIM_CARD);
    }

    public function scopeAntennas($query)
    {
        return $query->where('item_type', self::TYPE_ACCESSORY)
                     ->where('accessory_type', self::ACCESSORY_ANTENNA);
    }

    // ══════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════

    public function isRouter(): bool
    {
        return $this->item_type === self::TYPE_ROUTER;
    }

    public function isAccessory(): bool
    {
        return $this->item_type === self::TYPE_ACCESSORY;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get warehouse stock balance for this item.
     */
    public function getWarehouseStock(): int
    {
        return (int) $this->stockBalances()
            ->where('holder_type', 'warehouse')
            ->whereNull('holder_id')
            ->value('quantity') ?? 0;
    }

    /**
     * Get total stock across all locations.
     */
    public function getTotalStock(): int
    {
        return (int) $this->stockBalances()->sum('quantity');
    }

    /**
     * Check if stock is below reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->getWarehouseStock() <= $this->reorder_level;
    }

    /**
     * Get item type badge HTML.
     */
    public function getTypeBadge(): string
    {
        if ($this->isRouter()) {
            return '<span class="badge bg-primary">Router</span>';
        }

        $label = $this->accessory_type === self::ACCESSORY_SIM_CARD ? 'SIM Card' : 'Antenna';
        return '<span class="badge bg-info">' . $label . '</span>';
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadge(): string
    {
        return $this->isActive()
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
    }

    /**
     * Get available item types.
     */
    public static function getItemTypes(): array
    {
        return [
            self::TYPE_ROUTER => 'Router',
            self::TYPE_ACCESSORY => 'Accessory',
        ];
    }

    /**
     * Get available accessory types.
     */
    public static function getAccessoryTypes(): array
    {
        return [
            self::ACCESSORY_SIM_CARD => 'SIM Card',
            self::ACCESSORY_ANTENNA => 'Antenna',
        ];
    }
}
