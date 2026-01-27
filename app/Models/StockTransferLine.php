<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id', 'line_no', 'model_id', 'serial_id', 'serial_no',
        'quantity_requested', 'quantity_dispatched', 'quantity_received', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_dispatched' => 'decimal:4',
            'quantity_received' => 'decimal:4',
        ];
    }

    public function stockTransfer() { return $this->belongsTo(StockTransfer::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
}
