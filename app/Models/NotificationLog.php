<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'channel',
        'recipient',
        'subject',
        'content',
        'data',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    const UPDATED_AT = null;

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
