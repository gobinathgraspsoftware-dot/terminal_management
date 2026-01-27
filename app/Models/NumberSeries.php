<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSeries extends Model
{
    use HasFactory;

    const RESET_NEVER = 'never';
    const RESET_YEARLY = 'yearly';
    const RESET_MONTHLY = 'monthly';

    protected $table = 'number_series';

    protected $fillable = [
        'series_type', 'prefix', 'suffix', 'current_number', 'number_length',
        'reset_frequency', 'last_reset_date', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_reset_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public static function getNextNumber($type)
    {
        $series = static::where('series_type', $type)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if (!$series) {
            throw new \Exception("Number series for type '{$type}' not found");
        }

        // Check if reset is needed
        if ($series->shouldReset()) {
            $series->resetSeries();
        }

        $series->increment('current_number');
        $number = $series->current_number;
        
        return $series->prefix . 
               str_pad($number, $series->number_length, '0', STR_PAD_LEFT) . 
               ($series->suffix ?? '');
    }

    protected function shouldReset(): bool
    {
        if ($this->reset_frequency === self::RESET_NEVER) {
            return false;
        }

        if (!$this->last_reset_date) {
            return true;
        }

        if ($this->reset_frequency === self::RESET_YEARLY) {
            return $this->last_reset_date->year < now()->year;
        }

        if ($this->reset_frequency === self::RESET_MONTHLY) {
            return $this->last_reset_date->format('Y-m') < now()->format('Y-m');
        }

        return false;
    }

    protected function resetSeries()
    {
        $this->update([
            'current_number' => 0,
            'last_reset_date' => now(),
        ]);
    }
}
