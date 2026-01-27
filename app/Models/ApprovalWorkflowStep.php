<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowStep extends Model
{
    use HasFactory;

    const APPROVER_TYPE_ROLE = 'role';
    const APPROVER_TYPE_USER = 'user';
    const APPROVER_TYPE_SUPERVISOR = 'supervisor';
    const APPROVER_TYPE_DEPARTMENT_HEAD = 'department_head';

    protected $fillable = [
        'workflow_id', 'step_order', 'step_name', 'approver_type', 'approver_role_id',
        'approver_user_id', 'min_amount', 'max_amount', 'is_required',
    ];

    protected function casts(): array
    {
        return [
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    public function workflow() { return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id'); }
}
