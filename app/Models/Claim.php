<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    // ── Category constants ──
    const CATEGORY_TICKET = 'ticket';
    const CATEGORY_OTHER  = 'other';

    // ── Status constants ──
    const STATUS_DRAFT           = 'draft';
    const STATUS_SUBMITTED       = 'submitted';
    const STATUS_VERIFIED        = 'verified';
    const STATUS_NON_CLAIMABLE   = 'non_claimable';
    const STATUS_PENDING_PAYMENT = 'pending_payment';
    const STATUS_PAID            = 'paid';
    const STATUS_CANCELLED       = 'cancelled';

    protected $fillable = [
        'claim_no', 'claim_category', 'claim_date', 'ticket_id',
        'technician_id', 'claim_period_from', 'claim_period_to',
        'description', 'claim_type_label',
        'total_mileage_km', 'total_mileage_amount', 'total_allowance_amount',
        'total_amount', 'original_amount',
        'remarks', 'admin_remarks',
        'status',
        'submitted_at', 'submitted_by',
        'verified_at', 'verified_by',
        'approved_at', 'approved_by',
        'rejected_at', 'rejected_by',
        'paid_at', 'paid_by',
        'payout_batch_id',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'claim_date'            => 'date',
            'claim_period_from'     => 'date',
            'claim_period_to'       => 'date',
            'total_mileage_km'      => 'decimal:2',
            'total_mileage_amount'  => 'decimal:2',
            'total_allowance_amount'=> 'decimal:2',
            'total_amount'          => 'decimal:2',
            'original_amount'       => 'decimal:2',
            'submitted_at'          => 'datetime',
            'verified_at'           => 'datetime',
            'approved_at'           => 'datetime',
            'rejected_at'           => 'datetime',
            'paid_at'               => 'datetime',
        ];
    }

    // ══════════════════════════════════════
    // Relationships
    // ══════════════════════════════════════

    public function technician()  { return $this->belongsTo(User::class, 'technician_id'); }

    /**
     * BUG FIX: Ticket relationship MUST include withTrashed().
     *
     * The Ticket model uses SoftDeletes. Without withTrashed(), completed tickets
     * that have been soft-deleted return null, causing all ticket columns
     * (ticket_no, vendor, merchant, supervisor) to display "-" in the claims list.
     */
    public function ticket()      { return $this->belongsTo(Ticket::class)->withTrashed(); }

    public function submitter()   { return $this->belongsTo(User::class, 'submitted_by'); }
    public function verifier()    { return $this->belongsTo(User::class, 'verified_by'); }
    public function approver()    { return $this->belongsTo(User::class, 'approved_by'); }
    public function payer()       { return $this->belongsTo(User::class, 'paid_by'); }
    public function payoutBatch() { return $this->belongsTo(PayoutBatch::class); }
    public function lines()       { return $this->hasMany(ClaimLine::class); }
    public function attachments() { return $this->hasMany(ClaimAttachment::class); }
    public function approvals()   { return $this->hasMany(ClaimApproval::class); }
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()     { return $this->belongsTo(User::class, 'updated_by'); }

    // ══════════════════════════════════════
    // Scopes
    // ══════════════════════════════════════

    public function scopeTicketClaims($query)  { return $query->where('claim_category', self::CATEGORY_TICKET); }
    public function scopeOtherClaims($query)   { return $query->where('claim_category', self::CATEGORY_OTHER); }
    public function scopeSubmitted($query)     { return $query->where('status', self::STATUS_SUBMITTED); }
    public function scopeVerified($query)      { return $query->where('status', self::STATUS_VERIFIED); }
    public function scopePendingPayment($query){ return $query->where('status', self::STATUS_PENDING_PAYMENT); }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }
        if ($user->hasRole('supervisor')) {
            $teamIds   = User::where('supervisor_id', $user->id)->pluck('id')->toArray();
            $teamIds[] = $user->id;
            return $query->where(function ($q) use ($teamIds, $user) {
                $q->whereIn('technician_id', $teamIds)
                  ->orWhere('submitted_by', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        // Technician
        return $query->where(function ($q) use ($user) {
            $q->where('technician_id', $user->id)
              ->orWhere('submitted_by', $user->id)
              ->orWhere('created_by', $user->id);
        });
    }

    // ══════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT           => 'Draft',
            self::STATUS_SUBMITTED       => 'Submitted',
            self::STATUS_VERIFIED        => 'Verified',
            self::STATUS_NON_CLAIMABLE   => 'Non-Claimable',
            self::STATUS_PENDING_PAYMENT => 'Pending Payment',
            self::STATUS_PAID            => 'Paid',
            self::STATUS_CANCELLED       => 'Cancelled',
        ];
    }

    public static function getStatusBadge(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT           => '<span class="badge bg-light text-dark">Draft</span>',
            self::STATUS_SUBMITTED       => '<span class="badge bg-info">Submitted</span>',
            self::STATUS_VERIFIED        => '<span class="badge bg-success">Verified</span>',
            self::STATUS_NON_CLAIMABLE   => '<span class="badge bg-danger">Non-Claimable</span>',
            self::STATUS_PENDING_PAYMENT => '<span class="badge bg-warning text-dark">Pending Payment</span>',
            self::STATUS_PAID            => '<span class="badge bg-primary">Paid</span>',
            self::STATUS_CANCELLED       => '<span class="badge bg-secondary">Cancelled</span>',
            default                      => '<span class="badge bg-secondary">' . ucfirst(str_replace('_', ' ', $status)) . '</span>',
        };
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_TICKET => 'Ticket Claim',
            self::CATEGORY_OTHER  => 'Other Claim',
        ];
    }

    public static function getOtherClaimTypes(): array
    {
        return [
            'courier_faulty_device' => 'Courier Faulty Device Back',
            'miscellaneous'         => 'Miscellaneous Expenses',
            'out_of_pocket'         => 'Out-of-Pocket Claim',
            'transport'             => 'Transport',
            'parking'               => 'Parking',
            'toll'                  => 'Toll',
            'meal'                  => 'Meal',
            'accommodation'         => 'Accommodation',
            'other'                 => 'Other',
        ];
    }

    /**
     * Get the vendor display name from the linked ticket.
     * Uses vendor_name (NOT NULL) with company_name as fallback.
     */
    public function getVendorDisplayName(): string
    {
        $vendor = $this->ticket?->vendor;
        if (!$vendor) return '-';
        return $vendor->vendor_name
            ?? $vendor->company_name
            ?? '-';
    }

    public function canBeVerified(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeProcessedForPayment(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }
}
