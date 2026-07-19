<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CarUnavailabilityPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'reason',
        'note',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_note',
        'resolved_at',
        'resolved_by',
        'resolution_note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    private static ?bool $tableExistsCache = null;
    private static ?bool $cancellationColumnsExistCache = null;
    private static ?bool $resolutionColumnsExistCache = null;

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        if (! static::supportsCancellationColumns()) {
            return $query;
        }

        return $query->whereNull('cancelled_at');
    }

    public function scopeCancelled($query)
    {
        if (! static::supportsCancellationColumns()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNotNull('cancelled_at');
    }

    public function scopeUnresolved($query)
    {
        if (! static::supportsResolutionColumns()) {
            return $query;
        }

        return $query->whereNull('resolved_at');
    }

    public function scopeExpiredBefore($query, Carbon|string|null $date = null)
    {
        $dateValue = ($date instanceof Carbon ? $date : Carbon::parse($date ?? Carbon::today()))->toDateString();

        return $query
            ->open()
            ->unresolved()
            ->whereDate('end_date', '<', $dateValue);
    }

    public function scopeActiveOn($query, Carbon|string|null $date = null)
    {
        $dateValue = ($date instanceof Carbon ? $date : Carbon::parse($date ?? Carbon::today()))->toDateString();

        return $query
            ->open()
            ->whereDate('start_date', '<=', $dateValue)
            ->whereDate('end_date', '>=', $dateValue);
    }

    public function scopeUpcomingFrom($query, Carbon|string|null $date = null)
    {
        $dateValue = ($date instanceof Carbon ? $date : Carbon::parse($date ?? Carbon::today()))->toDateString();

        return $query
            ->open()
            ->whereDate('start_date', '>', $dateValue);
    }

    public function scopeOverlappingWindow($query, Carbon $start, Carbon $end)
    {
        return $query
            ->open()
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());
    }

    public function reasonLabel(): ?string
    {
        return Car::unavailabilityReasonLabelFor($this->reason);
    }

    public function state(?Carbon $date = null): string
    {
        $date ??= Carbon::today();

        if ($this->isCancelled()) {
            return 'cancelled';
        }

        if ($this->start_date && $this->end_date && $date->betweenIncluded($this->start_date, $this->end_date)) {
            return 'active';
        }

        if ($this->start_date && $this->start_date->greaterThan($date)) {
            return 'upcoming';
        }

        if (static::supportsResolutionColumns() && ! $this->isResolved()) {
            return 'needs_action';
        }

        return 'completed';
    }

    public function stateLabel(?Carbon $date = null): string
    {
        return match ($this->state($date)) {
            'active' => 'Active',
            'upcoming' => 'Upcoming',
            'cancelled' => 'Cancelled',
            'needs_action' => 'Need Action',
            default => 'Completed',
        };
    }

    public function stateBadgeClass(?Carbon $date = null): string
    {
        return match ($this->state($date)) {
            'active' => 'bg-label-danger',
            'upcoming' => 'bg-label-info',
            'cancelled' => 'bg-label-warning',
            'needs_action' => 'bg-label-danger',
            default => 'bg-label-secondary',
        };
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function cancel(?int $userId = null, ?string $note = null): bool
    {
        if (! static::supportsCancellationColumns()) {
            return false;
        }

        if ($this->isCancelled()) {
            return false;
        }

        return $this->forceFill([
            'cancelled_at' => Carbon::now(),
            'cancelled_by' => $userId ?? Auth::id(),
            'cancellation_note' => $note,
            'updated_by' => $userId ?? Auth::id(),
        ])->save();
    }

    public function isResolved(): bool
    {
        return static::supportsResolutionColumns() && $this->resolved_at !== null;
    }

    public function resolve(?int $userId = null, ?string $note = null): bool
    {
        if (! static::supportsResolutionColumns() || $this->isCancelled() || $this->isResolved()) {
            return false;
        }

        return $this->forceFill([
            'resolved_at' => Carbon::now(),
            'resolved_by' => $userId ?? Auth::id(),
            'resolution_note' => $note,
            'updated_by' => $userId ?? Auth::id(),
        ])->save();
    }

    public function dateWindowLabel(): string
    {
        $start = $this->start_date?->format('Y-m-d') ?? '—';
        $end = $this->end_date?->format('Y-m-d') ?? '—';

        return $start . ' → ' . $end;
    }

    public static function tableExists(): bool
    {
        if (self::$tableExistsCache !== null) {
            return self::$tableExistsCache;
        }

        try {
            return self::$tableExistsCache = Schema::hasTable('car_unavailability_periods');
        } catch (\Throwable) {
            return self::$tableExistsCache = false;
        }
    }

    public static function supportsCancellationColumns(): bool
    {
        if (self::$cancellationColumnsExistCache !== null) {
            return self::$cancellationColumnsExistCache;
        }

        try {
            return self::$cancellationColumnsExistCache = Schema::hasColumn('car_unavailability_periods', 'cancelled_at')
                && Schema::hasColumn('car_unavailability_periods', 'cancelled_by')
                && Schema::hasColumn('car_unavailability_periods', 'cancellation_note');
        } catch (\Throwable) {
            return self::$cancellationColumnsExistCache = false;
        }
    }

    public static function supportsResolutionColumns(): bool
    {
        if (self::$resolutionColumnsExistCache !== null) {
            return self::$resolutionColumnsExistCache;
        }

        try {
            return self::$resolutionColumnsExistCache = Schema::hasColumn('car_unavailability_periods', 'resolved_at')
                && Schema::hasColumn('car_unavailability_periods', 'resolved_by')
                && Schema::hasColumn('car_unavailability_periods', 'resolution_note');
        } catch (\Throwable) {
            return self::$resolutionColumnsExistCache = false;
        }
    }
}
