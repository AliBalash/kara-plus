<?php

namespace App\Livewire\Pages\Panel\Expert\Brand;

use App\Models\CarModel;
use App\Livewire\Concerns\InteractsWithToasts;
use Livewire\Component;
use Livewire\WithPagination;

class BrandList extends Component
{
    use InteractsWithToasts;
    public $search = '';
    public $searchInput = '';
    public $fuelType = '';
    public $gearboxType = '';

    protected $queryString = ['search', 'fuelType', 'gearboxType'];
    protected $listeners = ['deleteBrand'];


    use WithPagination;

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $brands = CarModel::query()
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where(function ($scoped) use ($likeSearch) {
                    $scoped->where('brand', 'like', $likeSearch)
                        ->orWhere('model', 'like', $likeSearch);
                });
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

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function deleteBrand($id)
    {
        $brand = CarModel::findOrFail($id);
        // Delete the car record
        $brand->delete();

        $this->toast('success', 'Car has been deleted successfully.');
    }
}
