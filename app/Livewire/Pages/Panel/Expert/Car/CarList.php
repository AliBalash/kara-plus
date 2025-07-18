<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class CarList extends Component
{
    public $search = '';
    public $selectedBrand = '';
    public $onlyReserved = false;
    protected $listeners = ['deletecar'];


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


    public function deletecar($id)
    {
        $car = Car::findOrFail($id);

        // Delete related options if needed
        $car->options()->delete();

        // Delete the image file if it exists
        if ($car->carModel->image && Storage::disk('car_pics')->exists($car->carModel->image->file_name)) {
            Storage::disk('car_pics')->delete($car->carModel->image->file_name);
        }

        // Delete the car record
        $car->delete();

        // Flash success message to session
        session()->flash('success', 'Car has been deleted successfully.');
    }
}
