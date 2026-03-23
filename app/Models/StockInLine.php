<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_in_id', 'line_no', 'model_id', 'serial_id',
        'serial_no', 'quantity', 'unit_cost', 'condition', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity'  => 'decimal:4',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stockIn() { return $this->belongsTo(StockIn::class); }
    public function model()   { return $this->belongsTo(TerminalModel::class, 'model_id'); }
    public function serial()  { return $this->belongsTo(InventorySerial::class, 'serial_id'); }
}
