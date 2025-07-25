<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractCharges extends Model
{
    protected $fillable = [
        'contract_id',
        'title',
        'amount',
        'type',
        'description',
    ];

    // رابطه با قرارداد
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
