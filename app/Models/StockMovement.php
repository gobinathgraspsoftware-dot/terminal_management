<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    // ── Movement Type Constants ──
    const TYPE_STOCK_IN = 'stock_in';
    const TYPE_STOCK_OUT = 'stock_out';
    const TYPE_STOCK_RETURN = 'stock_return';
    const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';

    // ── Item Condition Constants ──
    const CONDITION_GOOD = 'good';
    const CONDITION_FAULTY = 'faulty';
    const CONDITION_DAMAGED = 'damaged';

    protected $fillable = [
        'movement_no',
        'inventory_item_id',
        'movement_type',
        'quantity',
        'from_holder_type',
        'from_holder_id',
        'to_holder_type',
        'to_holder_id',
        'ticket_id',
        'reference_type',
        'reference_id',
        'reason',
        'remarks',
        'item_condition',
        'movement_date',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'movement_date' => 'date',
        ];
    }

    // ══════════════════════════════════════
    // RELATIONSHIPS
    // ══════════════════════════════════════

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * From holder (technician or null for warehouse).
     */
    public function fromHolder()
    {
        return $this->belongsTo(User::class, 'from_holder_id');
    }

    /**
     * To holder (technician or null for warehouse).
     */
    public function toHolder()
    {
        return $this->belongsTo(User::class, 'to_holder_id');
    }

    /**
     * Stock adjustment record (if movement_type = stock_adjustment).
     */
    public function stockAdjustment()
    {
        return $this->hasOne(StockAdjustment::class, 'stock_movement_id');
    }

    // ══════════════════════════════════════
    // SCOPES
    // ══════════════════════════════════════

    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) $query->where('movement_date', '>=', $from);
        if ($to) $query->where('movement_date', '<=', $to);
        return $query;
    }

    public function scopeForItem($query, int $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    // ══════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════

    /**
     * Get movement type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->movement_type) {
            self::TYPE_STOCK_IN => 'Stock In',
            self::TYPE_STOCK_OUT => 'Stock Out',
            self::TYPE_STOCK_RETURN => 'Stock Return',
            self::TYPE_STOCK_ADJUSTMENT => 'Stock Adjustment',
            default => ucfirst(str_replace('_', ' ', $this->movement_type)),
        };
    }

    /**
     * Get movement type badge HTML.
     */
    public function getTypeBadge(): string
    {
        $colors = [
            self::TYPE_STOCK_IN => 'success',
            self::TYPE_STOCK_OUT => 'danger',
            self::TYPE_STOCK_RETURN => 'info',
            self::TYPE_STOCK_ADJUSTMENT => 'warning',
        ];

        $color = $colors[$this->movement_type] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . $this->getTypeLabel() . '</span>';
    }

    /**
     * Get condition badge HTML.
     */
    public function getConditionBadge(): string
    {
        $colors = [
            self::CONDITION_GOOD => 'success',
            self::CONDITION_FAULTY => 'warning',
            self::CONDITION_DAMAGED => 'danger',
        ];

        $color = $colors[$this->item_condition] ?? 'secondary';
        $label = ucfirst($this->item_condition ?? 'N/A');
        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }

    /**
     * Get from location display text.
     */
    public function getFromLocation(): string
    {
        if ($this->from_holder_type === 'warehouse') {
            return 'Warehouse';
        }
        return $this->fromHolder?->name ?? 'Unknown Technician';
    }

    /**
     * Get to location display text.
     */
    public function getToLocation(): string
    {
        if ($this->to_holder_type === 'warehouse') {
            return 'Warehouse';
        }
        return $this->toHolder?->name ?? 'Unknown Technician';
    }

    /**
     * Get available movement types.
     */
    public static function getMovementTypes(): array
    {
        return [
            self::TYPE_STOCK_IN => 'Stock In',
            self::TYPE_STOCK_OUT => 'Stock Out',
            self::TYPE_STOCK_RETURN => 'Stock Return',
            self::TYPE_STOCK_ADJUSTMENT => 'Stock Adjustment',
        ];
    }

    /**
     * Get available conditions.
     */
    public static function getConditions(): array
    {
        return [
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_FAULTY => 'Faulty',
            self::CONDITION_DAMAGED => 'Damaged',
        ];
    }
}
