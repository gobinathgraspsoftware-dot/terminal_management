<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'gateway_config_id',
        'payment_id',
        'invoice_id',
        'amount',
        'currency',
        'status',
        'gateway_status',
        'gateway_transaction_id',
        'gateway_response',
        'customer_name',
        'customer_email',
        'customer_phone',
        'payment_method',
        'initiated_at',
        'completed_at',
        'failed_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function gatewayConfig()
    {
        return $this->belongsTo(PaymentGatewayConfig::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByGateway($query, $gatewayConfigId)
    {
        return $query->where('gateway_config_id', $gatewayConfigId);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function markAsSuccess(array $responseData = []): void
    {
        $this->update([
            'status' => 'success',
            'gateway_status' => $responseData['gateway_status'] ?? 'completed',
            'gateway_response' => $responseData,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $error, array $responseData = []): void
    {
        $this->update([
            'status' => 'failed',
            'gateway_status' => $responseData['gateway_status'] ?? 'failed',
            'gateway_response' => $responseData,
            'error_message' => $error,
            'failed_at' => now(),
        ]);
    }

    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'error_message' => $reason,
            'failed_at' => now(),
        ]);
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'TXN' . date('YmdHis') . strtoupper(substr(uniqid(), -6));
    }
}
