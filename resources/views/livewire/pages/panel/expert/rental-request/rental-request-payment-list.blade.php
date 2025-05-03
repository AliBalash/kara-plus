<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Payment Contracts</h4>

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
                    <th>#</th> <!-- Contract or Payment Number -->
                    <th>Customer</th>
                    <th>Car Type</th>
                    <th>Delivery Date</th>
                    <th>Total Contract Amount</th>
                    <th>Amount Paid</th>
                    <th>Remaining</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($paymentContracts as $contract)
                    @php
                        $paid = $contract->payments->sum('amount');
                        $remaining = $contract->total_price - $paid;
                    @endphp
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ $contract->pickup_date ? $contract->pickup_date->format('d M Y') : '-' }}</td>
                        <td>{{ number_format($contract->total_price, 2) }} Toman</td>
                        <td>{{ number_format($paid, 2) }} Toman</td>
                        <td>{{ number_format($remaining, 2) }} Toman</td>
                        <td>
                            @if ($remaining <= 0)
                                <span class="badge bg-success">Settled</span>
                            @else
                                <span class="badge bg-warning">Remaining</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">

                                    <a class="dropdown-item"
                                        href="{{ route('rental-requests.payment', [$contract->id, $contract->customer->id]) }}">
                                        <i class="bx bx-money me-1"></i> Payment
                                    </a>

                                    @if ($contract->user_id === auth()->id())
                                        <!-- Details option -->

                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $contract->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>

                                        <!-- Edit option -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.form', $contract->id) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>

                                        <!-- Delete option -->
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
