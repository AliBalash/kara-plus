<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnDocument extends Model
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
