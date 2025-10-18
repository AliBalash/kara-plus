<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Awaiting Delivery</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="awaitPickupSearch">Search</label>
                    <span class="filter-hint">Customer, plate, ID</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="awaitPickupSearch" type="search" class="form-control" placeholder="Start typing…"
                        wire:model.defer="searchInput">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                        wire:target="applySearch">
                        <span wire:loading.remove wire:target="applySearch">Apply</span>
                        <span wire:loading wire:target="applySearch">…</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupStatus">Status</label>
                <select id="awaitPickupStatus" class="form-select" wire:model.live="statusFilter">
                    <option value="reserved">Reserved</option>
                    <option value="delivery">Awaiting Delivery</option>
                    <option value="agreement_inspection">Agreement Inspection</option>
                    <option value="awaiting_return">Awaiting Return</option>
                    <option value="returned">Returned</option>
                    <option value="complete">Complete</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="all">All Statuses</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupAssignment">Assignment</label>
                <select id="awaitPickupAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupFrom">Pickup From</label>
                <input id="awaitPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupTo">Pickup To</label>
                <input id="awaitPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupReturnFrom">Return From</label>
                <input id="awaitPickupReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitPickupReturnTo">Return To</label>
                <input id="awaitPickupReturnTo" type="date" class="form-control" wire:model.live="returnTo">
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field h-100">
                <label class="filter-label">Reset</label>
                <button class="btn btn-outline-secondary" type="button" wire:click="clearFilters">Clear Filters</button>
            </div>
        </div>
    </div>

    @include('livewire.pages.panel.expert.rental-request.partials.filter-styles')

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')" role="button" class="sortable">
                        #
                        <i class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th wire:click="sortBy('pickup_date')" role="button" class="sortable">
                        Pickup Date
                        <i class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('return_date')" role="button" class="sortable">
                        Return Date
                        <i class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Driver</th>
                    <th>Status</th>
                    <th wire:click="sortBy('agent_sale')" role="button" class="sortable">
                        Sales Agent
                        <i class="bx {{ $sortField === 'agent_sale' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($contracts as $contract)
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer?->fullName() ?? '—' }}</td>
                        <td>{{ $contract->car?->fullName() ?? '—' }}</td>
                        <td>{{ $contract->pickup_date?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $contract->return_date?->format('d M Y H:i') ?? '-' }}</td>

                        <td>
                            @php
                                $assignedToCurrentDriver = $isDriver && $contract->driver_id === $driverId;
                                $driverName = $contract->driver?->shortName() ?? $contract->driver?->fullName() ?? null;
                            @endphp

                            @if ($assignedToCurrentDriver)
                                <span class="badge bg-success">Assigned to you</span>
                            @elseif ($driverName)
                                <span class="badge bg-info text-dark" title="Assigned driver">{{ $driverName }}</span>
                            @else
                                <span class="badge bg-label-secondary text-muted">Unassigned</span>
                            @endif

                            @if ($isDriver && is_null($contract->driver_id))
                                <button class="btn btn-sm btn-outline-primary mt-2"
                                    wire:click="assignToDriver({{ $contract->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="assignToDriver({{ $contract->id }})">
                                    <span wire:loading.remove wire:target="assignToDriver({{ $contract->id }})">Assign to me</span>
                                    <span wire:loading wire:target="assignToDriver({{ $contract->id }})">Assigning...</span>
                                </button>
                            @endif
                        </td>

                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>

                        <td>
                            <span class="badge {{ $contract->agent_sale ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $contract->agent_sale ?? '—' }}
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

                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.pickup-document', $contract->id) }}">
                                        <i class="bx bx-file me-1"></i> Pickup Document
                                    </a>

                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $contract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>
                                    @if (!$isDriver && $contract->current_status !== 'cancelled')
                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                            onclick="if(confirm('Are you sure you want to cancel this contract?')) { @this.cancelContract({{ $contract->id }}) }">
                                            <i class="bx bx-block me-1"></i> Cancel
                                        </a>
                                    @endif
                                    @if (!$isDriver)
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deleteContract({{ $contract->id }})">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">No contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-center px-3 py-2">
            <div>
                {{ $contracts->links() }}
            </div>
        </div>
    </div>
@once
    @push('styles')
        <style>
            th.sortable {
                cursor: pointer;
                user-select: none;
            }

            th.sortable i {
                margin-left: 0.35rem;
            }

            th.sortable:hover {
                color: #007bff;
            }
        </style>
    @endpush
@endonce
</div>
