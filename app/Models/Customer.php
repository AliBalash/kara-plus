<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * ویژگی‌های قابل پر کردن (mass assignable).
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'national_code',
        'gender',
        'email',
        'phone',
        'address',
        'passport_number',
        'passport_expiry_date',
        'nationality',
        'license_number',
        'status',
        'registration_date',
    ];

    /**
     * ویژگی‌های مخفی برای آرایه‌ها.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'passport_expiry_date' => 'date',
        'registration_date' => 'date',
    ];

    /**
     * متد Full Name برای ترکیب نام و نام خانوادگی.
     *
     * @return string
     */
    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * متد بررسی وضعیت فعال بودن مشتری.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * متد بررسی اعتبار پاسپورت (آیا تاریخ انقضا گذشته؟).
     *
     * @return bool
     */
    public function isPassportValid(): bool
    {
        if ($this->passport_expiry_date) {
            return $this->passport_expiry_date->isFuture();
        }
        return false;
    }

    /**
     * متد برای دریافت مشتریان فعال.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * رابطه با مدل Contract (قراردادها).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'customer_id');
    }

    /**
     * رابطه با مدل Car (ماشین‌ها) از طریق قراردادها.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function cars()
    {
        return $this->hasManyThrough(Car::class, Contract::class, 'customer_id', 'id', 'id', 'car_id');
    }

    // Relationship with CustomerDocument model
    public function customerDocuments()
    {
        return $this->hasMany(CustomerDocument::class);
    }


    // Relationship with Payment model
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }


    protected static function boot()
    {
        parent::boot();

        // رویداد ایجاد مشتری
        static::creating(function ($customer) {
            if (empty($customer->registration_date)) {
                $customer->registration_date = now();
            }
        });

        // رویداد پس از ایجاد مشتری
        // static::creating(function ($customer) {
        //     if (empty($customer->gender)) { // اگر جنسیت تنظیم نشده باشد
        //         try {
        //             // ایجاد نمونه Guzzle Client
        //             $client = new Client();
        //             // فراخوانی API
        //             $response = $client->get("https://api.genderize.io", [
        //                 'query' => [
        //                     'name' => $customer->first_name, // ارسال نام برای تشخیص جنسیت
        //                 ]
        //             ]);
        //             // استخراج داده از پاسخ API
        //             $data = json_decode($response->getBody()->getContents(), true);
        //             // تنظیم جنسیت در مدل
        //             if (isset($data['gender'])) {
        //                 $customer->gender = $data['gender'];
        //                 $customer->save(); // ذخیره مدل به‌روزرسانی شده
        //             }
        //         } catch (\Exception $e) {
        //             // مدیریت خطاها (مثلاً لاگ کردن خطا)
        //             Log::error('Error in gender detection: ' . $e->getMessage());
        //         }
        //     }
        // });
    }
}
