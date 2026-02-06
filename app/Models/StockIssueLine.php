<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIssueLine extends Model
{
    use HasFactory;

    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_DEFECTIVE = 'defective';

    protected $fillable = [
        'stock_issue_id', 'line_no', 'model_id', 'serial_id',
        'serial_no', 'quantity', 'condition', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function stockIssue()
    {
        return $this->belongsTo(StockIssue::class);
    }

    public function model()
    {
        return $this->belongsTo(TerminalModel::class);
    }

    public function serial()
    {
        return $this->belongsTo(InventorySerial::class);
    }

    /**
     * Get condition badge HTML
     */
    public function getConditionBadgeAttribute(): string
    {
        $badges = [
            self::CONDITION_GOOD => '<span class="badge bg-success">Good</span>',
            self::CONDITION_DAMAGED => '<span class="badge bg-warning">Damaged</span>',
            self::CONDITION_DEFECTIVE => '<span class="badge bg-danger">Defective</span>',
        ];

        return $badges[$this->condition] ?? '<span class="badge bg-secondary">' . ucfirst($this->condition) . '</span>';
    }
}
