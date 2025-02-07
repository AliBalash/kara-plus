<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractStatus extends Model
{
    protected $fillable = ['contract_id', 'status', 'user_id', 'notes'];

    // ارتباط با قرارداد
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    // ارتباط با کاربر (کسی که تغییر داده)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
