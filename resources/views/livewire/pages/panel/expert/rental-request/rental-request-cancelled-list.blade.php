<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Cancelled</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search..." wire:model.defer="searchInput">
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="userFilter"
                title="Filter contracts by whether they are assigned to a user">
                <option value="">All Contracts</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup From" wire:model.live="pickupFrom"
                title="Filter contracts starting from this pickup date">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup To" wire:model.live="pickupTo"
                title="Filter contracts until this pickup date">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return From" wire:model.live="returnFrom"
                title="Filter contracts starting from this return date">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return To" wire:model.live="returnTo"
                title="Filter contracts until this return date">
        </div>

        <div class="col-md-2">
            <button class="btn btn-secondary w-100" wire:click="clearFilters">Clear Filters</button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info mt-2">{{ session('info') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-2">{{ session('error') }}</div>
    @endif

    <div class="table-responsive mt-3">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')" role="button">
                        #
                        <i
                            class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th wire:click="sortBy('pickup_date')" role="button">
                        Pickup Date
                        <i
                            class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('return_date')" role="button">
                        Return Date
                        <i
                            class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('updated_at')" role="button">
                        Cancelled At
                        <i
                            class="bx {{ $sortField === 'updated_at' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Status</th>
                    <th>Agent Sale</th>
                    <th>Expert</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $contract)
                    <tr wire:key="cancelled-contract-{{ $contract->id }}">
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ optional($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ optional($contract->return_date)->format('d M Y') }}</td>
                        <td>{{ optional($contract->updated_at)->format('d M Y H:i') }}</td>
                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>
                        <td>{{ $contract->agent_sale }}</td>
                        <td>
                            @if ($contract->user)
                                <span class="badge bg-primary">{{ $contract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('rental-requests.details', $contract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>
                                    <a class="dropdown-item" href="{{ route('rental-requests.edit', $contract->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        wire:click.prevent="deleteContract({{ $contract->id }})">
                                        <i class="bx bx-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No cancelled contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $contracts->links() }}
    </div>
</div>
