<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Booking</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="reservedSearch">Search</label>
                    <span class="filter-hint">Customer, plate, ID</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="reservedSearch" type="search" class="form-control" placeholder="Start typing…"
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
                <label class="filter-label" for="reservedStatus">Status</label>
                <select id="reservedStatus" class="form-select" wire:model.live="statusFilter">
                    <option value="reserved">Reserved</option>
                    <option value="delivery">Awaiting Delivery</option>
                    <option value="agreement_inspection">Agreement Inspection</option>
                    <option value="awaiting_return">Awaiting Return</option>
                    <option value="returned">Returned</option>
                    <option value="payment">Payment</option>
                    <option value="complete">Complete</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="all">All Statuses</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="reservedAssignment">Assignment</label>
                <select id="reservedAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="reservedPickupFrom">Pickup From</label>
                <input id="reservedPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="reservedPickupTo">Pickup To</label>
                <input id="reservedPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="reservedReturnFrom">Return From</label>
                <input id="reservedReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="reservedReturnTo">Return To</label>
                <input id="reservedReturnTo" type="date" class="form-control" wire:model.live="returnTo">
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
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" wire:key="success-message">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')" role="button" class="sortable">
                        #
                        <i
                            class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th wire:click="sortBy('pickup_date')" role="button" class="sortable">
                        Pickup Date
                        <i
                            class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Location</th>
                    <th wire:click="sortBy('agent_sale')" role="button" class="sortable">
                        Sales Agent
                        <i
                            class="bx {{ $sortField === 'agent_sale' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                    <th>Actions</th>
                    <th>Documents</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($reservedContracts as $reservedContract)
                    <tr>
                        <td>{{ $reservedContract->id }}</td>
                        <td>{{ $reservedContract->customer->fullName() }}</td>
                        <td>{{ $reservedContract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($reservedContract->pickup_date)->format('d M Y H:i') }}</td>
                        <td>{{ $reservedContract->pickup_location }}</td>
                        <td>
                            <span class="badge {{ $reservedContract->agent_sale ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $reservedContract->agent_sale ?? '—' }}
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-info text-dark">{{ $reservedContract->submitted_by_name ?? 'Website' }}</span>
                        </td>

                        <td>
                            @if ($reservedContract->user)
                                <span class="badge bg-success">{{ $reservedContract->user->shortName() }}</span>
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
                                        href="{{ route('rental-requests.pickup-document', $reservedContract->id) }}">
                                        <i class="bx bx-file me-1"></i> Delivery Document
                                    </a>
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $reservedContract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>
                                    @if ($reservedContract->current_status !== 'cancelled')
                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                            onclick="if(confirm('Are you sure you want to cancel this contract?')) { @this.cancelContract({{ $reservedContract->id }}) }">
                                            <i class="bx bx-block me-1"></i> Cancel
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($reservedContract->customerDocument()->exists())
                                <span class="badge bg-warning">📄 Customer</span>
                            @endif
                            @if ($reservedContract->ReturnDocument()->exists())
                                <span class="badge bg-success">📄 Return</span>
                            @endif
                            @if ($reservedContract->pickupDocument()->exists())
                                <span class="badge bg-primary">📄 Deliver</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Add Pagination Links -->
        <div class="mt-4">
            {{ $reservedContracts->links() }}
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
