<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'contract_id',
        'customer_id',
        'amount',
        'payment_type',
        'payment_date',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * رابطه با مدل Contract (قرارداد).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * متد برای گرفتن نوع پرداخت به صورت فرمت‌شده.
     *
     * @return string
     */
    public function getPaymentTypeLabelAttribute()
    {
        return ucfirst($this->payment_type);
    }

    // Relationship with Customer model
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * متد برای گرفتن مبلغ پرداخت‌شده با فرمت مناسب.
     *
     * @return string
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    /**
     * متد برای چک کردن نوع پرداخت (هزینه اجاره یا جریمه).
     *
     * @return bool
     */
    public function isRentalFee(): bool
    {
        return $this->payment_type === 'rental_fee';
    }

    /**
     * متد برای چک کردن اینکه پرداخت به عنوان جریمه بوده است یا خیر.
     *
     * @return bool
     */
    public function isFinePayment(): bool
    {
        return $this->payment_type === 'fine';
    }
}
