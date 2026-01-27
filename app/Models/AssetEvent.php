<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetEvent extends Model
{
    use HasFactory;

    const TYPE_INSTALLED = 'installed';
    const TYPE_SERVICED = 'serviced';
    const TYPE_REPAIRED = 'repaired';
    const TYPE_REPLACED = 'replaced';
    const TYPE_REMOVED = 'removed';
    const TYPE_WASTED = 'wasted';
    const TYPE_WARRANTY_CLAIM = 'warranty_claim';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'site_asset_id', 'event_date', 'event_type', 'job_order_id', 'old_serial_no',
        'new_serial_no', 'description', 'performed_by',
    ];

    protected function casts(): array { return ['event_date' => 'date']; }
    public function siteAsset() { return $this->belongsTo(SiteAsset::class); }
    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function performedBy() { return $this->belongsTo(User::class, 'performed_by'); }
}
