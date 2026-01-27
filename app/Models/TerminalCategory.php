<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminalCategory extends Model
{
    use HasFactory;

    const TYPE_TERMINAL = 'terminal';
    const TYPE_ROUTER = 'router';
    const TYPE_SIM = 'sim';
    const TYPE_ACCESSORY = 'accessory';
    const TYPE_OTHER = 'other';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'category_code', 'category_name', 'category_type', 'description',
        'is_serial_tracked', 'sort_order', 'status',
    ];

    protected function casts(): array { return ['is_serial_tracked' => 'boolean']; }
    public function terminalModels() { return $this->hasMany(TerminalModel::class, 'category_id'); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopeByType($query, $type) { return $query->where('category_type', $type); }
}
