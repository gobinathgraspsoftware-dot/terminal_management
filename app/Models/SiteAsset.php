<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteAsset extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_UNDER_SERVICE = 'under_service';
    const STATUS_REPLACED = 'replaced';
    const STATUS_REMOVED = 'removed';
    const STATUS_WASTED = 'wasted';

    protected $fillable = [
        'site_id', 'serial_id', 'serial_no', 'model_id', 'installed_date',
        'installed_by', 'installation_job_id', 'delivery_order_id', 'warranty_start',
        'warranty_end', 'status', 'removed_date', 'removed_by', 'removal_reason',
        'replacement_asset_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'installed_date' => 'date',
            'warranty_start' => 'date',
            'warranty_end' => 'date',
            'removed_date' => 'date',
        ];
    }

    public function site() { return $this->belongsTo(Site::class); }
    public function serial() { return $this->belongsTo(InventorySerial::class); }
    public function model() { return $this->belongsTo(TerminalModel::class); }
    public function installedBy() { return $this->belongsTo(User::class, 'installed_by'); }
    public function installationJob() { return $this->belongsTo(JobOrder::class, 'installation_job_id'); }
    public function deliveryOrder() { return $this->belongsTo(DeliveryOrder::class); }
    public function events() { return $this->hasMany(AssetEvent::class); }
    public function replacementAsset() { return $this->belongsTo(SiteAsset::class, 'replacement_asset_id'); }
}
