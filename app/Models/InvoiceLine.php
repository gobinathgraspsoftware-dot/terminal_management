<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use HasFactory;

    const ITEM_TYPE_JOB = 'job';
    const ITEM_TYPE_MODEL = 'model';
    const ITEM_TYPE_CHARGE = 'charge';
    const ITEM_TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'invoice_id', 'line_no', 'item_type', 'job_order_id', 'delivery_order_id',
        'model_id', 'charge_id', 'description', 'quantity', 'unit', 'unit_price',
        'discount_percent', 'discount_amount', 'tax_rate', 'tax_amount',
        'line_total', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function charge() { return $this->belongsTo(ChargeCatalog::class); }
}
