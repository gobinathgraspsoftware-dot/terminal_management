<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_out_id', 'line_no', 'model_id', 'serial_id',
        'serial_no', 'quantity', 'condition', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function stockOut() { return $this->belongsTo(StockOut::class); }
    public function model()    { return $this->belongsTo(TerminalModel::class, 'model_id'); }
    public function serial()   { return $this->belongsTo(InventorySerial::class, 'serial_id'); }
}
