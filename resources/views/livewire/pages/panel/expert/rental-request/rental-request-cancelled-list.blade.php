<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Cancelled</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="cancelledSearch">Search</label>
                    <span class="filter-hint">Customer, plate, ID</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="cancelledSearch" type="search" class="form-control" placeholder="Start typing…"
                        wire:model.defer="searchInput">
                    <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"
                        wire:target="applySearch">
                        <span wire:loading.remove wire:target="applySearch">Apply</span>
                        <span wire:loading wire:target="applySearch">…</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="cancelledAssignment">Assignment</label>
                <select id="cancelledAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="cancelledPickupFrom">Pickup From</label>
                <input id="cancelledPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="cancelledPickupTo">Pickup To</label>
                <input id="cancelledPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="cancelledReturnFrom">Return From</label>
                <input id="cancelledReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="cancelledReturnTo">Return To</label>
                <input id="cancelledReturnTo" type="date" class="form-control" wire:model.live="returnTo">
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field h-100">
                <label class="filter-label">Reset</label>
                <button class="btn btn-outline-secondary" wire:click="clearFilters">Clear Filters</button>
            </div>
        </div>
    </div>

    @include('livewire.pages.panel.expert.rental-request.partials.filter-styles')

    <div class="table-responsive mt-3">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')" role="button">
                        #
                        <i class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th wire:click="sortBy('pickup_date')" role="button">
                        Pickup Date
                        <i class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('return_date')" role="button">
                        Return Date
                        <i class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('updated_at')" role="button">
                        Cancelled At
                        <i class="bx {{ $sortField === 'updated_at' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Actions</th>
                    <th>Status</th>
                    <th>Sales Agent</th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($contracts as $contract)
                    <tr wire:key="cancelled-contract-{{ $contract->id }}">
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ $contract->car->fullName() }}</span>
                                <x-car-ownership-badge :car="$contract->car" />
                            </div>
                        </td>
                        <td>{{ optional($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ optional($contract->return_date)->format('d M Y') }}</td>
                        <td>{{ optional($contract->updated_at)->format('d M Y H:i') }}</td>
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
                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>
                        <td>
                            <span class="badge {{ $contract->agent?->name ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $contract->agent?->name ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $contract->submitted_by_name ?? 'Website' }}</span>
                        </td>
                        <td>
                            @if ($contract->user)
                                <span class="badge bg-success">{{ $contract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-label-secondary text-muted">Unassigned</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No cancelled contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $contracts->links() }}
    </div>
</div>
