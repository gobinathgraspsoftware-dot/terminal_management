<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportMonthlyRevenue extends Model
{
    use HasFactory;

    protected $table = 'report_monthly_revenue';

    protected $fillable = [
        'year', 'month', 'total_invoiced', 'total_collected',
        'total_outstanding', 'invoice_count',
    ];

    protected function casts(): array
    {
        return [
            'total_invoiced' => 'decimal:2',
            'total_collected' => 'decimal:2',
            'total_outstanding' => 'decimal:2',
        ];
    }
}
