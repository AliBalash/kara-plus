<?php

namespace App\Livewire\Pages\Panel\Expert\Agent;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Agent;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AgentList extends Component
{
    use WithPagination;
    use InteractsWithToasts;

    public string $search = '';
    public string $searchInput = '';

    public ?int $editingId = null;
    public string $name = '';
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
        $agent = Agent::findOrFail($id);

        $this->editingId = $agent->id;
        $this->name = $agent->name;
        $this->is_active = (bool) $agent->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'is_active']);
        $this->searchInput = $this->search;
        $this->is_active = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        Agent::updateOrCreate(
            ['id' => $this->editingId],
            $validated
        );

        $message = $this->editingId ? 'Agent updated successfully.' : 'Agent created successfully.';

        $this->toast('success', $message);

        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $agent = Agent::findOrFail($id);
        $agent->is_active = ! $agent->is_active;
        $agent->save();

        $this->toast('success', 'Agent status updated.');
    }

    public function delete(int $id): void
    {
        $agent = Agent::withCount('contracts')->find($id);

        if (! $agent) {
            return;
        }

        if ($agent->contracts_count > 0) {
            $this->toast('error', 'This agent is assigned to contracts and cannot be deleted.', false);
            return;
        }

        $agent->delete();
        $this->toast('success', 'Agent deleted successfully.');

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->resetPage();
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('agents', 'name')->ignore($this->editingId),
            ],
            'is_active' => ['boolean'],
        ];
    }

    public function render()
    {
        $search = trim($this->search);
        $likeSearch = '%' . $search . '%';

        $agents = Agent::query()
            ->withCount('contracts')
            ->when($search !== '', function ($query) use ($likeSearch) {
                $query->where('name', 'like', $likeSearch);
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.pages.panel.expert.agent.agent-list', compact('agents'));
    }
}
