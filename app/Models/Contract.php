<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Car;

class Contract extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'car_id',
        'agent_sale',
        'submitted_by_name',
        'pickup_date',
        'pickup_location',
        'return_location',
        'return_date',
        'total_price',
        'kardo_required',
        'current_status',
        'notes',
        'meta',
        'payment_on_delivery',
        'used_daily_rate',
        'discount_note',
        'delivery_driver_id',
        'return_driver_id',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'pickup_date' => 'datetime',
        'return_date' => 'datetime',
        'total_price' => 'decimal:2',
        'kardo_required' => 'boolean',
        'payment_on_delivery' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * متد برای دریافت وضعیت قرارداد.
     *
     * @return string
     */
    public function statusLabel(): string
    {
        return ucfirst($this->current_status); // نمایش وضعیت قرارداد با حرف اول بزرگ
    }

    /**
     * متد برای بررسی وضعیت قرارداد (فعال یا تکمیل شده).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->current_status === 'assigned' || $this->current_status === 'under_review' || $this->current_status === 'delivery';
    }

    /**
     * متد برای بررسی اینکه قرارداد کامل شده است یا خیر.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->current_status === 'complete';
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

    /**
     * رابطه با مدل Car (خودرو).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
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
        $salikFour = $payments->where('payment_type', 'salik_4_aed');
        $salikSix = $payments->where('payment_type', 'salik_6_aed');
        $salikOther = $payments->where('payment_type', 'salik_other_revenue');

        $salikTripCharges = $salikFour->sum('amount_in_aed') + $salikSix->sum('amount_in_aed');
        $salikOtherRevenue = $salikOther->sum('amount_in_aed');
        $salik = $salikTripCharges + $legacySalik;
        $parking = $payments->where('payment_type', 'parking')->sum('amount_in_aed');
        $damage = $payments->where('payment_type', 'damage')->sum('amount_in_aed');
        $paymentBack = $payments->where('payment_type', 'payment_back')->sum('amount_in_aed');
        $carwash = $payments->where('payment_type', 'carwash')->sum('amount_in_aed');
        $fuel = $payments->where('payment_type', 'fuel')->sum('amount_in_aed');

        $effectivePaid = $rentalPaid - $paymentBack;

        $balance = (float) $this->total_price
            - ($effectivePaid + $discounts + $securityDeposit)
            + $finePaid + $salik + $salikOtherRevenue + $parking + $damage + $carwash + $fuel;

        return round($balance, 2);
    }



    // همه‌ی آیتم‌های قیمت
    public function charges()
    {
        return $this->hasMany(ContractCharges::class);
    }

    /**
     * متد برای محاسبه قیمت نهایی قرارداد با توجه به روزهای اجاره.
     *
     * @return float
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
        if (!$this->pickup_date) {
            $this->update([
                'pickup_date' => now(),  // ثبت تاریخ شروع قرارداد
            ]);
        }
    }

    // متد برای نهایی‌سازی درخواست
    public function finalizeContract()
    {
        $this->update([
            'return_date' => now(),  // ثبت تاریخ پایان قرارداد
        ]);
    }

    public function pickupDocument()
    {
        return $this->hasOne(PickupDocument::class);
    }

    public function ReturnDocument()
    {
        return $this->hasOne(ReturnDocument::class);
    }

    protected static function booted(): void
    {
        static::created(function (Contract $contract) {
            $contract->syncCarAvailabilityForCar($contract->car_id);
        });

        static::updated(function (Contract $contract) {
            if ($contract->wasChanged('car_id')) {
                $contract->syncCarAvailabilityForCar($contract->getOriginal('car_id'));
            }

            $contract->syncCarAvailabilityForCar($contract->car_id);
        });

        static::deleted(function (Contract $contract) {
            $contract->syncCarAvailabilityForCar($contract->car_id);
        });
    }

    private function syncCarAvailabilityForCar(?int $carId): void
    {
        if (!$carId) {
            return;
        }

        $car = Car::find($carId);

        if (!$car) {
            return;
        }

        $inactiveStatuses = ['complete', 'cancelled', 'rejected', 'returned', 'payment'];
        $now = now();

        $relevantContracts = self::where('car_id', $carId)
            ->whereNotIn('current_status', $inactiveStatuses)
            ->get(['pickup_date', 'return_date', 'current_status']);

        if ($relevantContracts->isNotEmpty()) {
            $hasActiveReservation = $relevantContracts->contains(function ($reservation) use ($now) {
                if (!$reservation->pickup_date) {
                    return false;
                }

                $pickup = $reservation->pickup_date;
                $return = $reservation->return_date;

                $hasStarted = $pickup->lessThanOrEqualTo($now);
                $notReturned = $return === null || $return->greaterThanOrEqualTo($now);

                return $hasStarted && $notReturned;
            });

            if ($hasActiveReservation) {
                $update = ['availability' => false];

                if ($car->status !== 'under_maintenance') {
                    $update['status'] = 'reserved';
                }

                $car->update($update);
                return;
            }

            $hasUpcomingReservation = $relevantContracts->contains(function ($reservation) use ($now) {
                return $reservation->pickup_date && $reservation->pickup_date->greaterThan($now);
            });

            if ($hasUpcomingReservation) {
                $update = ['availability' => true];

                if ($car->status === 'reserved' || $car->status === 'available') {
                    $update['status'] = 'pre_reserved';
                }

                $car->update($update);
                return;
            }
        }

        $update = ['availability' => true];

        if (in_array($car->status, ['reserved', 'pre_reserved'], true)) {
            $update['status'] = 'available';
        }

        $car->update($update);
    }
}
