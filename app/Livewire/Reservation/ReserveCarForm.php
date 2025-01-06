<?php

namespace App\Livewire\Reservation;

use App\Models\Car;
use App\Models\CarModel;
use Livewire\Component;

class ReserveCarForm extends Component
{


    public $selectedBrand = '';
    public $cars = [];
    public $first_name;
    public $last_name;
    public $pickup_date;
    public $return_date;
    public $email;
    public $phone;
    public $messenger_phone;
    public $selectedCar = null; // ID of the selected car
    public $pickup_location;
    public $return_location;

    public function mount()
    {
        // مقدار اولیه نمایش تمام ماشین‌ها
        $this->cars = Car::all();
    }

    public function selectCar(Car $car)
    {
        $this->selectedCar = $car;
    }

    public function updatedSelectedBrand($value)
    {
        // وقتی کاربر برند را تغییر داد، فیلتر اعمال شود
        $this->cars = $value
            ? Car::whereHas('carModel', function ($query) use ($value) {
                $query->where('brand', $value);
            })->get()
            : Car::all();
            // dd($this->cars);
    }


    public function render()
    {
        // ارسال لیست برندها برای نمایش در Select Box
        $brands = CarModel::distinct()->pluck('brand');
        return view('livewire.reservation.reserve-car-form', compact('brands'))->layout('layouts.reservation');
    }
   
}
