<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    // This is for Spatie Activity Log package
    // The actual implementation is handled by the package
    protected $guarded = [];
    protected $casts = ['properties' => 'collection'];
    
    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
