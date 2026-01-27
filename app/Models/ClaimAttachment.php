<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id', 'claim_line_id', 'file_name', 'file_path',
        'file_size', 'mime_type', 'description', 'uploaded_by',
    ];

    public function claim() { return $this->belongsTo(Claim::class); }
    public function claimLine() { return $this->belongsTo(ClaimLine::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
