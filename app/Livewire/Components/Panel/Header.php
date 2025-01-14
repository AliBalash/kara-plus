<?php

namespace App\Livewire\Components\Panel;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Header extends Component
{
    public function render()
    {
        return view('livewire.components.panel.header');
    }


    public function logout()
    {
        // خروج کاربر
        Auth::logout();

        // بازسازی سشن برای جلوگیری از حملات
        session()->invalidate();
        session()->regenerateToken();

        // هدایت کاربر به صفحه لاگین
        return redirect()->to(route('auth.login'));
    }
}
