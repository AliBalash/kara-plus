<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

    class Fine extends Model
    {
        use HasFactory;

        /**
         * ویژگی‌های قابل پر کردن (mass assignable).
         *
         * @var array
         */
        protected $fillable = [
            'contract_id',
            'amount',
            'description',
            'fine_date',
            'is_paid',
        ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'fine_date' => 'date',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
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
     * متد برای تغییر وضعیت پرداخت جریمه.
     *
     * @return void
     */
    public function markAsPaid()
    {
        $this->update(['is_paid' => true]);
    }

    /**
     * متد برای گرفتن مبلغ جریمه با فرمت دلخواه.
     *
     * @return string
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    /**
     * متد برای چک کردن اینکه جریمه پرداخت شده است یا خیر.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    /**
     * متد برای نمایش وضعیت جریمه.
     *
     * @return string
     */
    public function statusLabel(): string
    {
        return $this->is_paid ? 'Paid' : 'Unpaid';
    }
}
