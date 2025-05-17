<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Contracts Me</h4>

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
                    <th>#</th> <!-- افزودن ستون ID قرارداد -->
                    <th>Customer</th>
                    <th>Car</th>
                    <th>Delivery Date</th>
                    <th>End Date</th>
                    <th>Expert</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($contracts as $contract)
                    <tr>
                        <td>{{ $contract->id }}</td> <!-- نمایش ID قرارداد -->
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}</td>
                        <td>
                            @if ($contract->user)
                                <span class="badge bg-primary">{{ $contract->user->fullName() }}</span>
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
                                            href="{{ route('rental-requests.form', $contract->id) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>
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
                @endforeach
            </tbody>
        </table>
    </div>
</div>
