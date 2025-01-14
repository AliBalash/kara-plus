<?php

namespace App\Livewire\Pages\Panel\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Login extends Component
{
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

        if (Auth::attempt(['phone' => $this->phone, 'password' => $this->password])) {

            session()->regenerate(); // بازسازی نشست برای جلوگیری از حملات CSRF

            // کاربر را دریافت کنید
            $user = Auth::user();
            // به‌روزرسانی last_login
            $user->updateLastLogin();

            // ریدایرکت به داشبورد
            return redirect()->to(route('expert.dashboard'));
        } else {
            session()->flash('error', 'Invalid credentials');
        }
    }
}
