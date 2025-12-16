<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractBalanceTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_contract_id',
        'to_contract_id',
        'customer_id',
        'created_by',
        'amount',
        'currency',
        'reference',
        'meta',
        'notes',
        'transferred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'transferred_at' => 'datetime',
    ];

    public function fromContract()
    {
        return $this->belongsTo(Contract::class, 'from_contract_id');
    }

    public function toContract()
    {
        return $this->belongsTo(Contract::class, 'to_contract_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
