<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_FOLLOW_UP = 'follow_up';
    public const STATUS_INTERESTED = 'interested';
    public const STATUS_NOT_INTERESTED = 'not_interested';
    public const STATUS_UNREACHABLE = 'unreachable';
    public const STATUS_CONVERTED = 'converted';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'messenger_phone',
        'email',
        'source',
        'discovery_source',
        'requested_vehicle',
        'pickup_date',
        'return_date',
        'priority',
        'status',
        'assigned_to',
        'created_by',
        'next_follow_up_at',
        'last_contacted_at',
        'notes',
        'customer_id',
        'converted_by',
        'converted_at',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'return_date' => 'date',
        'next_follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_FOLLOW_UP => 'Follow-up',
            self::STATUS_INTERESTED => 'Interested',
            self::STATUS_NOT_INTERESTED => 'Not interested',
            self::STATUS_UNREACHABLE => 'Unreachable',
            self::STATUS_CONVERTED => 'Converted',
        ];
    }

    public static function priorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public function displayName(): string
    {
        $name = trim($this->first_name . ' ' . $this->last_name);

        return $name !== '' ? $name : $this->phone;
    }

    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED || $this->customer_id !== null;
    }

    public function isFollowUpDue(): bool
    {
        return ! $this->isConverted()
            && $this->next_follow_up_at !== null
            && $this->next_follow_up_at->isPast();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }
}
