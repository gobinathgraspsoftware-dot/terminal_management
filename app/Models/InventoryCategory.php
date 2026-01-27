<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $table = 'inventory_categories';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function items()
    {
        return $this->hasMany(Inventory::class, 'category_id');
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

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function getActiveItemsCount(): int
    {
        return $this->items()->where('status', 'active')->count();
    }

    public function getTotalValue(): float
    {
        return $this->items()
                    ->where('status', 'active')
                    ->sum(\DB::raw('quantity * unit_price'));
    }
}
