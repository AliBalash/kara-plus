<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'car_model_id',
        'plate_number',
        'status',
        'availability',
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
        'manufacturing_year' => 'integer',
        'is_company_car' => 'boolean',
    ];

    private const OWNERSHIP_OPTIONS = [
        'company' => ['label' => 'Our Fleet', 'badge' => 'bg-label-primary'],
        'golden_key' => ['label' => 'Golden Key', 'badge' => 'bg-label-info'],
        'liverpool' => ['label' => 'Liverpool', 'badge' => 'bg-label-success'],
        'other' => ['label' => 'Other Fleet', 'badge' => 'bg-label-warning'],
    ];

    /**
     * متد برای بررسی وضعیت خودرو.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, ['available', 'pre_reserved'], true) && $this->availability;
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
     *
     * @return bool
     */
    public function isInsuranceExpired(): bool
    {
        return $this->insurance_expiry_date && $this->insurance_expiry_date->isPast();
    }

    /**
     * متد برای نام کامل خودرو (مدل و پلاک).
     *
     * @return string
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
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
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

    /**
     * متد برای دریافت خودروهای با وضعیت خاص.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // تعریف رابطه با بیمه (یک به یک)
    public function insurance()
    {
        return $this->hasOne(Insurance::class); // یک خودرو یک بیمه دارد
    }

    public function currentContract()
    {
        return $this->hasOne(\App\Models\Contract::class)
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
            return $this->resolveImageUrl($this->image);
        }

        if ($this->carModel?->image) {
            return $this->resolveImageUrl($this->carModel->image);
        }

        return asset('assets/car-pics/car test.webp');
    }

    private function resolveImageUrl(Image $image): string
    {
        $path = ltrim($image->file_path, '/');

        if (! str_starts_with($path, 'assets/')) {
            $path = 'assets/' . $path;
        }

        if (! str_ends_with($path, '/')) {
            $path .= '/';
        }

        return asset($path . $image->file_name);
    }
}
