<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetPriceWindow extends Model
{
    protected $guarded = [];

    protected $casts = ['active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date'];

    public function rates()
    {
        return $this->hasMany(FleetPriceWindowRate::class);
    }
}
