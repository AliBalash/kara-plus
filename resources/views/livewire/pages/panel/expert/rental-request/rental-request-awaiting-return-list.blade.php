<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Await Contracts</h4>

    <div class="row" style="padding: 0.5rem 1.5rem">
        <div class="">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..."
                    aria-label="Search..." wire:model.live.debounce.1000ms="search">
            </div>
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
                        <a href="#" wire:click="sortBy('return_date')">
                            Return Date
                            @if ($sortField === 'return_date')
                                <i
                                    class="bx {{ $sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' }}"></i>
                            @else
                                <i class="bx bx-sort"></i>
                            @endif
                        </a>
                    </th>
                    <th>Return Location</th>
                    <th>Actions</th>
                    <th>Status</th>
                    <th>Agent Sale</th>
                    <th>Expert</th>
                    <th>Document</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($awaitContracts as $awaitContract)
                    <tr>
                        <td>{{ $awaitContract->id }}</td>
                        <td>{{ $awaitContract->customer->fullName() }}</td>
                        <td>{{ $awaitContract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($awaitContract->return_date)->format('d M Y H:i') }}</td>
                        <td>{{ $awaitContract->return_location }}</td>
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
                                    @if ($awaitContract->user_id === auth()->id())
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $awaitContract->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-status-badge :status="$awaitContract->current_status" />
                        </td>
                        <td>{{ $awaitContract->agent_sale }}</td>

                        <td>
                            @if ($awaitContract->user)
                                <span class="badge bg-primary">{{ $awaitContract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
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
                @endforeach
            </tbody>
        </table>

        <!-- Add Pagination Links -->
        <div class="mt-4">
            {{ $awaitContracts->links() }}
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
            /* Or your preferred hover color */
        }
    </style>
@endpush
