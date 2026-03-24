<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'transfer_no',
        'from_holder_type',
        'from_holder_id',
        'to_holder_type',
        'to_holder_id',
        'inventory_item_id',
        'quantity',
        'status',
        'reason',
        'remarks',
        'transfer_date',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at'   => 'datetime',
            'quantity'       => 'integer',
        ];
    }

    // =========================================================
    // Relationships
    // =========================================================

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'transfer_id');
    }

    // =========================================================
    // Scopes
    // =========================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // =========================================================
    // Helpers
    // =========================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public static function getStatusBadgeColor(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING   => 'warning',
            self::STATUS_APPROVED  => 'info',
            self::STATUS_REJECTED  => 'danger',
            self::STATUS_COMPLETED => 'success',
            default => 'secondary',
        };
    }
}
