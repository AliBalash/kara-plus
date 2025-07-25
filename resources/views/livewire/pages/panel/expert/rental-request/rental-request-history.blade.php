<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Rental Request /</span> History
    </h4>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <x-detail-rental-request-tabs :contract-id="$contract->id" />



    <div class="card ">
        <h5 class="card-header">Rental Request History</h5>
        <div class="card-body">

            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th> <!-- افزودن ستون ID قرارداد -->
                            <th>Customer</th>
                            <th>Car</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Expert</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @foreach ($statuses as $status)
                            <tr>
                                <td>{{ $status->id }}</td> <!-- نمایش ID تاریخچه وضعیت -->
                                <td>{{ $status->contract->customer->fullName() }}</td>
                                <td>{{ $status->contract->car->fullName() }}</td>
                                <td>{{ \Carbon\Carbon::parse($status->created_at)->format('d M Y') }}</td>
                                <td>{{ $status->contract->return_date ? \Carbon\Carbon::parse($status->contract->return_date)->format('d M Y') : 'N/A' }}
                                </td>
                                <td>
                                    @if ($status->contract->user)
                                        <span class="badge bg-primary">{{ $status->contract->user->shortName() }}</span>
                                    @else
                                        <span class="badge bg-secondary">No Expert</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$status->status" />

                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <!-- گزینه Details -->
                                            <a class="dropdown-item"
                                                href="{{ route('rental-requests.details', $status->contract->id) }}">
                                                <i class="bx bx-info-circle me-1"></i> Details
                                            </a>

                                            <!-- گزینه Edit -->
                                            <a class="dropdown-item"
                                                href="{{ route('expert.rental-requests.edit', $status->contract->id) }}">
                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                            </a>

                                            <!-- گزینه Delete -->
                                            @if ($status->contract->user_id === auth()->id())
                                                <a class="dropdown-item" href="javascript:void(0);"
                                                    wire:click.prevent="deleteContract({{ $status->contract->id }})">
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
    </div>

</div>
