<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessoryUsage extends Model
{
    use HasFactory, SoftDeletes;

    // =========================================================================
    // CONSTANTS
    // =========================================================================
    const TYPE_SIM_CARD = 'sim_card';
    const TYPE_ANTENNA = 'antenna';
    const TYPE_CABLE = 'cable';
    const TYPE_ADAPTER = 'adapter';
    const TYPE_OTHER = 'other';

    const ACTION_USED = 'used';
    const ACTION_RETURNED = 'returned';
    const ACTION_REPLACED = 'replaced';

    const CONDITION_NEW = 'new';
    const CONDITION_GOOD = 'good';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_DEFECTIVE = 'defective';

    const ACCESSORY_TYPE_OPTIONS = [
        self::TYPE_SIM_CARD => 'SIM Card',
        self::TYPE_ANTENNA  => 'Antenna',
        self::TYPE_CABLE    => 'Cable',
        self::TYPE_ADAPTER  => 'Adapter',
        self::TYPE_OTHER    => 'Other',
    ];

    const ACTION_OPTIONS = [
        self::ACTION_USED     => 'Used',
        self::ACTION_RETURNED => 'Returned',
        self::ACTION_REPLACED => 'Replaced',
    ];

    const ACTION_BADGES = [
        self::ACTION_USED     => 'primary',
        self::ACTION_RETURNED => 'success',
        self::ACTION_REPLACED => 'warning',
    ];

    // =========================================================================
    // FILLABLE
    // =========================================================================
    protected $fillable = [
        'ticket_id', 'serial_id', 'model_id', 'accessory_type',
        'quantity', 'action', 'serial_no', 'condition',
        'return_date', 'return_condition', 'return_depot_id',
        'remarks', 'deducted_from_location_type', 'deducted_from_location_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'decimal:4',
            'return_date' => 'date',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function serial()
    {
        return $this->belongsTo(InventorySerial::class, 'serial_id');
    }

    public function model()
    {
        return $this->belongsTo(TerminalModel::class, 'model_id');
    }

    public function returnDepot()
    {
        return $this->belongsTo(Depot::class, 'return_depot_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================
    public function scopeUsed($query)
    {
        return $query->where('action', self::ACTION_USED);
    }

    public function scopeReturned($query)
    {
        return $query->where('action', self::ACTION_RETURNED);
    }

    public function scopeByTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('accessory_type', $type);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    public function isUsed(): bool
    {
        return $this->action === self::ACTION_USED;
    }

    public function isReturned(): bool
    {
        return $this->action === self::ACTION_RETURNED;
    }

    public function canReturn(): bool
    {
        return $this->action === self::ACTION_USED && is_null($this->return_date);
    }

    public function getAccessoryTypeLabelAttribute(): string
    {
        return self::ACCESSORY_TYPE_OPTIONS[$this->accessory_type] ?? ucfirst(str_replace('_', ' ', $this->accessory_type));
    }

    public function getActionBadgeAttribute(): string
    {
        $color = self::ACTION_BADGES[$this->action] ?? 'secondary';
        $label = self::ACTION_OPTIONS[$this->action] ?? ucfirst($this->action);
        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }
}
