<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteContact extends Model
{
    use HasFactory;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'site_id', 'contact_name', 'contact_title', 'contact_email',
        'contact_phone', 'is_primary', 'notes', 'status',
    ];

    protected function casts(): array { return ['is_primary' => 'boolean']; }
    public function site() { return $this->belongsTo(Site::class); }
    public function scopeActive($query) { return $query->where('status', self::STATUS_ACTIVE); }
    public function scopePrimary($query) { return $query->where('is_primary', true); }
}
