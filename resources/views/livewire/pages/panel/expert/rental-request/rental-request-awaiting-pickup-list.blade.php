<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Awaiting Pickup</h4>

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

    <!-- پیام‌ها -->
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
                    <th>#</th>
                    <th>Customer</th>
                    <th>Car</th>
                    <th>Pickup Date</th>
                    <th>Return Date</th>
                    <th>Actions</th>
                    <th>Status</th>
                    <th>Expert</th>
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

                                    @if ($contract->user_id === auth()->id())
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $contract->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>

                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deleteContract({{ $contract->id }})">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>

                        <td>
                            @if ($contract->user)
                                <span class="badge bg-primary">{{ $contract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No contracts found.</td>
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
</div>
