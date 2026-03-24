<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    const TYPE_STOCK_IN = 'stock_in';
    const TYPE_STOCK_OUT = 'stock_out';
    const TYPE_STOCK_RETURN = 'stock_return';
    const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';
    const TYPE_STOCK_TRANSFER = 'stock_transfer';

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
        'transfer_id',
        'reference_type',
        'reference_id',
        'reason',
        'remarks',
        'movement_date',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
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

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function fromHolder()
    {
        if ($this->from_holder_type === 'technician' && $this->from_holder_id) {
            return $this->belongsTo(User::class, 'from_holder_id');
        }
        return null;
    }

    public function toHolder()
    {
        if ($this->to_holder_type === 'technician' && $this->to_holder_id) {
            return $this->belongsTo(User::class, 'to_holder_id');
        }
        return null;
    }

    // =========================================================
    // Scopes
    // =========================================================

    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('inventory_item_id', $itemId);
    }

    // =========================================================
    // Helpers
    // =========================================================

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_STOCK_IN         => 'Stock In',
            self::TYPE_STOCK_OUT        => 'Stock Out',
            self::TYPE_STOCK_RETURN     => 'Stock Return',
            self::TYPE_STOCK_ADJUSTMENT => 'Stock Adjustment',
            self::TYPE_STOCK_TRANSFER   => 'Stock Transfer',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public static function getTypeBadgeColor(string $type): string
    {
        return match ($type) {
            self::TYPE_STOCK_IN         => 'success',
            self::TYPE_STOCK_OUT        => 'danger',
            self::TYPE_STOCK_RETURN     => 'info',
            self::TYPE_STOCK_ADJUSTMENT => 'warning',
            self::TYPE_STOCK_TRANSFER   => 'primary',
            default => 'secondary',
        };
    }
}
