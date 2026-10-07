<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractPaymentPeriod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contract_id', 'title', 'starts_on', 'ends_on', 'is_default',
        'created_by', 'updated_by', 'archived_by',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_default' => 'boolean',
    ];

    // Keep DATE values date-only on SQLite as well as MySQL, preserving exact adjacency.
    protected function startsOn(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    protected function endsOn(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }
}
