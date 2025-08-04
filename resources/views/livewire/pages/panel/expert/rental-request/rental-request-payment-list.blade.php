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
                    <th>#</th>
                    <th>Customer</th>
                    <th>Car Type</th>
                    <th>Pickup Date</th>
                    <th>Total Contract (AED)</th>
                    <th>Remaining (AED)</th>
                    <th>Actions</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($paymentContracts as $contract)
                    @php
                        // Get all payments converted to AED
                        $payments = $contract->payments;

                        $rentalPaid = $payments->where('payment_type', 'rental_fee')->sum('amount_in_aed');
                        $fines = $payments->where('payment_type', 'fine')->sum('amount_in_aed');
                        $discounts = $payments->where('payment_type', 'discount')->sum('amount_in_aed');
                        $prepaid = $payments->where('payment_type', 'prepaid_fine')->sum('amount_in_aed');

                        $remaining = $contract->total_price - ($rentalPaid + $discounts) + $fines;
                    @endphp
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>{{ $contract->customer->fullName() }}</td>
                        <td>{{ $contract->car->fullName() }}</td>
                        <td>{{ $contract->pickup_date?->format('d M Y') }}</td>
                        <td>{{ number_format($contract->total_price, 2) }}</td>
                        <td>{{ number_format($remaining, 2) }}</td>
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
                                            href="{{ route('expert.rental-requests.edit', $contract->id) }}">
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
                        <td>
                            @if ($remaining <= 0)
                                <span class="badge bg-success">Settled</span>
                            @else
                                <span class="badge bg-warning">Pending ({{ number_format($remaining, 2) }} AED)</span>
                            @endif
                            @if ($contract->payments->where('is_refundable', true)->count())
                                <span class="badge bg-info mt-1">Refundable</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>


    </div>


</div>
