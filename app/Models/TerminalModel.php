<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TerminalModel extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'model_code', 'model_name', 'category_id', 'brand', 'description',
        'specifications', 'is_serial_tracked', 'warranty_months',
        'default_accessories', 'image_path', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_serial_tracked' => 'boolean',
            'specifications' => 'array',
            'default_accessories' => 'array',
        ];
    }

    public function category() { return $this->belongsTo(TerminalCategory::class, 'category_id'); }
    public function inventorySerials() { return $this->hasMany(InventorySerial::class, 'model_id'); }
    public function stockBalances() { return $this->hasMany(StockBalance::class, 'model_id'); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeByCategory($query, $categoryId) { return $query->where('category_id', $categoryId); }
}
