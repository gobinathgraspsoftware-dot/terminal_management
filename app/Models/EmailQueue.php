<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailQueue extends Model
{
    use HasFactory;

    protected $table = 'email_queue';

    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'body',
        'template_id',
        'attachments',
        'priority',
        'status',
        'attempts',
        'max_attempts',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'attachments' => 'array',
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
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

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
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

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
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
