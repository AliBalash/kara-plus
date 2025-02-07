<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'contract_id',
        'visa',
        'passport',
        'license',
        'ticket',
    ];

    // Relationship with Customer model
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relationship with Contract model
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
