<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;

class CarList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    public $search = '';
    public $searchInput = '';
    public $selectedBrand = '';
    public $statusFilter = '';
    public $pickupFrom;
    public $pickupTo;
    public $sortField = 'id';
    public $sortDirection = 'asc';
    public $onlyReserved = false;

    protected $listeners = ['deletecar'];
    protected $queryString = ['search', 'selectedBrand', 'statusFilter', 'pickupFrom', 'pickupTo', 'sortField', 'sortDirection', 'onlyReserved'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function sortBy($field)
    {
        // اگر فیلد relation هست، sortField رو نام relation بذاریم
        if (in_array($field, ['pickup_date', 'return_date'])) {
            $this->sortField = $field;
        } else {
            if ($this->sortField === $field) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortField = $field;
                $this->sortDirection = 'asc';
            }
        }
    }


    public function deletecar($id)
    {
        $car = Car::findOrFail($id);

        $car->options()->delete();

        if ($car->image && Storage::disk('car_pics')->exists($car->image->file_name)) {
            Storage::disk('car_pics')->delete($car->image->file_name);
        }

        if ($car->image) {
            $car->image->delete();
        }

        $car->delete();
        $this->toast('success', 'Car has been deleted successfully.');
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->searchInput = '';
        $this->selectedBrand = '';
        $this->statusFilter = '';
        $this->pickupFrom = null;
        $this->pickupTo = null;
        $this->onlyReserved = false;
        $this->resetPage();
    }



    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $brands = Car::query()
            ->join('car_models', 'cars.car_model_id', '=', 'car_models.id')
            ->select('car_models.brand')
            ->distinct()
            ->pluck('brand');

        $carsQuery = Car::with(['carModel', 'currentContract'])
            ->when($search !== '', fn($q) => $q->where(function ($q) use ($likeSearch) {
                $q->where('plate_number', 'like', $likeSearch)
                    ->orWhereHas('carModel', fn($q2) => $q2->where(function ($modelQuery) use ($likeSearch) {
                        $modelQuery->where('brand', 'like', $likeSearch)
                            ->orWhere('model', 'like', $likeSearch);
                    }));
            }))

            ->when($this->selectedBrand, fn($q) => $q->whereHas('carModel', fn($q2) => $q2->where('brand', $this->selectedBrand)))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->onlyReserved, fn($q) => $q->whereIn('status', ['reserved', 'pre_reserved']))
            ->when($this->pickupFrom, fn($q) => $q->whereHas('currentContract', fn($q2) => $q2->where('pickup_date', '>=', $this->pickupFrom)))
            ->when($this->pickupTo, fn($q) => $q->whereHas('currentContract', fn($q2) => $q2->where('pickup_date', '<=', $this->pickupTo)));

        // مرتب‌سازی روی ستون relation بدون تکراری شدن رکورد
        if (in_array($this->sortField, ['pickup_date', 'return_date'])) {
            $carsQuery->leftJoin('contracts', function ($join) {
                $join->on('contracts.car_id', '=', 'cars.id')
                    ->whereRaw('contracts.id = (select id from contracts c2 where c2.car_id = cars.id order by pickup_date desc limit 1)');
            })->orderBy('contracts.' . $this->sortField, $this->sortDirection)
                ->select('cars.*'); // فقط ستون های cars
        } else {
            $carsQuery->orderBy($this->sortField, $this->sortDirection);
        }

        $cars = $carsQuery->paginate(10); // pagination سالم

        return view('livewire.pages.panel.expert.car.car-list', compact('cars', 'brands'));
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }
}
