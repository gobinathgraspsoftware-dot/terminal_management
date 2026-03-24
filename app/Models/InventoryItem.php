<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_ROUTER = 'router';
    const TYPE_ACCESSORY = 'accessory';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'item_code',
        'item_name',
        'job_category_id',
        'item_type',
        'description',
        'unit',
        'serial_number',
        'brand',
        'model',
        'reorder_level',
        'status',
        'created_by',
        'updated_by',
    ];

    // =========================================================
    // Relationships
    // =========================================================

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'inventory_item_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'inventory_item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // =========================================================
    // Scopes
    // =========================================================

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

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('job_category_id', $categoryId);
    }

    // =========================================================
    // Helpers
    // =========================================================

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
     * Get total warehouse stock quantity.
     */
    public function getWarehouseStockAttribute(): int
    {
        return $this->stockBalances()
            ->where('holder_type', 'warehouse')
            ->whereNull('holder_id')
            ->sum('quantity');
    }

    /**
     * Get total stock across all holders.
     */
    public function getTotalStockAttribute(): int
    {
        return $this->stockBalances()->sum('quantity');
    }

    /**
     * Check if stock is below reorder level.
     */
    public function isLowStock(): bool
    {
        return $this->warehouse_stock <= $this->reorder_level;
    }
}
