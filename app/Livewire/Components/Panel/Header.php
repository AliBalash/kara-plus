<?php

namespace App\Livewire\Components\Panel;

use App\Models\Car;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Header extends Component
{
    public function render()
    {
        return view('livewire.components.panel.header');
    }

    public $query = '';
    public $cars = [];

    public function updatedQuery()
    {
        if (strlen($this->query) > 1) {
            $this->cars = Car::with('carModel')
                ->where('plate_number', 'like', '%' . $this->query . '%')
                ->orWhereHas('carModel', function ($q) {
                    $q->where('brand', 'like', '%' . $this->query . '%')
                        ->orWhere('model', 'like', '%' . $this->query . '%');
                })
                ->get();
        } else {
            $this->cars = [];
        }
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
