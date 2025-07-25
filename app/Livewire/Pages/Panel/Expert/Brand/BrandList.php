<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;

use App\Models\CarModel;
use Livewire\Component;
use Livewire\WithPagination;

class BrandList extends Component
{
    public $search = '';
    public $fuelType = '';
    public $gearboxType = '';

    protected $queryString = ['search', 'fuelType', 'gearboxType'];
    protected $listeners = ['deleteBrand'];


    use WithPagination;

    public function render()
    {
       
        $brands = CarModel::query()
            ->when($this->search, function ($query) {
                $query->where('brand', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%');
            })
            ->when($this->fuelType, function ($query) {
                $query->where('fuel_type', $this->fuelType);
            })
            ->when($this->gearboxType, function ($query) {
                $query->where('gearbox_type', $this->gearboxType);
            })
            ->paginate(10);

        return view('livewire.pages.panel.expert.brand.brand-list', compact('brands'));
    }

    public function deleteBrand($id)
    {
        $brand = CarModel::findOrFail($id);
        // Delete the car record
        $brand->delete();

        // Flash success message to session
        session()->flash('success', 'Car has been deleted successfully.');
    }
}
