<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toll extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'car_id',
        'contract_id',
        'amount',
        'location',
        'toll_date',
        'is_paid',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'toll_date' => 'datetime',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    /**
     * رابطه با مدل Car (خودرو).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

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
     * متد برای فرمت کردن مبلغ عوارض.
     *
     * @return string
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    /**
     * متد برای چک کردن وضعیت پرداخت عوارض.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    /**
     * متد برای چک کردن وضعیت پرداخت نشده عوارض.
     *
     * @return bool
     */
    public function isUnpaid(): bool
    {
        return !$this->is_paid;
    }

    /**
     * متد برای گرفتن تاریخ و زمان عبور از عوارضی.
     *
     * @return string
     */
    public function getTollDateFormattedAttribute()
    {
        return $this->toll_date->format('Y-m-d H:i:s');
    }
}
