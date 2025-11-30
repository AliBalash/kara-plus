<?php

namespace App\Livewire\Pages\Panel\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Support\PhoneNumber;

class Login extends Component
{
    use InteractsWithToasts;
    public function render()
    {

        return view('livewire.pages.panel.auth.login')
            ->layout('layouts.auth');
    }

    public $phone;
    public $password;
    public $remember = false;

    public function login()
    {
        $this->validate([
            'phone' => 'required',
            'password' => 'required',
            'remember' => 'boolean',
        ]);

        // تبدیل شماره تلفن ورودی به فرمت استاندارد
        $normalizedPhone = PhoneNumber::normalize($this->phone) ?? trim((string) $this->phone);

        if (Auth::attempt(['phone' => $normalizedPhone, 'password' => $this->password], $this->remember)) {
            session()->regenerate(); // بازسازی نشست برای امنیت بیشتر

            // دریافت کاربر و آپدیت زمان آخرین ورود
            $user = Auth::user();
            $user->update(['last_login' => now()]);

            return redirect()->to(route('expert.dashboard'));
        }

        $this->toast('error', 'The number or password is incorrect.', false);
    }
}
