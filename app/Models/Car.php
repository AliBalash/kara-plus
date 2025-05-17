<?php

namespace App\Models;

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
        'price_per_day',
        'service_due_date',
        'damage_report',
        'manufacturing_year',
        'color',
        'notes',
        'chassis_number',
        'gps',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'availability' => 'boolean',
        'mileage' => 'integer',
        'price_per_day' => 'decimal:2',
        'insurance_expiry_date' => 'date',
        'service_due_date' => 'date',
        'manufacturing_year' => 'integer',
    ];

    /**
     * متد برای بررسی وضعیت خودرو.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->availability;
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
    public function fullName(): string
    {
        return $this->carModel->fullName() . ' (' . $this->plate_number . ')';
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
            ->where('current_status', 'reserved')
            ->latest('pickup_date');
    }
}
