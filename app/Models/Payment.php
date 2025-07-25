<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'customer_id',
        'user_id',
        'car_id',
        'amount',
        'currency',
        'payment_type',
        'description',
        'payment_date',
        'is_refundable',
        'is_paid',
        'rate',
        'receipt',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'is_refundable' => 'boolean',
        'is_paid' => 'boolean',
    ];

    /**
     * دریافت پرداخت‌های انجام‌شده
     */
    public static function getPaidPayments()
    {
        return self::where('is_paid', true)->get();
    }

    /**
     * دریافت پرداخت‌های معوقه
     */
    public static function getUnpaidPayments()
    {
        return self::where('is_paid', false)->get();
    }

    /**
     * دریافت پرداخت‌های قابل استرداد
     */
    public static function getRefundablePayments()
    {
        return self::where('is_refundable', true)->get();
    }

    /**
     * محاسبه کل پرداخت‌های یک قرارداد خاص
     */
    public static function getTotalPaymentsForContract($contractId)
    {
        return self::where('contract_id', $contractId)->sum('amount');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    /**
     * محاسبه کل مبلغ پرداخت‌شده توسط یک مشتری
     */
    public static function getTotalPaymentsForCustomer($customerId)
    {
        return self::where('customer_id', $customerId)->sum('amount');
    }

    /**
     * ارتباط با قرارداد
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * ارتباط با مشتری
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * ارتباط با خودرو
     */
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
