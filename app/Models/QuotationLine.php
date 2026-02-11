<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationLine extends Model
{
    use HasFactory;

    const ITEM_TYPE_MODEL = 'model';
    const ITEM_TYPE_CHARGE = 'charge';
    const ITEM_TYPE_CUSTOM = 'custom';

    protected $fillable = [
        'quotation_id', 'line_no', 'item_type', 'model_id', 'charge_id', 'description',
        'quantity', 'unit', 'unit_price', 'discount_percent', 'discount_amount',
        'tax_rate', 'tax_amount', 'line_total', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    // Relationships
    public function quotation() 
    { 
        return $this->belongsTo(Quotation::class); 
    }

    public function model() 
    { 
        return $this->belongsTo(TerminalModel::class); 
    }

    public function charge() 
    { 
        return $this->belongsTo(ChargeCatalog::class); 
    }

    // Helper Methods
    public function isModelItem(): bool
    {
        return $this->item_type === self::ITEM_TYPE_MODEL;
    }

    public function isChargeItem(): bool
    {
        return $this->item_type === self::ITEM_TYPE_CHARGE;
    }

    public function isCustomItem(): bool
    {
        return $this->item_type === self::ITEM_TYPE_CUSTOM;
    }

    public function getItemNameAttribute(): string
    {
        if ($this->isModelItem() && $this->model) {
            return $this->model->model_name;
        }

        if ($this->isChargeItem() && $this->charge) {
            return $this->charge->charge_name;
        }

        return $this->description ?? 'Custom Item';
    }

    public function calculateLineTotal(): float
    {
        // Calculate base amount
        $baseAmount = $this->quantity * $this->unit_price;

        // Apply discount
        if ($this->discount_percent > 0) {
            $this->discount_amount = round($baseAmount * ($this->discount_percent / 100), 2);
        }

        $amountAfterDiscount = $baseAmount - $this->discount_amount;

        // Calculate tax
        if ($this->tax_rate > 0) {
            $this->tax_amount = round($amountAfterDiscount * ($this->tax_rate / 100), 2);
        } else {
            $this->tax_amount = 0;
        }

        // Line total (excluding tax - tax is added at header level)
        $this->line_total = round($amountAfterDiscount, 2);

        return $this->line_total;
    }

    public function getSubtotalAttribute(): float
    {
        return round($this->quantity * $this->unit_price, 2);
    }

    public function getDiscountedAmountAttribute(): float
    {
        return round($this->subtotal - $this->discount_amount, 2);
    }

    public function getTotalWithTaxAttribute(): float
    {
        return round($this->line_total + $this->tax_amount, 2);
    }

    public static function getItemTypeList(): array
    {
        return [
            self::ITEM_TYPE_MODEL => 'Terminal Model',
            self::ITEM_TYPE_CHARGE => 'Charge Item',
            self::ITEM_TYPE_CUSTOM => 'Custom Item',
        ];
    }

    public function getItemTypeLabel(): string
    {
        return self::getItemTypeList()[$this->item_type] ?? 'Unknown';
    }

    // Scopes
    public function scopeModelItems($query)
    {
        return $query->where('item_type', self::ITEM_TYPE_MODEL);
    }

    public function scopeChargeItems($query)
    {
        return $query->where('item_type', self::ITEM_TYPE_CHARGE);
    }

    public function scopeCustomItems($query)
    {
        return $query->where('item_type', self::ITEM_TYPE_CUSTOM);
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            // Auto-calculate line total before saving
            $line->calculateLineTotal();
        });
    }
}
