<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'type',
        'quantity',
        'old_quantity',
        'new_quantity',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'old_quantity' => 'integer',
        'new_quantity' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', 'stock_in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', 'stock_out');
    }

    public function scopeAdjustment($query)
    {
        return $query->where('type', 'adjustment');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
