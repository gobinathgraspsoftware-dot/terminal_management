<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
     * Checks public_path first (cPanel direct uploads via move()),
     * then falls back to Storage disk (symlink-based uploads via storeAs()).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        // Check public_path first (cPanel compatible — files moved directly)
        $publicFile = public_path('storage/' . $this->image_path);
        if (file_exists($publicFile)) {
            return asset('storage/' . $this->image_path);
        }

        // Fallback: check storage disk (symlink-based)
        if (Storage::disk('public')->exists($this->image_path)) {
            return Storage::url($this->image_path);
        }

        return null;
    }

    /**
     * Check if model has an image
     */
    public function getHasImageAttribute(): bool
    {
        if (!$this->image_path) {
            return false;
        }

        // Check both locations
        $publicFile = public_path('storage/' . $this->image_path);
        if (file_exists($publicFile)) {
            return true;
        }

        return Storage::disk('public')->exists($this->image_path);
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
