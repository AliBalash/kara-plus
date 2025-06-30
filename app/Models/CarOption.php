<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarOption extends Model
{
    protected $fillable = ['car_id', 'option_key', 'option_value'];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
