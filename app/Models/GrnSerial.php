<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_line_id', 'serial_no', 'serial_id', 'hardware_type', 'device_type',
        'telco', 'sim_quota', 'warranty_start', 'warranty_end', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'warranty_start' => 'date',
            'warranty_end' => 'date',
        ];
    }

    public function grnLine() { return $this->belongsTo(GrnLine::class); }
    public function inventorySerial() { return $this->belongsTo(InventorySerial::class, 'serial_id'); }
}
