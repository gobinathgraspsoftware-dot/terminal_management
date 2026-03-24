<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

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

    // =========================================================
    // Relationships
    // =========================================================

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function holder()
    {
        if ($this->holder_type === 'technician' && $this->holder_id) {
            return $this->belongsTo(User::class, 'holder_id');
        }
        return null;
    }

    // =========================================================
    // Scopes
    // =========================================================

    public function scopeWarehouse($query)
    {
        return $query->where('holder_type', 'warehouse')->whereNull('holder_id');
    }

    public function scopeTechnician($query, $technicianId = null)
    {
        $q = $query->where('holder_type', 'technician');
        if ($technicianId) {
            $q->where('holder_id', $technicianId);
        }
        return $q;
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Get or create a balance record for the given item + holder.
     */
    public static function getOrCreate(int $itemId, string $holderType, ?int $holderId = null): self
    {
        return self::firstOrCreate(
            [
                'inventory_item_id' => $itemId,
                'holder_type'       => $holderType,
                'holder_id'         => $holderType === 'warehouse' ? null : $holderId,
            ],
            ['quantity' => 0]
        );
    }
}
