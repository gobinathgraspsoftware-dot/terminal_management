<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'gateway_name',
        'display_name',
        'api_key',
        'secret_key',
        'merchant_id',
        'callback_url',
        'return_url',
        'is_sandbox',
        'is_active',
        'supported_currencies',
        'config_data',
        'notes',
    ];

    protected $casts = [
        'is_sandbox' => 'boolean',
        'is_active' => 'boolean',
        'supported_currencies' => 'array',
        'config_data' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function transactions()
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeProduction($query)
    {
        return $query->where('is_sandbox', false);
    }

    public function scopeSandbox($query)
    {
        return $query->where('is_sandbox', true);
    }

    public function scopeByGateway($query, $gatewayName)
    {
        return $query->where('gateway_name', $gatewayName);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSandbox(): bool
    {
        return $this->is_sandbox;
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supported_currencies ?? []);
    }
}
