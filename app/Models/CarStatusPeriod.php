<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CarStatusPeriod extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AUTOMATIC = 'automatic';
    public const SOURCE_MIGRATION = 'migration';

    protected $fillable = [
        'car_id',
        'status',
        'availability',
        'reason',
        'manual_status',
        'manual_reason',
        'source',
        'note',
        'started_at',
        'ended_at',
        'started_by',
        'ended_by',
        'trigger_type',
        'trigger_id',
        'metadata',
    ];

    protected $casts = [
        'availability' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    private static ?bool $tableExistsCache = null;

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function starter()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function ender()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('ended_at');
    }

    public function close(?Carbon $endedAt = null, ?int $userId = null): bool
    {
        if ($this->ended_at !== null) {
            return false;
        }

        return $this->forceFill([
            'ended_at' => $endedAt ?? Carbon::now(),
            'ended_by' => $userId ?? Auth::id(),
        ])->save();
    }

    public function statusLabel(): string
    {
        return Car::operationalStatusLabelFor($this->status, (bool) $this->availability);
    }

    public function reasonLabel(): ?string
    {
        return Car::unavailabilityReasonLabelFor($this->reason);
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_AUTOMATIC => 'Automatic',
            self::SOURCE_MIGRATION => 'Migration',
            default => 'Manual',
        };
    }

    public function sourceBadgeClass(): string
    {
        return match ($this->source) {
            self::SOURCE_AUTOMATIC => 'bg-info-subtle text-info',
            self::SOURCE_MIGRATION => 'bg-secondary-subtle text-secondary',
            default => 'bg-primary-subtle text-primary',
        };
    }

    public function actorName(): string
    {
        if ($this->starter) {
            $name = trim($this->starter->fullName());

            if ($name !== '') {
                return $name;
            }

            return $this->starter->email ?: 'User #' . $this->starter->id;
        }

        return $this->source === self::SOURCE_AUTOMATIC ? 'System' : '—';
    }

    public function actorInitials(): string
    {
        if (! $this->starter) {
            return $this->source === self::SOURCE_AUTOMATIC ? 'SY' : '—';
        }

        $first = trim((string) $this->starter->first_name);
        $last = trim((string) $this->starter->last_name);
        $initials = mb_substr($first, 0, 1, 'UTF-8') . mb_substr($last, 0, 1, 'UTF-8');

        return mb_strtoupper($initials !== '' ? $initials : mb_substr((string) $this->starter->email, 0, 2, 'UTF-8'), 'UTF-8');
    }

    public function durationLabel(): string
    {
        if (! $this->started_at) {
            return '—';
        }

        $end = $this->ended_at ?? Carbon::now();

        return $this->started_at->diffForHumans($end, [
            'parts' => 2,
            'join' => true,
            'syntax' => Carbon::DIFF_ABSOLUTE,
        ]);
    }

    public static function tableExists(): bool
    {
        if (static::$tableExistsCache !== null) {
            return static::$tableExistsCache;
        }

        try {
            return static::$tableExistsCache = Schema::hasTable('car_status_periods');
        } catch (\Throwable) {
            return static::$tableExistsCache = false;
        }
    }
}
