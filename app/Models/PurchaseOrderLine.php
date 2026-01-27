<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'line_no', 'model_id', 'description', 'quantity_ordered',
        'quantity_received', 'quantity_cancelled', 'unit', 'unit_price',
        'discount_percent', 'discount_amount', 'tax_rate', 'tax_amount',
        'line_total', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'quantity_cancelled' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    
    public function getQuantityOutstandingAttribute()
    {
        return $this->quantity_ordered - $this->quantity_received - $this->quantity_cancelled;
    }
}
