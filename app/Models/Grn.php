<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grn extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'grn_no', 'grn_date', 'purchase_order_id', 'vendor_id', 'receiving_depot_id',
        'delivery_note_no', 'total_items', 'remarks', 'status', 'posted_at',
        'posted_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'grn_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    // Main relationships
    public function purchaseOrder() 
    { 
        return $this->belongsTo(PurchaseOrder::class); 
    }

    public function vendor() 
    { 
        return $this->belongsTo(Vendor::class); 
    }

    public function receivingDepot() 
    { 
        return $this->belongsTo(Depot::class, 'receiving_depot_id'); 
    }

    public function lines() 
    { 
        return $this->hasMany(GrnLine::class); 
    }

    public function inventorySerials() 
    { 
        return $this->hasMany(InventorySerial::class); 
    }

    // User relationships - ADDED THESE!
    public function createdBy() 
    { 
        return $this->belongsTo(User::class, 'created_by'); 
    }

    public function updatedBy() 
    { 
        return $this->belongsTo(User::class, 'updated_by'); 
    }

    public function postedBy() 
    { 
        return $this->belongsTo(User::class, 'posted_by'); 
    }

    // Helper methods
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted()
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }
}
