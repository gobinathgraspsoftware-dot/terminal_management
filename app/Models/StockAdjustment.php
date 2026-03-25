<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustments';

    const TYPE_INCREASE = 'increase';
    const TYPE_DECREASE = 'decrease';

    protected $fillable = [
        'adjustment_no',
        'inventory_item_id',
        'stock_movement_id',
        'adjustment_type',
        'old_quantity',
        'new_quantity',
        'difference',
        'old_value',
        'new_value',
        'reason',
        'remarks',
        'adjusted_by',
        'adjusted_at',
    ];

    protected function casts(): array
    {
        return [
            'old_quantity' => 'integer',
            'new_quantity' => 'integer',
            'difference' => 'integer',
            'adjusted_at' => 'datetime',
        ];
    }

    // ══════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    // ══════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════

    public function isIncrease(): bool
    {
        return $this->adjustment_type === self::TYPE_INCREASE;
    }

    public function getTypeBadge(): string
    {
        return $this->isIncrease()
            ? '<span class="badge bg-success">Increase</span>'
            : '<span class="badge bg-danger">Decrease</span>';
    }
}
