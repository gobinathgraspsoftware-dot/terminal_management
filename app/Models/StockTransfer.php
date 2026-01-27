<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transfer_no', 'transfer_date', 'from_depot_id', 'to_depot_id', 'total_items',
        'remarks', 'status', 'approved_at', 'approved_by', 'dispatched_at', 'dispatched_by',
        'received_at', 'received_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function fromDepot() { return $this->belongsTo(Depot::class, 'from_depot_id'); }
    public function toDepot() { return $this->belongsTo(Depot::class, 'to_depot_id'); }
    public function lines() { return $this->hasMany(StockTransferLine::class); }
}
