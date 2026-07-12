<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_PRE_RESERVED = 'pre_reserved';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_SOLD = 'sold';
    public const LEGACY_STATUS_UNDER_MAINTENANCE = 'under_maintenance';

    public const MANUAL_STATUS_AVAILABLE = 'available';
    public const MANUAL_STATUS_UNAVAILABLE = 'unavailable';
    public const MANUAL_STATUS_SOLD = 'sold';

    public const UNAVAILABILITY_REASON_MAINTENANCE = 'maintenance';
    public const UNAVAILABILITY_REASON_SERVICE_OIL = 'service_oil';
    public const UNAVAILABILITY_REASON_AC_PROBLEM = 'ac_problem';
    public const UNAVAILABILITY_REASON_ACCIDENT = 'accident';
    public const UNAVAILABILITY_REASON_INSURANCE = 'insurance';
    public const UNAVAILABILITY_REASON_MANAGEMENT_DECISION = 'management_decision';
    public const UNAVAILABILITY_REASON_FOR_SALE = 'for_sale';
    public const UNAVAILABILITY_REASON_REGISTRATION = 'registration';
    public const UNAVAILABILITY_REASON_NEED_ACTION = 'need_action';

    private const RENTABLE_STATUSES = [self::STATUS_AVAILABLE, self::STATUS_PRE_RESERVED];
    private const RESERVATION_SELECTION_BLOCKED_STATUSES = [self::STATUS_SOLD, self::STATUS_UNAVAILABLE];
    private const MANUAL_STATUSES = [
        self::MANUAL_STATUS_AVAILABLE,
        self::MANUAL_STATUS_UNAVAILABLE,
        self::MANUAL_STATUS_SOLD,
    ];
    private const MANUAL_UNAVAILABILITY_REASONS = [
        self::UNAVAILABILITY_REASON_MAINTENANCE,
        self::UNAVAILABILITY_REASON_SERVICE_OIL,
        self::UNAVAILABILITY_REASON_AC_PROBLEM,
        self::UNAVAILABILITY_REASON_ACCIDENT,
        self::UNAVAILABILITY_REASON_INSURANCE,
        self::UNAVAILABILITY_REASON_MANAGEMENT_DECISION,
        self::UNAVAILABILITY_REASON_FOR_SALE,
        self::UNAVAILABILITY_REASON_REGISTRATION,
    ];
    private const UNAVAILABILITY_REASON_LABELS = [
        self::UNAVAILABILITY_REASON_MAINTENANCE => 'Maintenance',
        self::UNAVAILABILITY_REASON_SERVICE_OIL => 'Service Oil',
        self::UNAVAILABILITY_REASON_AC_PROBLEM => 'AC Problem',
        self::UNAVAILABILITY_REASON_ACCIDENT => 'Accident',
        self::UNAVAILABILITY_REASON_INSURANCE => 'Insurance',
        self::UNAVAILABILITY_REASON_MANAGEMENT_DECISION => 'Management Decision',
        self::UNAVAILABILITY_REASON_FOR_SALE => 'For Sale',
        self::UNAVAILABILITY_REASON_REGISTRATION => 'Registration',
        self::UNAVAILABILITY_REASON_NEED_ACTION => 'Need Action',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private static array $imageDirectoryCache = [];

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'car_model_id',
        'plate_number',
        'status',
        'manual_status',
        'manual_unavailability_reason',
        'availability',
        'unavailability_reason',
        'mileage',
        'price_per_day_short',
        'price_per_day_mid',
        'price_per_day_long',
        'ldw_price_short',
        'ldw_price_mid',
        'ldw_price_long',
        'scdw_price_short',
        'scdw_price_mid',
        'scdw_price_long',
        'ldw_price',
        'scdw_price',
        'service_due_date',
        'damage_report',
        'manufacturing_year',
        'color',
        'notes',
        'chassis_number',
        'gps',
        'is_company_car',
        'ownership_type',
        'issue_date',
        'expiry_date',
        'passing_date',
        'passing_valid_for_days',
        'passing_status',
        'registration_valid_for_days',
        'registration_status',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'availability' => 'boolean',
        'mileage' => 'integer',
        'price_per_day_short' => 'decimal:2',
        'price_per_day_mid' => 'decimal:2',
        'price_per_day_long' => 'decimal:2',
        'ldw_price_short' => 'decimal:2',
        'ldw_price_mid' => 'decimal:2',
        'ldw_price_long' => 'decimal:2',
        'scdw_price_short' => 'decimal:2',
        'scdw_price_mid' => 'decimal:2',
        'scdw_price_long' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'insurance_expiry_date' => 'date',
        'service_due_date' => 'date',
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'passing_date' => 'date',
        'manufacturing_year' => 'integer',
        'is_company_car' => 'boolean',
        'passing_valid_for_days' => 'integer',
        'registration_valid_for_days' => 'integer',
    ];

    private const OWNERSHIP_OPTIONS = [
        'company' => ['label' => 'Our Fleet', 'badge' => 'bg-label-primary'],
        'golden_key' => ['label' => 'Golden Key', 'badge' => 'bg-label-info'],
        'liverpool' => ['label' => 'Liverpool', 'badge' => 'bg-label-success'],
        'safe_drive' => ['label' => 'Safe Drive', 'badge' => 'bg-label-secondary'],
        'other' => ['label' => 'Other Fleet', 'badge' => 'bg-label-warning'],
    ];

    /**
     * متد برای بررسی وضعیت خودرو.
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, self::RENTABLE_STATUSES, true) && $this->availability;
    }

    public static function availabilityForStatus(?string $status): bool
    {
        return in_array($status, self::RENTABLE_STATUSES, true);
    }

    public static function manualStatusLabels(): array
    {
        return [
            self::MANUAL_STATUS_AVAILABLE => 'Available',
            self::MANUAL_STATUS_UNAVAILABLE => 'Unavailable',
            self::MANUAL_STATUS_SOLD => 'Sold',
        ];
    }

    public static function manualUnavailabilityReasonLabels(): array
    {
        $labels = [];

        foreach (self::MANUAL_UNAVAILABILITY_REASONS as $reason) {
            $labels[$reason] = self::UNAVAILABILITY_REASON_LABELS[$reason];
        }

        return $labels;
    }

    public static function unavailabilityReasonLabelFor(?string $reason): ?string
    {
        return self::UNAVAILABILITY_REASON_LABELS[$reason] ?? null;
    }

    public function unavailabilityReasonLabel(): ?string
    {
        return static::unavailabilityReasonLabelFor($this->unavailability_reason);
    }

    public function operationalStatusContextNote(?Carbon $now = null): ?string
    {
        $now ??= Carbon::now();

        if (
            $this->operationalStatus() === self::STATUS_UNAVAILABLE
            && $this->unavailability_reason === self::UNAVAILABILITY_REASON_NEED_ACTION
            && $this->hasUpcomingReservationWindow($now)
        ) {
            return 'Upcoming booking also exists.';
        }

        return null;
    }

    /**
     * @return array{manual_status: string, manual_unavailability_reason: string|null}
     */
    public static function manualStateAttributes(?string $status, ?string $reason): array
    {
        $status = in_array($status, self::MANUAL_STATUSES, true)
            ? $status
            : self::MANUAL_STATUS_AVAILABLE;

        if ($status === self::MANUAL_STATUS_SOLD) {
            return [
                'manual_status' => self::MANUAL_STATUS_SOLD,
                'manual_unavailability_reason' => null,
            ];
        }

        if ($status === self::MANUAL_STATUS_UNAVAILABLE) {
            return [
                'manual_status' => self::MANUAL_STATUS_UNAVAILABLE,
                'manual_unavailability_reason' => static::normalizeManualUnavailabilityReason($reason)
                    ?? self::UNAVAILABILITY_REASON_MANAGEMENT_DECISION,
            ];
        }

        return [
            'manual_status' => self::MANUAL_STATUS_AVAILABLE,
            'manual_unavailability_reason' => null,
        ];
    }

    /**
     * @return array{manual_status: string, manual_unavailability_reason: string|null}
     */
    public static function normalizedManualState(
        ?string $manualStatus,
        ?string $manualUnavailabilityReason,
        ?string $operationalStatus = null,
        bool $availability = true,
        ?string $operationalUnavailabilityReason = null
    ): array {
        if (in_array($manualStatus, self::MANUAL_STATUSES, true)) {
            return static::manualStateAttributes($manualStatus, $manualUnavailabilityReason);
        }

        if ($operationalStatus === self::STATUS_SOLD) {
            return static::manualStateAttributes(self::MANUAL_STATUS_SOLD, null);
        }

        if (
            in_array($operationalStatus, [self::STATUS_UNAVAILABLE, self::LEGACY_STATUS_UNDER_MAINTENANCE], true)
            || (! $availability && ! in_array($operationalStatus, [self::STATUS_RESERVED, self::STATUS_SOLD], true))
        ) {
            return static::manualStateAttributes(
                self::MANUAL_STATUS_UNAVAILABLE,
                static::fallbackManualUnavailabilityReason(
                    $operationalStatus,
                    $manualUnavailabilityReason ?? $operationalUnavailabilityReason
                )
            );
        }

        return static::manualStateAttributes(self::MANUAL_STATUS_AVAILABLE, null);
    }

    public function resolvedManualStatus(): string
    {
        return static::normalizedManualState(
            $this->manual_status,
            $this->manual_unavailability_reason,
            $this->status,
            (bool) $this->availability,
            $this->unavailability_reason
        )['manual_status'];
    }

    public function resolvedManualUnavailabilityReason(): ?string
    {
        return static::normalizedManualState(
            $this->manual_status,
            $this->manual_unavailability_reason,
            $this->status,
            (bool) $this->availability,
            $this->unavailability_reason
        )['manual_unavailability_reason'];
    }

    /**
     * @return array{status: string, availability: bool, unavailability_reason: string|null}
     */
    public static function synchronizedStateForReservationWindow(
        ?string $manualStatus,
        ?string $manualUnavailabilityReason,
        bool $hasActiveReservation,
        bool $hasUpcomingReservation,
        bool $needsAction = false
    ): array {
        $manualState = static::manualStateAttributes($manualStatus, $manualUnavailabilityReason);

        if ($manualState['manual_status'] === self::MANUAL_STATUS_SOLD) {
            return [
                'status' => self::STATUS_SOLD,
                'availability' => false,
                'unavailability_reason' => null,
            ];
        }

        if ($needsAction) {
            return [
                'status' => self::STATUS_UNAVAILABLE,
                'availability' => false,
                'unavailability_reason' => self::UNAVAILABILITY_REASON_NEED_ACTION,
            ];
        }

        if ($hasActiveReservation) {
            return [
                'status' => self::STATUS_RESERVED,
                'availability' => false,
                'unavailability_reason' => null,
            ];
        }

        if ($manualState['manual_status'] === self::MANUAL_STATUS_UNAVAILABLE) {
            return [
                'status' => self::STATUS_UNAVAILABLE,
                'availability' => false,
                'unavailability_reason' => $manualState['manual_unavailability_reason'],
            ];
        }

        if ($hasUpcomingReservation) {
            return [
                'status' => self::STATUS_PRE_RESERVED,
                'availability' => true,
                'unavailability_reason' => null,
            ];
        }

        return [
            'status' => self::STATUS_AVAILABLE,
            'availability' => true,
            'unavailability_reason' => null,
        ];
    }

    /**
     * @return array{status: string, availability: bool, unavailability_reason: string|null, manual_status: string, manual_unavailability_reason: string|null}
     */
    public function synchronizedOperationalState(?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        $manualState = static::normalizedManualState(
            $this->manual_status,
            $this->manual_unavailability_reason,
            $this->status,
            (bool) $this->availability,
            $this->unavailability_reason
        );
        $needsAction = $this->hasNeedActionReservationWindow($now);
        $hasActiveReservation = ! $needsAction && $this->hasActiveReservationWindow($now);
        $hasUpcomingReservation = ! $needsAction && ! $hasActiveReservation && $this->hasUpcomingReservationWindow($now);
        $operationalState = static::synchronizedStateForReservationWindow(
            $manualState['manual_status'],
            $manualState['manual_unavailability_reason'],
            $hasActiveReservation,
            $hasUpcomingReservation,
            $needsAction
        );

        return array_merge($operationalState, $manualState);
    }

    public function syncOperationalState(?Carbon $now = null): bool
    {
        $attributes = $this->synchronizedOperationalState($now);
        $currentAvailability = (bool) $this->availability;

        if (
            $this->status === $attributes['status']
            && $currentAvailability === $attributes['availability']
            && $this->unavailability_reason === $attributes['unavailability_reason']
            && $this->manual_status === $attributes['manual_status']
            && $this->manual_unavailability_reason === $attributes['manual_unavailability_reason']
        ) {
            return false;
        }

        $this->forceFill($attributes);
        $this->saveQuietly();

        return true;
    }

    public function operationalStatus(): string
    {
        return static::resolveOperationalStatus($this->status, $this->availability);
    }

    public static function resolveOperationalStatus(?string $status, bool $availability): string
    {
        if ($status === self::STATUS_SOLD) {
            return self::STATUS_SOLD;
        }

        if ($status === self::STATUS_RESERVED) {
            return self::STATUS_RESERVED;
        }

        if ($status === self::STATUS_PRE_RESERVED) {
            return self::STATUS_PRE_RESERVED;
        }

        if (
            $status === self::STATUS_UNAVAILABLE
            || $status === self::LEGACY_STATUS_UNDER_MAINTENANCE
            || ! $availability
        ) {
            return self::STATUS_UNAVAILABLE;
        }

        if ($status === self::STATUS_AVAILABLE) {
            return self::STATUS_AVAILABLE;
        }

        return $status ?: self::STATUS_UNAVAILABLE;
    }

    public function operationalStatusLabel(): string
    {
        return static::operationalStatusLabelFor($this->status, $this->availability);
    }

    public static function operationalStatusLabelFor(?string $status, bool $availability): string
    {
        return match (static::resolveOperationalStatus($status, $availability)) {
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_PRE_RESERVED => 'Upcoming booking',
            self::STATUS_RESERVED => 'Active booking',
            self::STATUS_SOLD => 'Sold',
            self::STATUS_UNAVAILABLE => 'Unavailable',
            default => ucfirst((string) static::resolveOperationalStatus($status, $availability)),
        };
    }

    public function operationalStatusTone(): string
    {
        return static::operationalStatusToneFor($this->status, $this->availability);
    }

    public static function operationalStatusToneFor(?string $status, bool $availability): string
    {
        return match (static::resolveOperationalStatus($status, $availability)) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_PRE_RESERVED => 'info',
            self::STATUS_RESERVED => 'warning',
            self::STATUS_SOLD => 'danger',
            self::STATUS_UNAVAILABLE => 'secondary',
            default => 'secondary',
        };
    }

    public function operationalStatusIcon(): string
    {
        return static::operationalStatusIconFor($this->status, $this->availability);
    }

    public static function operationalStatusIconFor(?string $status, bool $availability): string
    {
        return match (static::resolveOperationalStatus($status, $availability)) {
            self::STATUS_AVAILABLE => 'bx bx-check-circle',
            self::STATUS_PRE_RESERVED => 'bx bx-calendar-event',
            self::STATUS_RESERVED => 'bx bx-time-five',
            self::STATUS_SOLD => 'bx bx-error',
            self::STATUS_UNAVAILABLE => 'bx bx-block',
            default => 'bx bx-car',
        };
    }

    public function operationalStatusBadgeClass(): string
    {
        if ($this->operationalStatus() === self::STATUS_UNAVAILABLE) {
            return match ($this->unavailability_reason) {
                self::UNAVAILABILITY_REASON_MAINTENANCE,
                self::UNAVAILABILITY_REASON_SERVICE_OIL,
                self::UNAVAILABILITY_REASON_AC_PROBLEM,
                self::UNAVAILABILITY_REASON_ACCIDENT,
                self::UNAVAILABILITY_REASON_INSURANCE,
                self::UNAVAILABILITY_REASON_REGISTRATION,
                self::UNAVAILABILITY_REASON_NEED_ACTION => 'bg-danger',
                self::UNAVAILABILITY_REASON_FOR_SALE => 'bg-dark',
                default => 'bg-secondary',
            };
        }

        return match ($this->operationalStatus()) {
            self::STATUS_AVAILABLE => 'bg-success',
            self::STATUS_PRE_RESERVED => 'bg-info',
            self::STATUS_RESERVED => 'bg-warning',
            self::STATUS_SOLD => 'bg-dark',
            default => 'bg-secondary',
        };
    }

    public function operationalStatusSubtleBadgeClass(): string
    {
        return static::operationalStatusSubtleBadgeClassFor(
            $this->status,
            $this->availability,
            $this->unavailability_reason
        );
    }

    public static function operationalStatusSubtleBadgeClassFor(
        ?string $status,
        bool $availability,
        ?string $unavailabilityReason = null
    ): string {
        if (static::resolveOperationalStatus($status, $availability) === self::STATUS_UNAVAILABLE) {
            return match ($unavailabilityReason) {
                self::UNAVAILABILITY_REASON_MAINTENANCE,
                self::UNAVAILABILITY_REASON_SERVICE_OIL,
                self::UNAVAILABILITY_REASON_AC_PROBLEM,
                self::UNAVAILABILITY_REASON_ACCIDENT,
                self::UNAVAILABILITY_REASON_INSURANCE,
                self::UNAVAILABILITY_REASON_REGISTRATION,
                self::UNAVAILABILITY_REASON_NEED_ACTION => 'bg-danger-subtle text-danger',
                self::UNAVAILABILITY_REASON_FOR_SALE => 'bg-dark-subtle text-dark',
                default => 'bg-secondary-subtle text-secondary',
            };
        }

        return match (static::resolveOperationalStatus($status, $availability)) {
            self::STATUS_AVAILABLE => 'bg-success-subtle text-success',
            self::STATUS_PRE_RESERVED => 'bg-info-subtle text-info',
            self::STATUS_RESERVED => 'bg-warning-subtle text-warning',
            self::STATUS_SOLD => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary',
        };
    }

    public function scopeByOperationalStatus($query, string $status)
    {
        return match ($status) {
            self::STATUS_AVAILABLE => $query->where('status', self::STATUS_AVAILABLE)->where('availability', true),
            self::STATUS_PRE_RESERVED => $query->where('status', self::STATUS_PRE_RESERVED)->where('availability', true),
            self::STATUS_RESERVED => $query->where('status', self::STATUS_RESERVED),
            self::STATUS_SOLD => $query->where('status', self::STATUS_SOLD),
            self::LEGACY_STATUS_UNDER_MAINTENANCE => $query->where(function ($builder) {
                $builder->where('status', self::LEGACY_STATUS_UNDER_MAINTENANCE)
                    ->orWhere(function ($maintenanceBuilder) {
                        $maintenanceBuilder->where('status', self::STATUS_UNAVAILABLE)
                            ->where('unavailability_reason', self::UNAVAILABILITY_REASON_MAINTENANCE);
                    });
            }),
            self::STATUS_UNAVAILABLE => $query->where(function ($builder) {
                $builder->where('status', self::STATUS_UNAVAILABLE)
                    ->orWhere('status', self::LEGACY_STATUS_UNDER_MAINTENANCE)
                    ->orWhere(function ($legacyBuilder) {
                        $legacyBuilder->where('availability', false)
                            ->whereIn('status', self::RENTABLE_STATUSES);
                    });
            }),
            default => $query->where('status', $status),
        };
    }

    public function scopeReservableForSelection($query)
    {
        return $query
            ->where(function ($builder) {
                $builder->whereNull('manual_status')
                    ->orWhere('manual_status', self::MANUAL_STATUS_AVAILABLE);
            })
            ->where(function ($builder) {
                $builder->where('status', self::STATUS_RESERVED)
                    ->orWhere(function ($readyBuilder) {
                        $readyBuilder->whereIn('status', self::RENTABLE_STATUSES)
                            ->where('availability', true);
                    });
            });
    }

    public function isSelectableForReservation(): bool
    {
        if ($this->resolvedManualStatus() !== self::MANUAL_STATUS_AVAILABLE) {
            return false;
        }

        return static::isSelectableForReservationState($this->status, $this->availability, $this->unavailability_reason);
    }

    public static function isSelectableForReservationState(
        ?string $status,
        bool $availability,
        ?string $unavailabilityReason = null
    ): bool {
        return ! in_array(
            static::resolveOperationalStatus($status, $availability),
            self::RESERVATION_SELECTION_BLOCKED_STATUSES,
            true
        );
    }

    public function reservationSelectionBlockReason(): ?string
    {
        if ($this->resolvedManualStatus() === self::MANUAL_STATUS_SOLD) {
            return 'The selected car has been sold and cannot be used for reservations.';
        }

        if ($this->resolvedManualStatus() === self::MANUAL_STATUS_UNAVAILABLE) {
            return static::unavailableSelectionBlockReason($this->resolvedManualUnavailabilityReason());
        }

        return static::reservationSelectionBlockReasonFor($this->status, $this->availability, $this->unavailability_reason);
    }

    public static function reservationSelectionBlockReasonFor(
        ?string $status,
        bool $availability,
        ?string $unavailabilityReason = null
    ): ?string {
        return match (static::resolveOperationalStatus($status, $availability)) {
            self::STATUS_SOLD => 'The selected car has been sold and cannot be used for reservations.',
            self::STATUS_UNAVAILABLE => static::unavailableSelectionBlockReason($unavailabilityReason),
            default => null,
        };
    }

    /**
     * متد برای خودروهایی که نیاز به سرویس دارند.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsService($query)
    {
        return $query->whereDate('service_due_date', '<=', now());
    }

    /**
     * متد برای بررسی وضعیت بیمه خودرو.
     */
    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry_date && $this->insurance_expiry_date->isPast();
    }

    /**
     * متد برای نام کامل خودرو (مدل و پلاک).
     */
    public function modelName(): string
    {
        return optional($this->carModel)->fullName() ?? 'Vehicle';
    }

    public function nameWithPlate(): string
    {
        $name = $this->modelName();

        return $this->plate_number ? sprintf('%s (%s)', $name, $this->plate_number) : $name;
    }

    public function fullName(): string
    {
        return $this->nameWithPlate();
    }

    public function ownershipLabel(): string
    {
        $ownershipType = $this->ownershipType();

        return static::OWNERSHIP_OPTIONS[$ownershipType]['label'] ?? 'Other Fleet';
    }

    public function ownershipBadgeClass(): string
    {
        $ownershipType = $this->ownershipType();

        return static::OWNERSHIP_OPTIONS[$ownershipType]['badge'] ?? 'bg-label-secondary';
    }

    public function ownershipType(): string
    {
        if ($this->ownership_type) {
            return $this->ownership_type;
        }

        return $this->is_company_car ? 'company' : 'other';
    }

    /**
     * رابطه با مدل CarModel.
     */
    public function carModel()
    {
        return $this->belongsTo(CarModel::class, 'car_model_id');
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function hasNeedActionReservationWindow(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->contracts()
            ->whereIn('current_status', static::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<=', $now)
            ->whereNotNull('return_date')
            ->where('return_date', '<', $now)
            ->exists();
    }

    public function hasActiveReservationWindow(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->contracts()
            ->whereIn('current_status', static::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('return_date')->orWhere('return_date', '>=', $now);
            })
            ->exists();
    }

    public function hasUpcomingReservationWindow(?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        return $this->contracts()
            ->whereIn('current_status', static::reservingStatuses())
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>', $now)
            ->exists();
    }

    public function scopeWithoutActiveReservations($query)
    {
        $now = Carbon::now();
        $statuses = static::reservingStatuses();

        return $query->whereDoesntHave('contracts', function ($builder) use ($now, $statuses) {
            $builder->whereIn('current_status', $statuses)
                ->whereNotNull('pickup_date')
                ->where('pickup_date', '<=', $now)
                ->where(function ($timeQuery) use ($now) {
                    $timeQuery->whereNull('return_date')->orWhere('return_date', '>=', $now);
                });
        });
    }

    public function upcomingReservation()
    {
        $statuses = static::reservingStatuses();

        return $this->hasOne(Contract::class)
            ->whereIn('current_status', $statuses)
            ->whereNotNull('pickup_date')
            ->where('pickup_date', '>', Carbon::now())
            ->orderBy('pickup_date');
    }

    public static function reservingStatuses(): array
    {
        return [
            'pending',
            'assigned',
            'under_review',
            'reserved',
            'delivery',
            'inspection',
            'agreement_inspection',
            'awaiting_return',
        ];
    }

    public function hasReservingContracts(?int $exceptContractId = null): bool
    {
        $query = $this->contracts()
            ->whereIn('current_status', static::reservingStatuses());

        if ($exceptContractId) {
            $query->where('id', '!=', $exceptContractId);
        }

        return $query->exists();
    }

    /**
     * متد برای دریافت خودروهای با وضعیت خاص.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function insurance()
    {
        return $this->hasOne(Insurance::class);
    }

    public function currentContract()
    {
        return $this->hasOne(Contract::class)
            ->whereIn('current_status', static::reservingStatuses())
            ->orderBy('pickup_date');
    }

    public function options()
    {
        return $this->hasMany(CarOption::class);
    }

    public function primaryImageUrl(): string
    {
        if ($this->image) {
            $url = $this->resolveImageUrl($this->image);
            if ($url !== null) {
                return $url;
            }
        }

        if ($this->carModel?->image) {
            $url = $this->resolveImageUrl($this->carModel->image);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->publicAssetUrl('assets/car-pics/car test.webp');
    }

    private function resolveImageUrl(Image $image): ?string
    {
        $fileName = trim((string) $image->file_name);
        if ($fileName === '') {
            return null;
        }

        $path = trim((string) $image->file_path);
        $path = ltrim($path, '/');

        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, 'assets/')) {
            $path = 'assets/' . $path;
        }

        $path = rtrim($path, '/');
        $relativePath = $path . '/' . $fileName;

        if (! is_file(public_path($relativePath))) {
            $matchedPath = $this->resolveClosestImagePath($path, $fileName);
            if ($matchedPath === null) {
                return null;
            }

            return $this->publicAssetUrl($matchedPath);
        }

        return $this->publicAssetUrl($relativePath);
    }

    private function resolveClosestImagePath(string $basePath, string $requestedFileName): ?string
    {
        $candidateFiles = $this->imageFilesInDirectory($basePath);
        if ($candidateFiles === []) {
            return null;
        }

        $targetKey = $this->normalizeImageKey(pathinfo($requestedFileName, PATHINFO_FILENAME));
        if ($targetKey === '') {
            return null;
        }

        $bestMatch = null;
        $bestScore = PHP_INT_MAX;

        foreach ($candidateFiles as $candidateFileName) {
            $candidateKey = $this->normalizeImageKey(pathinfo($candidateFileName, PATHINFO_FILENAME));
            if ($candidateKey === '') {
                continue;
            }

            if ($candidateKey === $targetKey) {
                return $basePath . '/' . $candidateFileName;
            }

            if (! str_contains($candidateKey, $targetKey) && ! str_contains($targetKey, $candidateKey)) {
                continue;
            }

            $distance = levenshtein($targetKey, $candidateKey);
            $lengthPenalty = abs(strlen($candidateKey) - strlen($targetKey));
            $score = $distance + $lengthPenalty;

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidateFileName;
            }
        }

        if ($bestMatch === null) {
            return null;
        }

        return $basePath . '/' . $bestMatch;
    }

    /**
     * @return array<int, string>
     */
    private function imageFilesInDirectory(string $relativeDirectory): array
    {
        $normalizedDirectory = trim($relativeDirectory, '/');
        if (
            isset(self::$imageDirectoryCache[$normalizedDirectory])
            && self::$imageDirectoryCache[$normalizedDirectory] !== []
        ) {
            return self::$imageDirectoryCache[$normalizedDirectory];
        }

        $absoluteDirectory = public_path($normalizedDirectory);
        if (! is_dir($absoluteDirectory)) {
            self::$imageDirectoryCache[$normalizedDirectory] = [];

            return [];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $files = [];
        clearstatcache(true, $absoluteDirectory);

        foreach (scandir($absoluteDirectory) ?: [] as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '' || $entry === '.' || $entry === '..') {
                continue;
            }

            $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $entry;
            if (! is_file($absolutePath)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $files[] = $entry;
        }

        self::$imageDirectoryCache[$normalizedDirectory] = $files;

        return $files;
    }

    private function normalizeImageKey(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9]+/i', '', strtolower(trim($value)));
    }

    private function publicAssetUrl(string $relativePath): string
    {
        $segments = array_values(array_filter(explode('/', ltrim($relativePath, '/')), static fn (string $segment): bool => $segment !== ''));
        $encodedPath = implode('/', array_map(static fn (string $segment): string => rawurlencode($segment), $segments));

        return asset($encodedPath);
    }

    private static function normalizeManualUnavailabilityReason(?string $reason): ?string
    {
        return in_array($reason, self::MANUAL_UNAVAILABILITY_REASONS, true) ? $reason : null;
    }

    private static function fallbackManualUnavailabilityReason(?string $status, ?string $reason): string
    {
        if ($status === self::LEGACY_STATUS_UNDER_MAINTENANCE) {
            return self::UNAVAILABILITY_REASON_MAINTENANCE;
        }

        return static::normalizeManualUnavailabilityReason($reason)
            ?? self::UNAVAILABILITY_REASON_MANAGEMENT_DECISION;
    }

    private static function unavailableSelectionBlockReason(?string $reason): string
    {
        if ($reason === self::UNAVAILABILITY_REASON_NEED_ACTION) {
            return 'The selected car needs action because its contract return time has passed and the file is still open.';
        }

        $reasonLabel = static::unavailabilityReasonLabelFor($reason);

        if ($reasonLabel !== null) {
            return "The selected car is unavailable ({$reasonLabel}) and cannot be used for reservations until it is reactivated.";
        }

        return 'The selected car is marked unavailable and cannot be used for reservations until it is reactivated.';
    }
}
