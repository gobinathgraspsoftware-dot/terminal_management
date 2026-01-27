<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory;

    const TYPE_GRN_IN = 'grn_in';
    const TYPE_ISSUE_TO_TECH = 'issue_to_tech';
    const TYPE_RETURN_FROM_TECH = 'return_from_tech';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_INSTALL = 'install';
    const TYPE_REPLACEMENT_OUT = 'replacement_out';
    const TYPE_REPLACEMENT_IN = 'replacement_in';
    const TYPE_WASTAGE = 'wastage';
    const TYPE_RETURN_TO_VENDOR = 'return_to_vendor';
    const TYPE_ADJUSTMENT = 'adjustment';

    protected $table = 'stock_ledger';

    protected $fillable = [
        'transaction_date', 'transaction_no', 'transaction_type', 'reference_type',
        'reference_id', 'serial_id', 'serial_no', 'model_id', 'quantity',
        'from_location_type', 'from_location_id', 'to_location_type', 'to_location_id',
        'unit_cost', 'total_cost', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function serial() { return $this->belongsTo(InventorySerial::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
