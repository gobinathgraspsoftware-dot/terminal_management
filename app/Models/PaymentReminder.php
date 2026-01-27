<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'reminder_type',
        'reminder_date',
        'sent_via',
        'status',
        'notes',
    ];

    protected $casts = [
        'reminder_date' => 'date',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('reminder_type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('sent_via', $channel);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('reminder_date', today());
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent']);
    }

    public function markAsFailed(string $note = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $note,
        ]);
    }
}
