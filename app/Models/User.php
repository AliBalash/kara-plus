<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , HasRoles;


    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar',
        'password',
        'status',
        'last_login',
        'national_code',
        'address',
    ];

    protected $dates = ['last_login']; // برای مدیریت تاریخ
    public function updateLastLogin()
    {
        $this->last_login = now();
        $this->save();
    }
    /**
     * ویژگی‌های مخفی برای آرایه‌ها.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تبدیل‌های مربوط به نوع داده‌ها.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
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
     * متد تعیین وضعیت فعال بودن کاربر.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * متد برای هش کردن رمز عبور به صورت خودکار.
     *
     * @param string $password
     */
    // public function setPasswordAttribute($value)
    // {
    //     $this->attributes['password'] = Hash::needsRehash($value) ? bcrypt($value) : $value;
    // }

    /**
     * رابطه با مدل Contract (قراردادها).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'user_id');
    }

    /**
     * ارسال نوتیفیکیشن خوش‌آمد به کاربر.
     *
     * @return void
     */
    public function sendWelcomeNotification(): void
    {
        // مثلاً نوتیفیکیشن خوش‌آمد گویی را ارسال می‌کند
        $this->notify(new \App\Notifications\WelcomeNotification($this));
    }
}
