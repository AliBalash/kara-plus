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
                    <th>#</th> <!-- ID پرداخت -->
                    <th>Customer</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($paymentContracts as $payment)
                    <!-- دسترسی به پرداخت‌های قرارداد -->
                    <tr>
                        <td>{{ $payment->id }}</td>
                        <td>{{ $payment->customer->fullName() }}</td>
                        <td>{{ $payment->transaction_id }}</td>
                        <td>{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>

                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <!-- گزینه مشاهده جزئیات -->
                                    @if ($payment->user_id === auth()->id())
                                        <!-- گزینه Details -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.details', $payment->id) }}">
                                            <i class="bx bx-info-circle me-1"></i> Details
                                        </a>

                                        <!-- گزینه Edit -->
                                        <a class="dropdown-item"
                                            href="{{ route('rental-requests.form', $payment->id) }}">
                                            <i class="bx bx-edit-alt me-1"></i> Edit
                                        </a>

                                        <!-- گزینه Delete -->
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deleteContract({{ $payment->id }})">
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
