<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Awaiting Return</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="awaitReturnSearch">Search</label>
                    <span class="filter-hint">Customer, plate, ID</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="awaitReturnSearch" type="search" class="form-control" placeholder="Start typing…"
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
                <label class="filter-label" for="awaitReturnStatus">Status</label>
                <select id="awaitReturnStatus" class="form-select" wire:model.live="statusFilter">
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
                <label class="filter-label" for="awaitReturnAssignment">Assignment</label>
                <select id="awaitReturnAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitReturnPickupFrom">Pickup From</label>
                <input id="awaitReturnPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitReturnPickupTo">Pickup To</label>
                <input id="awaitReturnPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitReturnReturnFrom">Return From</label>
                <input id="awaitReturnReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="awaitReturnReturnTo">Return To</label>
                <input id="awaitReturnReturnTo" type="date" class="form-control" wire:model.live="returnTo">
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


    <!-- نمایش پیام‌ها -->
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
                    <th wire:click="sortBy('return_date')" role="button" class="sortable">
                        Return Date
                        <i class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Return Location</th>
                    <th>Driver</th>
                    <th>Actions</th>
                    <th>Status</th>
                    <th wire:click="sortBy('agent_sale')" role="button" class="sortable">
                        Sales Agent
                        <i class="bx {{ $sortField === 'agent_sale' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                    <th>Documents</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($awaitContracts as $awaitContract)
                    <tr>
                        <td>{{ $awaitContract->id }}</td>
                        <td>
                            <div>{{ $awaitContract->customer->fullName() }}</div>
                            <div class="text-muted small">{{ $awaitContract->customer->phone ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ $awaitContract->car->fullName() }}</span>
                                <x-car-ownership-badge :car="$awaitContract->car" />
                            </div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($awaitContract->return_date)->format('d M Y H:i') }}</td>
                        <td>{{ $awaitContract->return_location }}</td>
                        <td>
                            @php
                                $assignedToCurrentDriver = $isDriver && $awaitContract->driver_id === $driverId;
                                $driverName = $awaitContract->driver?->shortName() ?? $awaitContract->driver?->fullName() ?? null;
                            @endphp

                            @if ($assignedToCurrentDriver)
                                <span class="badge bg-success">Assigned to you</span>
                            @elseif ($driverName)
                                <span class="badge bg-info text-dark" title="Assigned driver">{{ $driverName }}</span>
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
                                        href="{{ route('rental-requests.return-document', $awaitContract->id) }}">
                                        <i class="bx bx-file me-1"></i> Return Document
                                    </a>
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $awaitContract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>
                                    @if (!$isDriver && $awaitContract->current_status !== 'cancelled')
                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                            onclick="if(confirm('Are you sure you want to cancel this contract?')) { @this.cancelContract({{ $awaitContract->id }}) }">
                                            <i class="bx bx-block me-1"></i> Cancel
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-status-badge :status="$awaitContract->current_status" />
                        </td>
                        <td>
                            <span class="badge {{ $awaitContract->agent_sale ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $awaitContract->agent_sale ?? '—' }}
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-info text-dark">{{ $awaitContract->submitted_by_name ?? 'Website' }}</span>
                        </td>

                        <td>
                            @if ($awaitContract->user)
                                <span class="badge bg-success">{{ $awaitContract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-label-secondary text-muted">Unassigned</span>
                            @endif
                        </td>
                        </td>
                        <td>
                            @if ($awaitContract->customerDocument()->exists())
                                <span class="badge bg-warning">📄 Customer</span>
                            @endif
                            @if ($awaitContract->ReturnDocument()->exists())
                                <span class="badge bg-success">📄 Return</span>
                            @endif
                            @if ($awaitContract->pickupDocument()->exists())
                                <span class="badge bg-primary">📄 Deliver</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted">No contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Add Pagination Links -->
        <div class="mt-4">
            {{ $awaitContracts->links() }}
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
