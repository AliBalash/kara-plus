<div class="container-xl py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Team /</span> Users &amp; Roles</h4>
            <p class="text-muted mb-0">Assign or remove the driver role for operational staff.</p>
        </div>
        <div class="badge bg-primary-subtle text-primary px-3 py-2">
            <i class="bx bx-id-card me-1"></i> Drivers: {{ $driverCount }}
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" class="form-control" placeholder="Search name, email or phone" wire:model.live.debounce.400ms="search" autocomplete="off" enterkeyhint="search">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Roles</th>
                            <th class="text-end">Driver Access</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->shortName() }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <span class="badge bg-label-primary me-1">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">No role</span>
                                    @endforelse
                                </td>
                                <td class="text-end">
                                    @if ($user->hasRole('driver'))
                                        <button wire:click="removeDriver({{ $user->id }})" class="btn btn-sm btn-outline-danger">
                                            <i class="bx bx-user-minus me-1"></i> Remove driver
                                        </button>
                                    @else
                                        <button wire:click="assignDriver({{ $user->id }})" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-user-plus me-1"></i> Make driver
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                @if ($users->total())
                    Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}
                @else
                    No users to display
                @endif
            </div>
            <div>{{ $users->links() }}</div>
        </div>
    </div>
</div>
