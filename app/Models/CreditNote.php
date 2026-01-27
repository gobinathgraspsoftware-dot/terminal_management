<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    const TYPE_AR = 'ar';
    const TYPE_AP = 'ap';

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_APPLIED = 'applied';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'credit_note_no', 'credit_note_date', 'credit_note_type', 'invoice_id',
        'client_id', 'vendor_id', 'reason', 'subtotal', 'tax_amount', 'total_amount',
        'status', 'approved_at', 'approved_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_note_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function lines() { return $this->hasMany(CreditNoteLine::class); }
}
