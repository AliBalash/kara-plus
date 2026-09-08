<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractCharges extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'amendment_id',
        'title',
        'amount',
        'type',
        'description',
        'source_type',
        'quantity',
        'unit',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'effective_from',
        'effective_to',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'metadata' => 'array',
    ];

    // رابطه با قرارداد
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function amendment()
    {
        return $this->belongsTo(ContractAmendment::class, 'amendment_id');
    }

    protected static function booted(): void
    {
        static::creating(function (ContractCharges $charge): void {
            // Legacy create paths remain compatible, but every new row has an
            // explicit origin. Amendment charges always set this themselves.
            if ($charge->source_type === null) {
                $charge->source_type = $charge->amendment_id ? 'amendment' : 'original';
            }

            if (($charge->source_type === 'amendment') !== ($charge->amendment_id !== null)) {
                throw new \DomainException('Amendment charges must reference an amendment, and original charges must not.');
            }

            if ($charge->source_type === 'original'
                && in_array($charge->currentContractStatus(), Contract::FINANCIALLY_IMMUTABLE_STATUSES, true)) {
                throw new \DomainException('Original charges cannot be added after a contract becomes operational.');
            }
        });

        static::updating(function (ContractCharges $charge): void {
            if ($charge->isDirty(['contract_id', 'amendment_id', 'source_type'])
                || $charge->source_type === 'amendment'
                || in_array($charge->currentContractStatus(), Contract::FINANCIALLY_IMMUTABLE_STATUSES, true)) {
                throw new \DomainException('Operational contract charges are immutable.');
            }
        });

        static::deleting(function (ContractCharges $charge): void {
            if ($charge->source_type === 'amendment'
                || in_array($charge->currentContractStatus(), Contract::FINANCIALLY_IMMUTABLE_STATUSES, true)) {
                throw new \DomainException('Operational contract charges cannot be deleted.');
            }
        });
    }

    private function currentContractStatus(): ?string
    {
        return Contract::query()->whereKey($this->contract_id)->value('current_status');
    }
}
