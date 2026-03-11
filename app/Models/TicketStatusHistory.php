<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'ticket_status_history';

    protected $fillable = ['ticket_id', 'from_status', 'to_status', 'changed_by', 'remarks', 'created_at'];

    protected function casts(): array { return ['created_at' => 'datetime']; }

    public function ticket() { return $this->belongsTo(Ticket::class); }
    public function changedBy() { return $this->belongsTo(User::class, 'changed_by'); }
}
