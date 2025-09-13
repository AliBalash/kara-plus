<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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


    public function calculateRemainingBalance()
    {
        $payments = $this->payments()->get();

        $rentalPaid = $payments->where('payment_type', 'rental_fee')->sum('amount_in_aed');
        $discounts = $payments->where('payment_type', 'discount')->sum('amount_in_aed');
        $securityDeposit = $payments->where('payment_type', 'security_deposit')->sum('amount_in_aed');
        $finePaid = $payments->where('payment_type', 'fine')->sum('amount_in_aed');
        $salik = $payments->where('payment_type', 'salik')->sum('amount_in_aed');

        return $this->total_price
            - ($rentalPaid + $discounts + $securityDeposit)
            + $finePaid + $salik;
    }



    // همه‌ی آیتم‌های قیمت
    public function charges()
    {
        return $this->hasMany(ContractCharges::class);
    }

    /**
     * متد برای محاسبه قیمت نهایی قرارداد با توجه به روزهای اجاره.
     *
     * @return decimal
     */
    public function calculateTotalPrice(): decimal
    {
        $days = $this->pickup_date->diffInDays($this->return_date ?? now());
        return $days * $this->car->price_per_day;
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
}
