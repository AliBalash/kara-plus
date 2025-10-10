<?php

namespace App\Livewire\Pages\Panel\Expert\User;

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ManageUserRoles extends Component
{
    use WithPagination;
    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function assignDriver(int $userId): void
    {
        $user = User::findOrFail($userId);
        $driverRole = Role::firstOrCreate([
            'name' => 'driver',
            'guard_name' => 'web',
        ]);

        $currentRoles = $user->getRoleNames()->toArray();
        if (! in_array($driverRole->name, $currentRoles, true)) {
            $user->syncRoles(array_unique(array_merge($currentRoles, [$driverRole->name])));
        }

        session()->flash('message', $user->shortName() . ' assigned to driver role.');
    }

    public function removeDriver(int $userId): void
    {
        $user = User::findOrFail($userId);
        if ($user->hasRole('driver')) {
            $user->removeRole('driver');
            session()->flash('message', $user->shortName() . ' removed from driver role.');
        }
    }

    public function render()
    {
        abort_if(auth()->user()?->hasRole('driver'), 403);

        $users = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($query) {
                $term = '%' . Str::lower($this->search) . '%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(first_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
                });
            })
            ->orderBy('first_name')
            ->paginate(10);

        $driverCount = User::role('driver')->count();

        return view('livewire.pages.panel.expert.user.manage-user-roles', [
            'users' => $users,
            'driverCount' => $driverCount,
        ]);
    }
}
