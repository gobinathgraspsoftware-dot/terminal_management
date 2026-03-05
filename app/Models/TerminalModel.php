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

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function category()
    {
        return $this->belongsTo(TerminalCategory::class, 'category_id');
    }

    public function inventorySerials()
    {
        return $this->hasMany(InventorySerial::class, 'model_id');
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class, 'model_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the full URL for the model image.
     *
     * Simply uses asset() — no file_exists check.
     * On cPanel, file_exists(public_path()) fails because public_path()
     * returns the Laravel /public dir, NOT the cPanel /public_html dir.
     * But asset() generates the correct web URL regardless.
     *
     * The file IS stored at DOCUMENT_ROOT/storage/terminal_models/...
     * and asset('storage/...') generates the matching web URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * Check if model has an image path set
     */
    public function getHasImageAttribute(): bool
    {
        return !empty($this->image_path);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
