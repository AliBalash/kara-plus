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
        'car_inside_video',
        'car_outside_video',
        'fuelLevel',
        'mileage',
        'tars_approved_at',
        'tars_approved_by',
        'kardo_approved_at',
        'kardo_approved_by',
        'note',
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
