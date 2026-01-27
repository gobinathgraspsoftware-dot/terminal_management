<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id', 'line_no', 'model_id', 'serial_id', 'serial_no',
        'system_quantity', 'physical_quantity', 'unit_cost', 'variance_value', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'physical_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'variance_value' => 'decimal:2',
        ];
    }

    public function stockAdjustment() { return $this->belongsTo(StockAdjustment::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
    
    public function getVarianceQuantityAttribute()
    {
        return $this->physical_quantity - $this->system_quantity;
    }
}
