<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;

    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';
    const EVENT_RESTORED = 'restored';
    const EVENT_POSTED = 'posted';
    const EVENT_APPROVED = 'approved';
    const EVENT_REJECTED = 'rejected';
    const EVENT_CANCELLED = 'cancelled';

    public $timestamps = false;

    protected $fillable = [
        'auditable_type', 'auditable_id', 'event', 'old_values', 'new_values',
        'url', 'ip_address', 'user_agent', 'user_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
