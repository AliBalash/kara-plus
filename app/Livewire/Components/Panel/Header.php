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
        $term = trim((string) $this->query);

        if (strlen($term) > 1) {
            $now = now();

            $this->cars = Car::with([
                'carModel',
                'contracts' => function ($query) use ($now) {
                    $query->with(['customer', 'deliveryDriver', 'returnDriver'])
                        ->whereIn('current_status', Car::reservingStatuses())
                        ->where(function ($builder) use ($now) {
                            $builder
                                ->whereNull('pickup_date')
                                ->orWhere('pickup_date', '>=', $now)
                                ->orWhere(function ($active) use ($now) {
                                    $active->whereNotNull('pickup_date')
                                        ->where('pickup_date', '<', $now)
                                        ->where(function ($time) use ($now) {
                                            $time->whereNull('return_date')
                                                ->orWhere('return_date', '>=', $now);
                                        });
                                });
                        })
                        ->orderByRaw('pickup_date IS NULL')
                        ->orderBy('pickup_date');
                },
            ])
                ->where(function ($builder) use ($term) {
                    $builder->where('plate_number', 'like', '%' . $term . '%')
                        ->orWhereHas('carModel', function ($q) use ($term) {
                            $q->where('brand', 'like', '%' . $term . '%')
                                ->orWhere('model', 'like', '%' . $term . '%');
                        });
                })
                ->orderBy('plate_number')
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
