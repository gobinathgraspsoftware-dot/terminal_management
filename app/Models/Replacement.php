<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Replacement extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const DESTINATION_RETURN_TO_DEPOT = 'return_to_depot';
    const DESTINATION_RETURN_TO_VENDOR = 'return_to_vendor';
    const DESTINATION_WASTAGE = 'wastage';

    const DESTINATION_OPTIONS = [
        self::DESTINATION_RETURN_TO_DEPOT  => 'Return to Depot',
        self::DESTINATION_RETURN_TO_VENDOR => 'Return to Vendor',
        self::DESTINATION_WASTAGE          => 'Wastage / Scrap',
    ];

    const STATUS_BADGES = [
        self::STATUS_DRAFT     => 'secondary',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    protected $fillable = [
        'replacement_no', 'replacement_date', 'ticket_id',
        'technician_id', 'site_id',
        'old_serial_id', 'old_serial_no', 'old_model_id',
        'new_serial_id', 'new_serial_no', 'new_model_id',
        'old_device_condition', 'old_device_destination', 'return_depot_id',
        'reason', 'remarks', 'status',
        'completed_at', 'completed_by', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'replacement_date' => 'date',
            'completed_at'     => 'datetime',
        ];
    }

    // ── Relationships ──
    public function ticket()       { return $this->belongsTo(Ticket::class); }
    public function technician()   { return $this->belongsTo(User::class, 'technician_id'); }
    public function site()         { return $this->belongsTo(Site::class); }
    public function oldSerial()    { return $this->belongsTo(InventorySerial::class, 'old_serial_id'); }
    public function newSerial()    { return $this->belongsTo(InventorySerial::class, 'new_serial_id'); }
    public function oldModel()     { return $this->belongsTo(TerminalModel::class, 'old_model_id'); }
    public function newModel()     { return $this->belongsTo(TerminalModel::class, 'new_model_id'); }
    public function returnDepot()  { return $this->belongsTo(Depot::class, 'return_depot_id'); }
    public function completedByUser() { return $this->belongsTo(User::class, 'completed_by'); }
    public function createdByUser()   { return $this->belongsTo(User::class, 'created_by'); }

    // ── Helpers ──
    public function isDraft(): bool     { return $this->status === self::STATUS_DRAFT; }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }
}
