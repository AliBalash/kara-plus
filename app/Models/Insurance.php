<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'expiry_date',
        'valid_days',
        'status',
        'insurance_company',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'valid_days' => 'integer',
    ];

    // رابطه با مدل Car
    public function car()
    {
        return $this->belongsTo(Car::class); // هر بیمه به یک خودرو مربوط است
    }

    /**
     * بررسی انقضای بیمه
     * @return bool
     */
    public function isExpired()
    {
        return Carbon::parse($this->expiry_date)->isPast();
    }

    /**
     * تعداد روزهای باقی‌مانده تا انقضا
     * @return int|null
     */
    public function daysUntilExpiry()
    {
        if ($this->expiry_date) {
            return Carbon::now()->diffInDays(Carbon::parse($this->expiry_date), false);
        }
        return null;
    }

    /**
     * بررسی وضعیت بیمه (موفقیت‌آمیز)
     * @return bool
     */
    public function isDone()
    {
        return $this->status === 'done';
    }

    /**
     * بروزرسانی وضعیت بیمه
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(string $newStatus)
    {
        $allowedStatuses = ['done', 'pending', 'failed'];
        if (in_array($newStatus, $allowedStatuses)) {
            $this->update(['status' => $newStatus]);
            return true;
        }
        return false;
    }


    /**
     * دریافت نام شرکت بیمه به صورت Uppercase
     * @return string|null
     */
    public function getFormattedCompanyName()
    {
        return $this->insurance_company ? strtoupper($this->insurance_company) : null;
    }

    /**
     * فیلتر بیمه‌ها بر اساس وضعیت
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * فیلتر بیمه‌ها بر اساس انقضا
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param bool $expired
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query, bool $expired = true)
    {
        if ($expired) {
            return $query->whereDate('expiry_date', '<', Carbon::now());
        }
        return $query->whereDate('expiry_date', '>=', Carbon::now());
    }

    /**
     * دریافت تاریخ انقضا به صورت فرمت شده
     * @param string $format
     * @return string|null
     */
    public function getFormattedExpiryDate($format = 'd-m-Y')
    {
        return $this->expiry_date ? Carbon::parse($this->expiry_date)->format($format) : null;
    }
}
