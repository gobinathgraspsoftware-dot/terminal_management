<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPhoto extends Model
{
    use HasFactory;

    const TYPE_BEFORE = 'before';
    const TYPE_AFTER = 'after';
    const TYPE_ISSUE = 'issue';
    const TYPE_SERIAL = 'serial';
    const TYPE_SIGNATURE = 'signature';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'job_order_id', 'photo_type', 'file_name', 'file_path',
        'file_size', 'mime_type', 'caption', 'uploaded_by',
    ];

    public function jobOrder() { return $this->belongsTo(JobOrder::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }
    
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
