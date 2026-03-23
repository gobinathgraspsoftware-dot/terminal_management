<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_DIRECT_TO_SITE = 'direct_to_site';
    const TYPE_WASTAGE = 'wastage';
    const TYPE_RETURN_TO_VENDOR = 'return_to_vendor';
    const TYPE_DONATION = 'donation';
    const TYPE_OTHER = 'other';

    const OUT_TYPE_OPTIONS = [
        self::TYPE_DIRECT_TO_SITE  => 'Direct to Site',
        self::TYPE_WASTAGE         => 'Wastage / Scrap',
        self::TYPE_RETURN_TO_VENDOR => 'Return to Vendor',
        self::TYPE_DONATION        => 'Donation',
        self::TYPE_OTHER           => 'Other',
    ];

    const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'secondary',
        self::STATUS_POSTED    => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $fillable = [
        'stock_out_no', 'stock_out_date', 'out_type',
        'from_depot_id', 'from_technician_id',
        'to_site_id', 'to_vendor_id',
        'total_items', 'remarks', 'status',
        'posted_at', 'posted_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stock_out_date' => 'date',
            'posted_at'      => 'datetime',
        ];
    }

    public function fromDepot()      { return $this->belongsTo(Depot::class, 'from_depot_id'); }
    public function fromTechnician() { return $this->belongsTo(User::class, 'from_technician_id'); }
    public function toSite()         { return $this->belongsTo(Site::class, 'to_site_id'); }
    public function toVendor()       { return $this->belongsTo(Vendor::class, 'to_vendor_id'); }
    public function lines()          { return $this->hasMany(StockOutLine::class); }
    public function postedByUser()   { return $this->belongsTo(User::class, 'posted_by'); }
    public function createdByUser()  { return $this->belongsTo(User::class, 'created_by'); }

    public function isDraft(): bool     { return $this->status === self::STATUS_DRAFT; }
    public function isPosted(): bool    { return $this->status === self::STATUS_POSTED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
}
