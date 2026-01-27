<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id', 'line_no', 'po_line_id', 'model_id', 'description',
        'quantity_received', 'unit', 'unit_cost', 'line_total', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:4',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function grn() { return $this->belongsTo(Grn::class); }
    public function poLine() { return $this->belongsTo(PurchaseOrderLine::class, 'po_line_id'); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function serials() { return $this->hasMany(GrnSerial::class); }
}
