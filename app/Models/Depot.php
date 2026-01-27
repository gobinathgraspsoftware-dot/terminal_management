<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depot extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const TYPE_MAIN = 'main';
    const TYPE_REGIONAL = 'regional';
    const TYPE_TECHNICIAN = 'technician';

    protected $fillable = [
        'depot_code', 'depot_name', 'depot_type', 'address', 'city', 'state',
        'postcode', 'country', 'pic_name', 'pic_phone', 'pic_email',
        'is_default', 'notes', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($depot) {
            if (empty($depot->depot_code)) {
                $depot->depot_code = static::generateDepotCode();
            }
        });
    }

    protected static function generateDepotCode(): string
    {
        $prefix = 'DPT';
        $last = static::withTrashed()->where('depot_code', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $newNumber = $last ? ((int) substr($last->depot_code, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function stockBalances() { return $this->hasMany(StockBalance::class, 'location_id')->where('location_type', 'depot'); }
    public function stockIssues() { return $this->hasMany(StockIssue::class, 'from_depot_id'); }
    public function grns() { return $this->hasMany(Grn::class, 'receiving_depot_id'); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class, 'receiving_depot_id'); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeDefault($query) { return $query->where('is_default', true); }
}
