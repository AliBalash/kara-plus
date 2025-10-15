<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Inspection Contracts
    </h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search..." aria-label="Search"
                    wire:model.defer="searchInput">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="delivery">Awaiting Delivery</option>
                <option value="agreement_inspection">Agreement Inspection</option>
                <option value="awaiting_return">Awaiting Return</option>
                <option value="returned">Returned</option>
                <option value="complete">Complete</option>
                <option value="cancelled">Cancelled</option>
                <option value="all">All Statuses</option>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="userFilter">
                <option value="">All Contracts</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup From" wire:model.live="pickupFrom">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup To" wire:model.live="pickupTo">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return From" wire:model.live="returnFrom">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return To" wire:model.live="returnTo">
        </div>

        <div class="col-md-2">
            <button class="btn btn-secondary w-100" type="button" wire:click="clearFilters">Clear Filters</button>
        </div>
    </div>

    <!-- Display messages -->
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
                    <th>Car Type</th>
                    <th wire:click="sortBy('pickup_date')" role="button" class="sortable">
                        Pickup Date
                        <i
                            class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('return_date')" role="button" class="sortable">
                        Return Date
                        <i
                            class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Actions</th>
                    <th>Inspection Status</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($inspectionContracts as $contract)
                    @php
                        $pickupDocument = $contract->pickupDocument;
                        $tarsStatus = $pickupDocument && $pickupDocument->tars_approved_at ? 'Approved' : 'Pending';
                        $kardoStatus = $contract->kardo_required
                            ? ($pickupDocument && $pickupDocument->kardo_approved_at
                                ? 'Approved'
                                : 'Pending')
                            : 'Not Required';

                        $statusBadgeClasses = [
                            'Approved' => 'bg-success',
                            'Pending' => 'bg-warning text-dark',
                            'Not Required' => 'bg-secondary',
                        ];

                        $tarsBadgeClass = $statusBadgeClasses[$tarsStatus] ?? 'bg-secondary';
                        $kardoBadgeClass = $statusBadgeClasses[$kardoStatus] ?? 'bg-secondary';
                    @endphp
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ $contract->pickup_date?->format('d M Y H:i') }}</td>
                        <td>{{ $contract->return_date?->format('d M Y H:i') }}</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.inspection', $contract->id) }}">
                                        <i class="bx bx-check-circle me-1"></i> Inspect Documents
                                    </a>
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $contract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.edit', $contract->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>
                                    @if ($contract->current_status !== 'cancelled')
                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                            onclick="if(confirm('Are you sure you want to cancel this contract?')) { @this.cancelContract({{ $contract->id }}) }">
                                            <i class="bx bx-block me-1"></i> Cancel
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        wire:click.prevent="deleteContract({{ $contract->id }})">
                                        <i class="bx bx-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $tarsBadgeClass }}">TARS: {{ $tarsStatus }}</span>
                            <span class="badge {{ $kardoBadgeClass }} mt-1">CARDO: {{ $kardoStatus }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $inspectionContracts->links() }}
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
