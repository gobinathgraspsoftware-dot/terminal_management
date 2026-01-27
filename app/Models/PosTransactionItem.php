<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'inventory_id',
        'item_name',
        'quantity',
        'unit_price',
        'subtotal',
        'discount',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'transaction_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
