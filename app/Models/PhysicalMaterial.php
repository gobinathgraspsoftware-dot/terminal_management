<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subject_id',
        'grade_level',
        'month',
        'year',
        'description',
        'quantity_available',
        'status',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'year' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function collections()
    {
        return $this->hasMany(PhysicalMaterialCollection::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
                     ->where('quantity_available', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock')
                     ->orWhere('quantity_available', '<=', 0);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->where('grade_level', $gradeLevel);
    }

    public function scopeByMonth($query, $month)
    {
        return $query->where('month', $month);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->quantity_available > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->status === 'out_of_stock' || $this->quantity_available <= 0;
    }

    public function decrementQuantity(int $amount = 1): void
    {
        $newQuantity = max(0, $this->quantity_available - $amount);
        
        $this->update([
            'quantity_available' => $newQuantity,
            'status' => $newQuantity > 0 ? 'available' : 'out_of_stock',
        ]);
    }

    public function incrementQuantity(int $amount = 1): void
    {
        $this->update([
            'quantity_available' => $this->quantity_available + $amount,
            'status' => 'available',
        ]);
    }
}
