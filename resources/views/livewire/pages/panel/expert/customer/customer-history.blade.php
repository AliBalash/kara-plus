<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Customer /</span> History</h4>

    <div>
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('customer.detail', $customerId) }}">
                            <i class="bx bx-file me-1"></i> Contract Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('customer.history', $customerId) }}"><i
                                class="bx bx-history me-1"></i> History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bx bx-paperclip me-1"></i> Attachments</a>
                    </li>
                </ul>
                <div class="card mb-4">
                    <h5 class="card-header">Customer History</h5>
                    <div class="card-body">


                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th> <!-- افزودن ستون ID قرارداد -->
                                        <th>Customer</th>
                                        <th>Car</th>
                                        <th>Delivery Date</th>
                                        <th>Return Date</th>
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
                                            <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}
                                            </td>
                                            <td>
                                                @if ($contract->user)
                                                    <span
                                                        class="badge bg-primary">{{ $contract->user->fullName() }}</span>
                                                @else
                                                    <span class="badge bg-secondary">No User</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge 
                                                    @switch($contract->current_status)
                                                        @case('pending') bg-label-warning @break
                                                        @case('assigned') bg-label-info @break
                                                        @case('under_review') bg-label-secondary @break
                                                        @case('reserved') bg-label-primary @break
                                                        @case('delivery') bg-label-dark @break
                                                        @case('agreement_inspection') bg-label-light @break
                                                        @case('awaiting_return') bg-label-warning @break
                                                        @case('returned') bg-label-success @break
                                                        @case('complete') bg-label-success @break
                                                        @case('cancelled') bg-label-danger @break
                                                        @case('rejected') bg-label-danger @break
                                                        @default bg-label-secondary
                                                    @endswitch">
                                                    {{ ucfirst(str_replace('_', ' ', $contract->current_status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        @if (is_null($contract->user_id))
                                                            <a href="#" class="dropdown-item text-danger">
                                                                <i class="bx bx-error-alt me-1 text-danger"></i> Without
                                                                Expert
                                                            </a>
                                                        @endif
                                                        @if ($contract->user_id === auth()->id())
                                                            <a class="dropdown-item"
                                                                href="{{ route('rental-requests.details', $contract->id) }}">
                                                                <i class="bx bx-info-circle me-1"></i> Details
                                                            </a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('rental-requests.form', $contract->id) }}">
                                                                <i class="bx bx-edit-alt me-1"></i> Edit
                                                            </a>
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
                </div>
            </div>
        </div>

    </div>
</div>
