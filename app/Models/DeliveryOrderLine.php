<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id', 'line_no', 'model_id', 'serial_id', 'serial_no',
        'description', 'quantity', 'unit', 'remarks',
    ];

    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
}
