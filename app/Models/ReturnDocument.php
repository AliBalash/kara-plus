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
        'car_video',
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
