<?php

namespace App\Livewire\Pages\Panel\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Livewire\Concerns\InteractsWithToasts;

class Login extends Component
{
    use InteractsWithToasts;
    public function render()
    {

        // ذخیره رمز عبور هش‌شده
        // $user = User::find(1);
        // $user->password = bcrypt('12345678');
        // $user->save(); // ذخیره در دیتابیس


        // $this->password = '12345678';
        // dd(Hash::check($this->password , $user->password));
        // dd($user->password ,$user->password);

        // if (Auth::guard('web')->attempt($credentials)) {





        return view('livewire.pages.panel.auth.login')
            ->layout('layouts.auth');
    }

    public $phone;
    public $password;

    public function login()
    {
        $this->validate([
            'phone' => 'required',
            'password' => 'required',
        ]);

        // تبدیل شماره تلفن ورودی به فرمت استاندارد
        $normalizedPhone = $this->normalizePhoneNumber($this->phone);

        if (Auth::attempt(['phone' => $normalizedPhone, 'password' => $this->password])) {
            session()->regenerate(); // بازسازی نشست برای امنیت بیشتر

            // دریافت کاربر و آپدیت زمان آخرین ورود
            $user = Auth::user();
            $user->update(['last_login' => now()]);

            return redirect()->to(route('expert.dashboard'));
        }

        $this->toast('error', 'The number or password is incorrect.', false);
    }

    // تابع استاندارد‌سازی شماره تلفن
    private function normalizePhoneNumber($phone)
    {
        $phone = trim($phone);

        if (preg_match('/^09\d{9}$/', $phone)) {
            return '+98' . substr($phone, 1);
        }

        if (preg_match('/^098\d{9}$/', $phone)) {
            return '+98' . substr($phone, 2);
        }

        if (preg_match('/^0971\d{9,10}$/', $phone)) {
            return '+971' . substr($phone, 4);
        }

        if (preg_match('/^971\d{9}$/', $phone)) {
            return '+971' . substr($phone, 3);
        }

        return $phone; // اگر فرمت نامشخص بود، همان مقدار را برگرداند
    }
}
