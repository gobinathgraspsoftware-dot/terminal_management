<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIssueLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_issue_id', 'line_no', 'model_id', 'serial_id',
        'serial_no', 'quantity', 'remarks',
    ];

    protected function casts(): array { return ['quantity' => 'decimal:4']; }
    public function stockIssue() { return $this->belongsTo(StockIssue::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
}
