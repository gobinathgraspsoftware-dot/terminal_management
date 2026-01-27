<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappQueue extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_queue';

    protected $fillable = [
        'recipient_phone',
        'recipient_name',
        'message',
        'template_id',
        'media_url',
        'priority',
        'status',
        'whatsapp_message_id',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function template()
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('scheduled_at')
                     ->where('status', 'pending');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'pending')
                     ->where(function($q) {
                         $q->whereNull('scheduled_at')
                           ->orWhere('scheduled_at', '<=', now());
                     });
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->attempts < $this->max_attempts;
    }

    public function markAsSent(string $messageId = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'whatsapp_message_id' => $messageId,
            'error_message' => null,
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
            'attempts' => $this->attempts + 1,
        ]);
    }
}
