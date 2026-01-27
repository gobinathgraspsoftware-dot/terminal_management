<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    use HasFactory;

    const LOCATION_TYPE_DEPOT = 'depot';
    const LOCATION_TYPE_TECHNICIAN = 'technician';

    protected $fillable = [
        'model_id', 'location_type', 'location_id', 'quantity_on_hand',
        'quantity_reserved', 'last_movement_date',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'last_movement_date' => 'date',
        ];
    }

    public function model() { return $this->belongsTo(TerminalModel::class); }

    public function location()
    {
        return $this->morphTo(__FUNCTION__, 'location_type', 'location_id');
    }

    public function getQuantityAvailableAttribute()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }
}
