<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'under_3_fee',
        'over_3_fee',
        'is_active',
    ];

    protected $casts = [
        'under_3_fee' => 'decimal:2',
        'over_3_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
