<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetPriceWindowRate extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['value' => 'decimal:2', 'car_id' => 'integer', 'fleet_price_window_id' => 'integer'];

    public function window()
    {
        return $this->belongsTo(FleetPriceWindow::class, 'fleet_price_window_id');
    }
}
