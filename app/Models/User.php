<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;


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

    public function driverContracts()
    {
        return $this->hasMany(Contract::class, 'driver_id');
    }

    public function shortName(): string
    {
        $first = mb_strtolower($this->first_name, 'UTF-8');
        $last = mb_strtoupper($this->last_name, 'UTF-8');

        // بررسی ترکیب‌های چندحرفی در ابتدای نام
        $specialPrefixes = ['sh', 'ch', 'kh'];
        $initial = mb_substr($first, 0, 2, 'UTF-8');

        if (in_array($initial, $specialPrefixes)) {
            $short = mb_strtoupper($initial, 'UTF-8');
        } else {
            // اگر دو حرف اول جزو ترکیب‌ها نبود، فقط اولین حرف گرفته شود
            $short = mb_strtoupper(mb_substr($first, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return $short . '.' . $last;
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null; // see the note above in Gate::before about why null must be returned here.
    }
}
