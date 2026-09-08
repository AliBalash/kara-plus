<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractAmendment extends Model
{
    use HasFactory;

    public const TYPE_EXTENSION = 'extension';

    public const STATUSES = ['draft', 'pending_approval', 'approved', 'rejected', 'cancelled'];

    public const TYPES = ['extension', 'early_return', 'rate_change', 'insurance_change', 'vehicle_change', 'adjustment'];

    protected $fillable = ['contract_id', 'sequence_no', 'type', 'status', 'requested_by', 'requested_at', 'approved_by', 'approved_at', 'effective_at', 'old_return_at', 'new_return_at', 'extension_start_at', 'extension_end_at', 'currency', 'pricing_policy', 'subtotal', 'tax_amount', 'total_amount', 'before_snapshot', 'after_snapshot', 'pricing_snapshot', 'reason', 'notes', 'idempotency_key'];

    protected $casts = [
        'requested_at' => 'datetime', 'approved_at' => 'datetime', 'effective_at' => 'datetime',
        'old_return_at' => 'datetime', 'new_return_at' => 'datetime', 'extension_start_at' => 'datetime', 'extension_end_at' => 'datetime',
        'subtotal' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
        'before_snapshot' => 'array', 'after_snapshot' => 'array', 'pricing_snapshot' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function charges()
    {
        return $this->hasMany(ContractCharges::class, 'amendment_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    protected static function booted(): void
    {
        static::updating(function (ContractAmendment $amendment): void {
            if ($amendment->getOriginal('status') === 'approved') {
                throw new \DomainException('Approved amendments are immutable.');
            }
        });

        static::deleting(function (): void {
            throw new \DomainException('Amendments are business history and cannot be deleted.');
        });
    }
}
