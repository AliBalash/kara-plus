<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> List</h4>


    <!-- Filters -->
    <div class="row p-3 g-3">
        <!-- Search -->
        <div class="col-md-3">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search..." wire:model.defer="searchInput">
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"
                    wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <!-- Status Filter -->
        <!-- Status Filter -->
        <div class="col-md-2">
            <select class="form-select" wire:model.live="statusFilter" title="Filter contracts by their current status">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="assigned">Assigned</option>
                <option value="under_review">Under Review</option>
                <option value="reserved">Reserved</option>
                <option value="delivery">Delivery</option>
                <option value="agreement_inspection">Agreement Inspection</option>
                <option value="awaiting_return">Awaiting Return</option>
                <option value="returned">Returned</option>
                <option value="complete">Complete</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <!-- User Filter -->
        <div class="col-md-2">
            <select class="form-select" wire:model.live="userFilter"
                title="Filter contracts by whether they are assigned to a user">
                <option value="">All Contracts</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <!-- Date Filters -->
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

        <!-- Clear Filters -->
        <div class="col-md-2">
            <button class="btn btn-secondary w-100" wire:click="clearFilters">Clear Filters</button>
        </div>
    </div>


    <!-- نمایش پیام‌ها -->
    <!-- Flash Messages -->
    @if (session('success'))
        <div class="alert alert-success mt-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-2">{{ session('error') }}</div>
    @endif

    <!-- Table -->
    <div class="table-responsive mt-3">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')">
                        # <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th wire:click="sortBy('pickup_date')">
                        Pickup Date <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th wire:click="sortBy('return_date')">
                        Return Date <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th wire:click="sortBy('current_status')">
                        Status <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th>Agent Sale</th>
                    <th>Expert</th>
                    <th>Actions</th>
                    <th>Documents</th>
                </tr>
            </thead>
            <tbody wire:poll.10s>
                @forelse($contracts as $contract)
                    <tr wire:key="contract-{{ $contract->id }}">
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}</td>
                        <td>
                            <span
                                class="badge 
                                @switch($contract->current_status)
                                    @case('pending') bg-warning @break
                                    @case('assigned') bg-primary @break
                                    @case('under_review') bg-info @break
                                    @case('reserved') bg-secondary @break
                                    @case('delivery') bg-dark @break
                                    @case('agreement_inspection') bg-success @break
                                    @case('awaiting_return') bg-light text-dark @break
                                    @case('returned') bg-success @break
                                    @case('complete') bg-success @break
                                    @case('cancelled') bg-danger @break
                                    @case('rejected') bg-danger @break
                                    @default bg-secondary
                                @endswitch">
                                {{ ucfirst(str_replace('_', ' ', $contract->current_status)) }}
                            </span>
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
                                    @if (is_null($contract->user_id))
                                        <a wire:click.prevent="assignToMe({{ $contract->id }})" class="dropdown-item"
                                            href="javascript:void(0);">
                                            <i class="bx bx-user-check me-1"></i> Assign to Me
                                        </a>
                                    @endif
                                    {{-- <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $contract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a> --}}
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
                            @if ($contract->customerDocument()->exists())
                                <span class="badge bg-warning">📄 Customer</span>
                            @endif
                            @if ($contract->ReturnDocument()->exists())
                                <span class="badge bg-success">📄 Return</span>
                            @endif
                            @if ($contract->pickupDocument()->exists())
                                <span class="badge bg-primary">📄 Deliver</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $contracts->links() }}
    </div>
</div>

</div>
