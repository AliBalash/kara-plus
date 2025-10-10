<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'user_id',
        'tars_contract',
        'kardo_contract',
        'factor_contract',
        'car_dashboard',
        'car_inside_photos',
        'car_outside_photos',
        'fuelLevel',
        'mileage',
        'tars_approved_at',
        'tars_approved_by',
        'kardo_approved_at',
        'kardo_approved_by',
        'note',
        'driver_note',
    ];

    protected $casts = [
        'car_inside_photos' => 'array',
        'car_outside_photos' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
