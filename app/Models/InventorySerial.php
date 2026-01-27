<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySerial extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_ISSUED_TO_TECH = 'issued_to_tech';
    const STATUS_INSTALLED = 'installed';
    const STATUS_UNDER_SERVICE = 'under_service';
    const STATUS_RETURNED_TO_VENDOR = 'returned_to_vendor';
    const STATUS_WASTED = 'wasted';
    const STATUS_RESERVED = 'reserved';

    const LOCATION_TYPE_DEPOT = 'depot';
    const LOCATION_TYPE_TECHNICIAN = 'technician';
    const LOCATION_TYPE_SITE = 'site';
    const LOCATION_TYPE_VENDOR = 'vendor';

    protected $fillable = [
        'serial_no', 'model_id', 'hardware_type', 'device_type', 'telco',
        'sim_quota', 'current_status', 'current_location_type', 'current_location_id',
        'grn_id', 'grn_date', 'po_id', 'warranty_start', 'warranty_end',
        'purchase_price', 'remarks', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'grn_date' => 'date',
            'warranty_start' => 'date',
            'warranty_end' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function grn() { return $this->belongsTo(Grn::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class, 'po_id'); }
    
    public function currentLocation()
    {
        return $this->morphTo(__FUNCTION__, 'current_location_type', 'current_location_id');
    }

    public function stockLedgers() { return $this->hasMany(StockLedger::class, 'serial_id'); }
    public function siteAsset() { return $this->hasOne(SiteAsset::class, 'serial_id'); }

    public function scopeInStock($query) { return $query->where('current_status', self::STATUS_IN_STOCK); }
    public function scopeAvailable($query)
    {
        return $query->where('current_status', self::STATUS_IN_STOCK)
            ->where('current_status', '!=', self::STATUS_RESERVED);
    }
    public function scopeAtDepot($query, $depotId)
    {
        return $query->where('current_location_type', self::LOCATION_TYPE_DEPOT)
            ->where('current_location_id', $depotId);
    }

    public function updateLocation($type, $id, $status)
    {
        $this->update([
            'current_location_type' => $type,
            'current_location_id' => $id,
            'current_status' => $status,
        ]);
    }
}
