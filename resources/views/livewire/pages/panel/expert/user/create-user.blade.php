<div class="container-xl py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Team /</span> Create user</h4>
            <p class="text-muted mb-0">Super Admins can onboard teammates and fine-tune their access in one place.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-primary px-3 py-2">
                <i class="bx bx-shield-quarter me-1"></i> Roles: {{ $roles->count() }}
            </span>
            <span class="badge bg-label-info px-3 py-2">
                <i class="bx bx-key me-1"></i> Permissions: {{ $permissions->count() }}
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Profile</h5>
                        <small class="text-muted">Capture the essentials for the new teammate.</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary">
                        <i class="bx bx-user-plus me-1"></i> New account
                    </span>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="first-name">First name</label>
                            <input id="first-name" type="text" wire:model.live="firstName"
                                class="form-control @error('firstName') is-invalid @enderror"
                                placeholder="e.g. Sara" autocomplete="off">
                            @error('firstName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="last-name">Last name</label>
                            <input id="last-name" type="text" wire:model.live="lastName"
                                class="form-control @error('lastName') is-invalid @enderror"
                                placeholder="e.g. Rahimi" autocomplete="off">
                            @error('lastName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Work email</label>
                            <input id="email" type="email" wire:model.live="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="name@company.com" autocomplete="off">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="phone">Phone (optional)</label>
                            <input id="phone" type="text" wire:model.live="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                placeholder="09xxxxxxxxx" autocomplete="tel">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-lock-open"></i></span>
                                <input id="password" type="password" wire:model.live="password"
                                    class="form-control @error('password') is-invalid @enderror" autocomplete="new-password"
                                    placeholder="Minimum 8 characters">
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-check-shield"></i></span>
                                <input id="password_confirmation" type="password" wire:model.live="passwordConfirmation"
                                    class="form-control @error('passwordConfirmation') is-invalid @enderror"
                                    autocomplete="new-password" placeholder="Repeat password">
                                @error('passwordConfirmation')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bx bx-save me-1"></i> Create user
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Roles</h5>
                        <small class="text-muted">Choose the responsibilities for this profile.</small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearRoles">
                            <i class="bx bx-eraser me-1"></i> Clear
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllRoles">
                            <i class="bx bx-select-multiple me-1"></i> Select all
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($roles as $role)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" value="{{ $role->name }}"
                                id="role-{{ $role->id }}" wire:model.live="selectedRoles">
                            <label class="form-check-label d-flex flex-column" for="role-{{ $role->id }}">
                                <span class="fw-semibold text-capitalize">{{ $role->name }}</span>
                                <small class="text-muted">Tap to give or revoke this role.</small>
                            </label>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No roles defined yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Permissions</h5>
                        <small class="text-muted">Granular access for unique responsibilities.</small>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="clearPermissions">
                            <i class="bx bx-eraser me-1"></i> Clear
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="selectAllPermissions">
                            <i class="bx bx-select-multiple me-1"></i> Select all
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if ($permissions->count())
                        <div class="row g-3">
                            @foreach ($permissions->chunk(ceil($permissions->count() / 2)) as $group)
                                <div class="col-12 col-md-6">
                                    @foreach ($group as $permission)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox"
                                                id="permission-{{ $permission->id }}" value="{{ $permission->name }}"
                                                wire:model.live="selectedPermissions">
                                            <label class="form-check-label text-capitalize"
                                                for="permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No permissions available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
