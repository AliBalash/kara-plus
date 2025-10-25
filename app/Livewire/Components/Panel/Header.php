<?php

namespace App\Livewire\Components\Panel;

use App\Models\Car;
use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Header extends Component
{
    public $query = '';
    public $cars = [];
    public $agreementQuery = '';
    public $agreementResults = [];

    public function render()
    {
        return view('livewire.components.panel.header');
    }

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

    public function updatedAgreementQuery()
    {
        $query = trim((string) $this->agreementQuery);

        if (strlen($query) > 1) {
            $this->agreementResults = Contract::with(['pickupDocument', 'customer', 'car.carModel'])
                ->whereHas('pickupDocument', function ($q) use ($query) {
                    $q->where('agreement_number', 'like', '%' . $query . '%');
                })
                ->orderByDesc('pickup_date')
                ->limit(10)
                ->get();
        } else {
            $this->agreementResults = [];
        }
    }

    public function resetAgreementSearch()
    {
        $this->agreementQuery = '';
        $this->agreementResults = [];
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
