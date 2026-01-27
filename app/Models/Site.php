<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'site_code', 'site_name', 'client_id', 'address', 'city', 'state',
        'postcode', 'country', 'latitude', 'longitude', 'pic_name',
        'pic_phone', 'pic_email', 'operating_hours', 'notes', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($site) {
            if (empty($site->site_code)) {
                $site->site_code = static::generateSiteCode();
            }
        });
    }

    protected static function generateSiteCode(): string
    {
        $prefix = 'SITE';
        $last = static::withTrashed()->where('site_code', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $newNumber = $last ? ((int) substr($last->site_code, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public function client() { return $this->belongsTo(Client::class); }
    public function contacts() { return $this->hasMany(SiteContact::class); }
    public function jobOrders() { return $this->hasMany(JobOrder::class); }
    public function siteAssets() { return $this->hasMany(SiteAsset::class); }
    public function deliveryOrders() { return $this->hasMany(DeliveryOrder::class); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeByState($query, $state) { return $query->where('state', $state); }
    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([$this->address, $this->postcode . ' ' . $this->city, $this->state, $this->country]));
    }
}
