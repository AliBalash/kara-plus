<?php

namespace App\Livewire\Pages\Panel\Expert\LocationCost;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\LocationCost;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class LocationCostList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    public string $search = '';
    public string $searchInput = '';

    public ?int $editingId = null;
    public string $location = '';
    public float $under_3_fee = 0;
    public float $over_3_fee = 0;
    public bool $is_active = true;

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->searchInput = $this->search;
    }

    public function updatedSearchInput(): void
    {
        $this->applySearch();
    }

    public function applySearch(): void
    {
        $this->search = trim($this->searchInput);
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $cost = LocationCost::findOrFail($id);

        $this->editingId = $cost->id;
        $this->location = $cost->location;
        $this->under_3_fee = (float) $cost->under_3_fee;
        $this->over_3_fee = (float) $cost->over_3_fee;
        $this->is_active = (bool) $cost->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'location', 'under_3_fee', 'over_3_fee', 'is_active']);
        $this->searchInput = $this->search;
        $this->is_active = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        LocationCost::updateOrCreate(
            ['id' => $this->editingId],
            $validated
        );

        $message = $this->editingId ? 'Location cost updated successfully.' : 'Location cost created successfully.';

        $this->toast('success', $message);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $cost = LocationCost::find($id);

        if (!$cost) {
            return;
        }

        $cost->delete();
        $this->toast('success', 'Location cost deleted successfully.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $cost = LocationCost::findOrFail($id);
        $cost->is_active = ! $cost->is_active;
        $cost->save();

        $this->toast('success', 'Location status updated.');
    }

    protected function rules(): array
    {
        return [
            'location' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_costs', 'location')->ignore($this->editingId),
            ],
            'under_3_fee' => ['required', 'numeric', 'min:0'],
            'over_3_fee' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $locationCosts = LocationCost::query()
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where('location', 'like', $likeSearch);
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.pages.panel.expert.location-cost.location-cost-list', compact('locationCosts'));
    }
}
