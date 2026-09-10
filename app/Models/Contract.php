<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Contract extends Model
{
    use HasFactory;

    public const AMENDABLE_STATUSES = ['delivery', 'inspection', 'agreement_inspection', 'awaiting_return'];

    /** Small operational time corrections do not change the commercial ledger. */
    public const RETURN_TIME_TOLERANCE_MINUTES = 120;

    public const FINANCIALLY_IMMUTABLE_STATUSES = ['delivery', 'inspection', 'agreement_inspection', 'awaiting_return', 'returned', 'payment', 'complete'];

    private bool $commercialMutationAuthorized = false;

    public const STATUS_REVIEW_PENDING = 'review_pending';

    public const INTAKE_SOURCE_PANEL = 'panel';

    public const INTAKE_SOURCE_WEBSITE = 'website';

    public const CUSTOMER_BALANCE_EXCLUDED_STATUSES = ['cancelled', 'rejected', self::STATUS_REVIEW_PENDING];

    public const COMMUNICATION_CHANNELS = [
        'google_ads',
        'meta_ads',
        'whatsapp',
        'telegram',
        'instagram',
        'dubizzle',
        'one_click',
        'youtube',
        'snapchat',
        'tiktok',
        'influencer',
        'google_search',
        'invygo',
    ];

    public const COMMUNICATION_CHANNEL_ALIASES = [
        'invigo' => 'invygo',
    ];

    public const COMMUNICATION_CHANNEL_LABELS = [
        'google_ads' => 'Google Ads',
        'meta_ads' => 'Meta Ads',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'instagram' => 'Instagram',
        'dubizzle' => 'Dubizzle',
        'one_click' => 'One Click',
        'youtube' => 'YouTube',
        'snapchat' => 'Snapchat',
        'tiktok' => 'TikTok',
        'influencer' => 'Influencer',
        'google_search' => 'Google Search',
        'invygo' => 'Invygo',
    ];

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'car_id',
        'requested_car_id',
        'agent_id',
        'intake_source',
        'public_request_uuid',
        'communication_channel',
        'submitted_by_name',
        'pickup_date',
        'pickup_location',
        'return_location',
        'return_date',
        'original_return_date',
        'actual_pickup_at',
        'actual_return_at',
        'total_price',
        'kardo_required',
        'current_status',
        'notes',
        'meta',
        'payment_on_delivery',
        'used_daily_rate',
        'custom_daily_rate_enabled',
        'discount_note',
        'delivery_driver_id',
        'return_driver_id',
        'licensed_driver_name',
        'deposit',
        'deposit_category',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'original_return_date' => 'datetime',
        'actual_pickup_at' => 'datetime',
        'actual_return_at' => 'datetime',
        'total_price' => 'decimal:2',
        'kardo_required' => 'boolean',
        'payment_on_delivery' => 'boolean',
        'custom_daily_rate_enabled' => 'boolean',
        'meta' => 'array',
    ];

    protected function communicationChannel(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::normalizeCommunicationChannel($value),
            set: fn (?string $value) => self::normalizeCommunicationChannel($value),
        );
    }

    public static function normalizeCommunicationChannel(?string $channel): ?string
    {
        if ($channel === null || $channel === '') {
            return $channel;
        }

        return self::COMMUNICATION_CHANNEL_ALIASES[$channel] ?? $channel;
    }

    public static function communicationChannelLabel(?string $channel): string
    {
        $normalizedChannel = self::normalizeCommunicationChannel($channel);

        if ($normalizedChannel === null || $normalizedChannel === '') {
            return '—';
        }

        return self::COMMUNICATION_CHANNEL_LABELS[$normalizedChannel]
            ?? Str::headline(str_replace('_', ' ', $normalizedChannel));
    }

    /**
     * متد برای دریافت وضعیت قرارداد.
     */
    public function statusLabel(): string
    {
        return ucfirst($this->current_status); // نمایش وضعیت قرارداد با حرف اول بزرگ
    }

    /**
     * متد برای بررسی وضعیت قرارداد (فعال یا تکمیل شده).
     */
    public function isActive(): bool
    {
        return $this->current_status === 'assigned' || $this->current_status === 'under_review' || $this->current_status === 'delivery';
    }

    /**
     * متد برای بررسی اینکه قرارداد کامل شده است یا خیر.
     */
    public function isCompleted(): bool
    {
        return $this->current_status === 'complete';
    }

    public function scopeIncludedInCustomerBalance($query)
    {
        return $query->whereNotIn('current_status', self::CUSTOMER_BALANCE_EXCLUDED_STATUSES);
    }

    public function isIncludedInCustomerBalance(): bool
    {
        return ! in_array($this->current_status, self::CUSTOMER_BALANCE_EXCLUDED_STATUSES, true);
    }

    /**
     * رابطه با مدل User (کارشناس).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryDriver()
    {
        return $this->belongsTo(User::class, 'delivery_driver_id');
    }

    public function returnDriver()
    {
        return $this->belongsTo(User::class, 'return_driver_id');
    }

    public function latestStatus()
    {
        return $this->hasOne(ContractStatus::class)->latestOfMany();
    }

    /**
     * رابطه با مدل Customer (مشتری).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * رابطه با مدل Car (خودرو).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function requestedCar()
    {
        return $this->belongsTo(Car::class, 'requested_car_id');
    }

    public function isReviewPending(): bool
    {
        return $this->current_status === self::STATUS_REVIEW_PENDING;
    }

    public function isWebsiteIntake(): bool
    {
        return $this->intake_source === self::INTAKE_SOURCE_WEBSITE;
    }

    public function calculateRemainingBalance($payments = null)
    {
        if (is_null($payments)) {
            $payments = $this->relationLoaded('payments')
                ? $this->payments
                : $this->payments()->get();
        }

        $rentalPaid = $payments->where('payment_type', 'rental_fee')->sum('amount_in_aed');
        $discounts = $payments->where('payment_type', 'discount')->sum('amount_in_aed');
        $securityDeposit = $payments->where('payment_type', 'security_deposit')->sum('amount_in_aed');
        $finePaid = $payments->where('payment_type', 'fine')->sum('amount_in_aed');
        $legacySalik = $payments->where('payment_type', 'salik')->sum('amount_in_aed');
        $salikTripCharges = $payments
            ->whereIn('payment_type', Payment::salikTripPaymentTypeKeys())
            ->sum('amount_in_aed');
        $salikOther = $payments->where('payment_type', 'salik_other_revenue');
        $salikOtherRevenue = $salikOther->sum('amount_in_aed');
        $salik = $salikTripCharges + $legacySalik;
        $parking = $payments->where('payment_type', 'parking')->sum('amount_in_aed');
        $damage = $payments->where('payment_type', 'damage')->sum('amount_in_aed');
        $paymentBack = $payments->where('payment_type', 'payment_back')->sum('amount_in_aed');
        $carwash = $payments->where('payment_type', 'carwash')->sum('amount_in_aed');
        $fuel = $payments->where('payment_type', 'fuel')->sum('amount_in_aed');
        $noDepositFee = $payments->where('payment_type', 'no_deposit_fee')->sum('amount_in_aed');

        $effectivePaid = $rentalPaid - $paymentBack;

        $balance = (float) $this->total_price
            - ($effectivePaid + $discounts + $securityDeposit)
            + $finePaid + $salik + $salikOtherRevenue + $parking + $damage + $carwash + $fuel + $noDepositFee;

        $incomingTransfers = $this->relationLoaded('incomingBalanceTransfers')
            ? (float) $this->incomingBalanceTransfers->sum('amount')
            : (float) $this->incomingBalanceTransfers()->sum('amount');

        $outgoingTransfers = $this->relationLoaded('outgoingBalanceTransfers')
            ? (float) $this->outgoingBalanceTransfers->sum('amount')
            : (float) $this->outgoingBalanceTransfers()->sum('amount');

        $balance = $balance + $incomingTransfers - $outgoingTransfers;

        return round($balance, 2);
    }

    // همه‌ی آیتم‌های قیمت
    public function charges()
    {
        return $this->hasMany(ContractCharges::class);
    }

    /** Commercial changes are durable children of the operational contract. */
    public function amendments()
    {
        return $this->hasMany(ContractAmendment::class)->orderBy('sequence_no');
    }

    /**
     * متد برای محاسبه قیمت نهایی قرارداد با توجه به روزهای اجاره.
     */
    public function calculateTotalPrice(): float
    {
        $days = $this->pickup_date->diffInDays($this->return_date ?? now());
        $dailyRate = (float) ($this->car->price_per_day ?? 0);

        return round($days * $dailyRate, 2);
    }

    // Relationship with CustomerDocument model
    public function customerDocument()
    {
        return $this->hasOne(CustomerDocument::class);
    }

    // Relationship with Payment model
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function incomingBalanceTransfers()
    {
        return $this->hasMany(ContractBalanceTransfer::class, 'to_contract_id');
    }

    public function outgoingBalanceTransfers()
    {
        return $this->hasMany(ContractBalanceTransfer::class, 'from_contract_id');
    }

    // ارتباط با تاریخچه وضعیت‌ها
    public function statuses()
    {
        return $this->hasMany(ContractStatus::class);
    }

    // تغییر وضعیت درخواست
    public function changeStatus($newStatus, $userId, $notes = null)
    {
        $this->statuses()->create([
            'status' => $newStatus,
            'user_id' => $userId,
            'notes' => $notes,
        ]);

        $this->update(['current_status' => $newStatus]);

        if ($newStatus === 'delivery' && $this->actual_pickup_at === null) {
            $this->update(['actual_pickup_at' => now()]);
        }

        // اگر وضعیت به pending تغییر کرد
        if ($newStatus === 'pending') {
            $this->initializeContract();
        }

        // اگر وضعیت به complete تغییر کرد
        if ($newStatus === 'complete') {
            $this->finalizeContract();
        }
    }

    // متد جدید برای ثبت تاریخ شروع قرارداد
    public function initializeContract()
    {
        if (! $this->pickup_date) {
            $this->update([
                'pickup_date' => now(),  // ثبت تاریخ شروع قرارداد
            ]);
        }
    }

    // متد برای نهایی‌سازی درخواست
    public function finalizeContract()
    {
        if ($this->actual_return_at === null) {
            $this->update([
                // return_date remains the current planned return. The actual
                // timestamp is written once and never moved by settlement.
                'actual_return_at' => now(),
            ]);
        }
    }

    /** Apply an approved commercial extension while keeping normal edits locked. */
    public function applyApprovedExtension($newReturnAt, float $newTotalPrice): void
    {
        $this->commercialMutationAuthorized = true;

        try {
            $this->update([
                'return_date' => $newReturnAt,
                'total_price' => round($newTotalPrice, 2),
            ]);
        } finally {
            $this->commercialMutationAuthorized = false;
        }
    }

    /** Apply an audited commercial correction without mutating charge history. */
    public function applyApprovedCommercialCorrection(array $attributes): void
    {
        $allowed = Arr::only($attributes, [
            'car_id',
            'pickup_date',
            'return_date',
            'pickup_location',
            'return_location',
            'total_price',
            'used_daily_rate',
            'custom_daily_rate_enabled',
            'discount_note',
            'kardo_required',
            'payment_on_delivery',
            'meta',
        ]);

        $this->commercialMutationAuthorized = true;

        try {
            $this->update($allowed);
        } finally {
            $this->commercialMutationAuthorized = false;
        }
    }

    /**
     * Correct operational planning details without rewriting the financial
     * ledger. A return increase beyond the time tolerance is an amendment.
     */
    public function applyOperationalScheduleAndLocationCorrections(
        $newPickupAt,
        $newReturnAt,
        ?string $pickupLocation,
        ?string $returnLocation
    ): void {
        $newReturnAt = Carbon::parse($newReturnAt);
        $newPickupAt = Carbon::parse($newPickupAt);
        $currentReturnAt = Carbon::parse($this->return_date);
        if ($newReturnAt->lessThanOrEqualTo($newPickupAt)) {
            throw new \DomainException('The planned return must be after the pickup time.');
        }

        if ($newReturnAt->greaterThan($currentReturnAt)
            && $currentReturnAt->diffInMinutes($newReturnAt) > self::RETURN_TIME_TOLERANCE_MINUTES) {
            throw new \DomainException('A return increase beyond the two-hour tolerance must be handled through an extension.');
        }

        $this->commercialMutationAuthorized = true;

        try {
            $this->update([
                'pickup_date' => $newPickupAt,
                'return_date' => $newReturnAt,
                'pickup_location' => $pickupLocation,
                'return_location' => $returnLocation,
            ]);
        } finally {
            $this->commercialMutationAuthorized = false;
        }
    }

    public function pickupDocument()
    {
        return $this->hasOne(PickupDocument::class);
    }

    /**
     * Agreement numbers belong to the pickup document, but are part of the
     * contract reference that staff use throughout the panel.
     */
    public function getAgreementNumberAttribute(): ?string
    {
        return $this->pickupDocument?->agreement_number;
    }

    /**
     * Match the two identifiers that can be used to locate a contract.
     *
     * This scope is deliberately relation-based instead of joining pickup
     * documents, so it remains correct for contracts that do not yet have an
     * agreement number.
     */
    public function scopeWhereReferenceLike(Builder $query, string $like): Builder
    {
        return $query
            ->where('contracts.id', 'like', $like)
            ->orWhereHas('pickupDocument', fn (Builder $documentQuery) => $documentQuery
                ->where('agreement_number', 'like', $like));
    }

    public function returnDocument()
    {
        return $this->hasOne(ReturnDocument::class);
    }

    protected static function booted(): void
    {
        static::created(function (Contract $contract) {
            if ($contract->return_date && $contract->original_return_date === null) {
                $contract->updateQuietly(['original_return_date' => $contract->return_date]);
            }
            $contract->syncCarAvailabilityForCar($contract->car_id);
        });

        static::updated(function (Contract $contract) {
            if ($contract->wasChanged('car_id')) {
                $contract->syncCarAvailabilityForCar($contract->getOriginal('car_id'));
            }

            $contract->syncCarAvailabilityForCar($contract->car_id);
        });

        static::updating(function (Contract $contract): void {
            if ($contract->isDirty('original_return_date')
                && $contract->getOriginal('original_return_date') !== null) {
                throw new \DomainException('The original contract return date is immutable.');
            }

            $commercialFields = [
                'customer_id',
                'car_id',
                'pickup_date',
                'return_date',
                'total_price',
                'used_daily_rate',
                'custom_daily_rate_enabled',
                'discount_note',
            ];

            if (! $contract->commercialMutationAuthorized
                && in_array($contract->getOriginal('current_status'), self::FINANCIALLY_IMMUTABLE_STATUSES, true)
                && $contract->isDirty($commercialFields)) {
                throw new \DomainException('Operational contract commercial terms are immutable. Use an amendment.');
            }
        });

        static::deleting(function (Contract $contract): void {
            if (in_array($contract->current_status, self::FINANCIALLY_IMMUTABLE_STATUSES, true)
                || $contract->amendments()->exists()
                || $contract->payments()->exists()) {
                throw new \DomainException('Operational or financial contracts cannot be deleted. Cancel or archive the contract instead.');
            }
        });

        static::deleted(function (Contract $contract) {
            $contract->syncCarAvailabilityForCar($contract->car_id);
        });
    }

    private function syncCarAvailabilityForCar(?int $carId): void
    {
        if (! $carId) {
            return;
        }

        $car = Car::find($carId);

        if (! $car) {
            return;
        }

        $car->syncOperationalState();
    }
}
