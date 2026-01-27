<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_name', 'workflow_type', 'description', 'is_active',
    ];

    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function steps() { return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id'); }
}
