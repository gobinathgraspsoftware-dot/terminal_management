<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'branch_name',
        'address',
        'state_id',
        'city_id',
        'postcode',
        'country',
        'contact_person',
        'contact_email',
        'contact_phone',
        'is_primary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'state_id' => 'integer',
            'city_id' => 'integer',
        ];
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByState($query, $stateId)
    {
        return $query->where('state_id', $stateId);
    }

    // Accessors
    public function getFullAddressAttribute(): string
    {
        $stateName = $this->relationLoaded('state') && $this->state
            ? $this->state->name
            : ($this->state()->first()?->name ?? '');

        $cityName = $this->relationLoaded('city') && $this->city
            ? $this->city->name
            : ($this->city()->first()?->name ?? '');

        $parts = array_filter([
            $this->address,
            trim(($this->postcode ?? '') . ' ' . $cityName),
            $stateName,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    public function getStateNameAttribute(): string
    {
        if ($this->relationLoaded('state') && $this->state) {
            return $this->state->name;
        }
        return $this->state()->first()?->name ?? '-';
    }

    public function getCityNameAttribute(): string
    {
        if ($this->relationLoaded('city') && $this->city) {
            return $this->city->name;
        }
        return $this->city()->first()?->name ?? '-';
    }
}
