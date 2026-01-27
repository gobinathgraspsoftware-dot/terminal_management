<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportDailyJobSummary extends Model
{
    use HasFactory;

    protected $table = 'report_daily_job_summary';

    protected $fillable = [
        'report_date', 'total_jobs', 'pending_jobs', 'assigned_jobs', 'in_progress_jobs',
        'completed_jobs', 'failed_jobs', 'cancelled_jobs', 'installation_jobs',
        'service_jobs', 'sla_met', 'sla_breached',
    ];

    protected function casts(): array { return ['report_date' => 'date']; }
}
