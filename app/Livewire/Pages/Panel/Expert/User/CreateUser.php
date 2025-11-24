<?php

namespace App\Livewire\Pages\Panel\Expert\User;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateUser extends Component
{
    use InteractsWithToasts;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public ?string $phone = null;

    public string $password = '';

    public string $passwordConfirmation = '';

    /**
     * @var array<int, string>
     */
    public array $selectedRoles = [];

    /**
     * @var array<int, string>
     */
    public array $selectedPermissions = [];

    public Collection $roles;

    public Collection $permissions;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $guard = auth()->getDefaultDriver();
        $this->roles = Role::whereGuardName($guard)->orderBy('name')->get();
        $this->permissions = Permission::whereGuardName($guard)->orderBy('name')->get();
    }

    public function selectAllRoles(): void
    {
        $this->selectedRoles = $this->roles->pluck('name')->toArray();
    }

    public function clearRoles(): void
    {
        $this->selectedRoles = [];
    }

    public function selectAllPermissions(): void
    {
        $this->selectedPermissions = $this->permissions->pluck('name')->toArray();
    }

    public function clearPermissions(): void
    {
        $this->selectedPermissions = [];
    }

    public function save(): void
    {
        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', Password::default()],
            'passwordConfirmation' => ['same:password'],
            'selectedRoles' => ['array'],
            'selectedPermissions' => ['array'],
        ], [
            'passwordConfirmation.same' => 'Passwords must match.',
        ]);

        $user = User::create([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => 'active',
            'password' => Hash::make($this->password),
        ]);

        if (! empty($this->selectedRoles)) {
            $user->syncRoles($this->selectedRoles);
        }

        if (! empty($this->selectedPermissions)) {
            $user->syncPermissions($this->selectedPermissions);
        }

        $this->toast('success', $user->shortName() . ' created successfully.');

        $this->reset(['firstName', 'lastName', 'email', 'phone', 'password', 'passwordConfirmation', 'selectedRoles', 'selectedPermissions']);
    }

    public function render()
    {
        return view('livewire.pages.panel.expert.user.create-user');
    }
}
