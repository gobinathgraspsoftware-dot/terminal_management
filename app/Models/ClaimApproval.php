<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimApproval extends Model
{
    use HasFactory;

    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_RETURNED = 'returned';

    protected $fillable = [
        'claim_id', 'approval_level', 'approver_id', 'action', 'comments', 'actioned_at',
    ];

    protected function casts(): array { return ['actioned_at' => 'datetime']; }
    public function claim() { return $this->belongsTo(Claim::class); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
}
