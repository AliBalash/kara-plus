<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Booking</h4>

    <div class="row" style="padding: 0.5rem 1.5rem">
        <div class="">
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
        <!-- /Search -->
    </div>


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
                    <th>#</th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th>
                        <a href="#" wire:click="sortBy('pickup_date')">
                            Pickup Date
                            @if ($sortField === 'pickup_date')
                                <i
                                    class="bx {{ $sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                            @else
                                <i class="bx bx-sort"></i>
                            @endif
                        </a>
                    </th>
                    <th>Location</th>
                    <th>Actions</th>
                    <th>Agent Sale</th>
                    <th>Expert</th>
                    <th>Document</th>
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
                                    @if ($reservedContract->user_id === auth()->id())
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
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $reservedContract->agent_sale }}</td>

                        <td>
                            @if ($reservedContract->user)
                                <span class="badge bg-primary">{{ $reservedContract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
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

@push('styles')
    <style>
        th a {
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        th a:hover {
            color: #007bff;
            /* Bootstrap primary color or your preferred hover color */
        }
    </style>
@endpush
