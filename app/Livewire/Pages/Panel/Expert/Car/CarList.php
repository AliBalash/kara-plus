<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use Livewire\Component;
use Livewire\WithPagination;

class CarList extends Component
{
    public $search = '';
    public $selectedBrand = '';
    public $onlyReserved = false;


    protected $queryString = ['search', 'onlyReserved'];
    use WithPagination;

    public function render()
    {
        $brands = Car::query()
            ->join('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->select('car_models.brand')
            ->distinct()
            ->pluck('brand');

        $cars = Car::with(['carModel', 'currentContract'])
            ->when($this->search, function ($query) {
                if (is_numeric($this->search)) {
                    $query->where('plate_number', 'like', '%' . $this->search . '%');
                } else {
                    $query->orWhereHas('carModel', function ($query) {
                        $query->where('brand', 'like', '%' . $this->search . '%')
                            ->orWhere('model', 'like', '%' . $this->search . '%');
                    });
                }
            })
            ->when($this->selectedBrand, function ($query) {
                $query->whereHas('carModel', function ($query) {
                    $query->where('brand', $this->selectedBrand);
                });
            })
            ->when($this->onlyReserved, function ($query) {
                $query->where('status', 'reserved');
            })
            ->paginate(10);

        return view('livewire.pages.panel.expert.car.car-list', compact('cars', 'brands'));
    }
}
