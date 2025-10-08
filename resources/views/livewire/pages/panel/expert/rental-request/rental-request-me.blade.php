<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Contracts Me</h4>

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
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="assigned">Assigned</option>
                <option value="under_review">Under Review</option>
                <option value="reserved">Booking</option>
                <option value="delivery">Delivery</option>
                <option value="agreement_inspection">Agreement Inspection</option>
                <option value="awaiting_return">Return</option>
                <option value="returned">Returned</option>
                <option value="complete">Complete</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
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
            <button type="button" class="btn btn-secondary w-100" wire:click="clearFilters">Clear Filters</button>
        </div>
    </div>

    <!-- نمایش پیام‌ها -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
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
                    <th class="cursor-pointer" wire:click="sortBy('id')">
                        # <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th class="cursor-pointer" wire:click="sortBy('pickup_date')">
                        Pickup Date <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th class="cursor-pointer" wire:click="sortBy('return_date')">
                        Return Date <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th>Agent Sale</th>
                    <th>Expert</th>
                    <th class="cursor-pointer" wire:click="sortBy('current_status')">
                        Status <i class="bx bx-sort-alt-2"></i>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0" wire:poll.10s>
                @forelse ($contracts as $contract)
                    <tr wire:key="contract-me-{{ $contract->id }}">
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}</td>
                        <td>{{ $contract->agent_sale }}</td>
                        <td>
                            @if ($contract->user)
                                <span class="badge bg-primary">{{ $contract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
                        </td>
                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    @if (is_null($contract->user_id))
                                        <!-- گزینه Assign to Me -->
                                        <a wire:click.prevent="assignToMe({{ $contract->id }})" class="dropdown-item"
                                            href="javascript:void(0);">
                                            <i class="bx bx-user-check me-1"></i> Assign to Me
                                        </a>
                                    @endif
                                    @if ($contract->user_id === auth()->id())
                                        <!-- گزینه Details -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $contract->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>

                                        <!-- گزینه Edit -->
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
                                    @endif

                                    <!-- گزینه Delete -->
                                    @if ($contract->user_id === auth()->id())
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
                        <td colspan="9" class="text-center">No contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3 px-3">
        {{ $contracts->links() }}
    </div>
</div>
