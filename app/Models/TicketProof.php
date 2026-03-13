<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id', 'ticket_status_history_id', 'proof_type',
        'file_name', 'file_path', 'file_size', 'mime_type',
        'caption', 'uploaded_by',
    ];

    public function ticket()        { return $this->belongsTo(Ticket::class); }
    public function statusHistory() { return $this->belongsTo(TicketStatusHistory::class, 'ticket_status_history_id'); }
    public function uploader()      { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
    }
}
