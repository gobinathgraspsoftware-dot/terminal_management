<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    protected $table = 'stock_balances';

    const HOLDER_WAREHOUSE = 'warehouse';
    const HOLDER_TECHNICIAN = 'technician';

    protected $fillable = [
        'inventory_item_id',
        'holder_type',
        'holder_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    // ══════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * Holder user (technician). NULL for warehouse.
     */
    public function holder()
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    // ══════════════════════════════════════
    // SCOPES
    // ══════════════════════════════════════

    public function scopeWarehouse($query)
    {
        return $query->where('holder_type', self::HOLDER_WAREHOUSE)
                     ->whereNull('holder_id');
    }

    public function scopeForTechnician($query, int $technicianId)
    {
        return $query->where('holder_type', self::HOLDER_TECHNICIAN)
                     ->where('holder_id', $technicianId);
    }

    // ══════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════

    public function isWarehouse(): bool
    {
        return $this->holder_type === self::HOLDER_WAREHOUSE;
    }

    /**
     * Get or create a balance record for item + location.
     */
    public static function getOrCreate(int $itemId, string $holderType = 'warehouse', ?int $holderId = null): self
    {
        return static::firstOrCreate([
            'inventory_item_id' => $itemId,
            'holder_type' => $holderType,
            'holder_id' => $holderId,
        ], [
            'quantity' => 0,
        ]);
    }

    /**
     * Get warehouse balance for an item.
     */
    public static function warehouseBalance(int $itemId): int
    {
        return (int) static::where('inventory_item_id', $itemId)
            ->warehouse()
            ->value('quantity') ?? 0;
    }
}
