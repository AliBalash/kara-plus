<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Reserved</h4>

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
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Expert</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($reservedContracts as $reservedContract)
                    <tr>
                        <td>{{ $reservedContract->id }}</td> <!-- نمایش ID قرارداد -->
                        <td>{{ $reservedContract->customer->fullName() }}</td>
                        <td>{{ $reservedContract->car->fullName() }}</td>
                        <td>{{ \Carbon\Carbon::parse($reservedContract->start_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($reservedContract->end_date)->format('d M Y') }}</td>
                        <td>
                            @if ($reservedContract->user)
                                <span class="badge bg-primary">{{ $reservedContract->user->fullName() }}</span>
                            @else
                                <span class="badge bg-secondary">No User</span>
                            @endif
                        </td>
                        <td>
                            <span
                                class="badge 
        
                                @switch($reservedContract->current_status)
                                    @case('pending') bg-label-warning @break
                                    @case('assigned') bg-label-info @break
                                    @case('under_review') bg-label-secondary @break
                                    @case('reserved') bg-label-primary @break
                                    @case('delivery_in_progress') bg-label-dark @break
                                    @case('agreement_inspection') bg-label-light @break
                                    @case('awaiting_return') bg-label-warning @break
                                    @case('returned') bg-label-success @break
                                    @case('complete') bg-label-success @break
                                    @case('cancelled') bg-label-danger @break
                                    @case('rejected') bg-label-danger @break
                                    @default bg-label-secondary
                                @endswitch">
                                {{ ucfirst(str_replace('_', ' ', $reservedContract->current_status)) }}
                            </span>

                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">


                                    <!-- گزینه Pickup Document -->
                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.pickup-document', $reservedContract->id) }}">
                                        <i class="bx bx-file me-1"></i> Pickup Document
                                    </a>

                                    
                                    @if (is_null($reservedContract->user_id))
                                        <!-- گزینه Assign to Me -->
                                        <a wire:click.prevent="assignToMe({{ $reservedContract->id }})"
                                            class="dropdown-item" href="javascript:void(0);">
                                            <i class="bx bx-user-check me-1"></i> Assign to Me
                                        </a>
                                    @endif
                                    @if ($reservedContract->user_id === auth()->id())
                                        <!-- گزینه Details -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $reservedContract->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>

                                        <!-- گزینه Edit -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.form', $reservedContract->id) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>




                                        <!-- گزینه Delete -->
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deleteContract({{ $reservedContract->id }})">
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
