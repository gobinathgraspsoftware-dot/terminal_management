<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'item_code',
        'name',
        'category_id',
        'description',
        'unit_price',
        'selling_price',
        'quantity',
        'min_quantity',
        'unit',
        'supplier',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'expiry_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock')
                     ->orWhere('quantity', '<=', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_quantity');
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0 || $this->status === 'out_of_stock';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiring(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->unit_price <= 0) {
            return 0;
        }

        return (($this->selling_price - $this->unit_price) / $this->unit_price) * 100;
    }
}
