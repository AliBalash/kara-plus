<?php

namespace App\Livewire\Pages\Panel\Expert\Car;

use App\Models\Car;
use Carbon\Carbon;
use App\Livewire\Concerns\InteractsWithToasts;
use Illuminate\Support\Facades\DB;
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
        $imageFileName = $car->image?->file_name;

        DB::transaction(function () use ($car) {
            $car->options()->delete();

            if ($car->image) {
                $car->image->delete();
            }

            $car->delete();
        });

        if ($imageFileName && Storage::disk('car_pics')->exists($imageFileName)) {
            Storage::disk('car_pics')->delete($imageFileName);
        }

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
            ->when($this->statusFilter, fn($q) => $q->byOperationalStatus($this->statusFilter))
            ->when($this->onlyReserved, fn($q) => $q->whereIn('status', ['reserved', 'pre_reserved']));

        $this->applyDateFilters($carsQuery);

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

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    protected function applyDateFilters(Builder $query): void
    {
        if (! $this->pickupFrom && ! $this->pickupTo) {
            return;
        }

        [$windowStart, $windowEnd] = $this->selectedWindowBounds();

        if ($this->statusFilter === 'available') {
            $query->whereDoesntHave('contracts', function (Builder $contractQuery) use ($windowStart, $windowEnd) {
                $contractQuery
                    ->whereIn('current_status', Car::reservingStatuses())
                    ->whereNotNull('pickup_date')
                    ->where('pickup_date', '<', $windowEnd)
                    ->where(function (Builder $overlapQuery) use ($windowStart) {
                        $overlapQuery->whereNull('return_date')
                            ->orWhere('return_date', '>', $windowStart);
                    });
            });

            return;
        }

        $query->whereHas('currentContract', function (Builder $contractQuery) use ($windowStart, $windowEnd) {
            $contractQuery
                ->where('pickup_date', '<', $windowEnd)
                ->where(function (Builder $overlapQuery) use ($windowStart) {
                    $overlapQuery->whereNull('return_date')
                        ->orWhere('return_date', '>', $windowStart);
                });
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function selectedWindowBounds(): array
    {
        $from = $this->pickupFrom
            ? Carbon::parse($this->pickupFrom)->startOfDay()
            : null;
        $to = $this->pickupTo
            ? Carbon::parse($this->pickupTo)->endOfDay()
            : null;

        if ($from && $to) {
            return [$from, $to];
        }

        if ($from) {
            return [$from, $from->copy()->endOfDay()];
        }

        $to ??= Carbon::now()->endOfDay();

        return [$to->copy()->startOfDay(), $to];
    }
}
