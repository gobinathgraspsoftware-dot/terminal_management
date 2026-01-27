<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeCatalog extends Model
{
    use HasFactory;

    const TYPE_INSTALLATION = 'installation';
    const TYPE_SERVICE = 'service';
    const TYPE_HARDWARE = 'hardware';
    const TYPE_ACCESSORY = 'accessory';
    const TYPE_LABOUR = 'labour';
    const TYPE_TRANSPORT = 'transport';
    const TYPE_OTHER = 'other';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $table = 'charge_catalog';

    protected $fillable = [
        'charge_code', 'charge_name', 'charge_type', 'description',
        'default_price', 'tax_rate', 'is_taxable', 'unit', 'status',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_taxable' => 'boolean',
        ];
    }

    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeByType($query, $type) { return $query->where('charge_type', $type); }
}
