<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_note_id', 'line_no', 'invoice_line_id', 'description',
        'quantity', 'unit_price', 'tax_rate', 'tax_amount', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function creditNote() { return $this->belongsTo(CreditNote::class); }
    public function invoiceLine() { return $this->belongsTo(InvoiceLine::class); }
}
