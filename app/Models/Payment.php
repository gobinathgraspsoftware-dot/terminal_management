<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    const TYPE_RECEIPT = 'receipt';
    const TYPE_PAYMENT = 'payment';

    const METHOD_CASH = 'cash';
    const METHOD_CHEQUE = 'cheque';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CREDIT_CARD = 'credit_card';
    const METHOD_ONLINE = 'online';
    const METHOD_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_CLEARED = 'cleared';
    const STATUS_BOUNCED = 'bounced';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_no', 'payment_date', 'payment_type', 'invoice_id', 'client_id',
        'vendor_id', 'amount', 'payment_method', 'reference_no', 'bank_name',
        'remarks', 'status', 'cleared_date', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'cleared_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
}
