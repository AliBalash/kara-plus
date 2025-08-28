<div class="container-fluid py-4">

    <div class="row g-4">

        @cannot('car')

            {{-- Reserved Cars --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header bg-warning text-white d-flex align-items-center rounded-top-4">
                        <i class="bi bi-car-front-fill me-2 fs-4"></i>
                        <h5 class="m-0 fw-bold">Reserved Cars</h5>
                    </div>
                    <div class="card-body p-3" style="max-height: 550px; overflow-y: auto;" data-simplebar>
                        @forelse ($reservedCars as $contract)
                            <div class="card mb-3 shadow-sm border-0 rounded-3 hover-shadow">
                                <div class="row g-0 align-items-center">
                                    <div class="col-4">
                                        <img src="{{ $contract->car->carModel->image ? asset('assets/car-pics/' . $contract->car->carModel->image->file_name) : asset('assets/car-pics/car test.webp') }}"
                                            class="img-fluid rounded-start" alt="Car">
                                    </div>
                                    <div class="col-8">
                                        <div class="card-body py-2">
                                            <span class="badge bg-warning text-dark mb-2">Reserved</span>
                                            <h6 class="fw-bold mb-1">{{ $contract->car->fullname() }}</h6>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar me-1"></i>
                                                {{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }} -
                                                {{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-person-circle me-1"></i>
                                                {{ $contract->customer->fullName() }}
                                            </small>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span
                                                    class="fw-bold text-success">${{ number_format($contract->total_price, 2) }}</span>
                                                <a href="{{ route('rental-requests.edit', $contract->id) }}"
                                                    class="btn btn-sm btn-outline-dark">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted">No reserved cars found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Returned Cars --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-header bg-success text-white d-flex align-items-center rounded-top-4">
                        <i class="bi bi-arrow-counterclockwise me-2 fs-4"></i>
                        <h5 class="m-0 fw-bold">Returned Cars</h5>
                    </div>
                    <div class="card-body p-3" style="max-height: 550px; overflow-y: auto;" data-simplebar>
                        <ul class="list-unstyled mb-0">
                            @forelse ($returnedCars as $contract)
                                <li class="d-flex mb-4 pb-3 border-bottom">
                                    <div class="me-3">
                                        <img src="{{ $contract->car->carModel->image ? asset('assets/car-pics/' . $contract->car->carModel->image->file_name) : asset('assets/car-pics/car test.webp') }}"
                                            alt="Car Image" width="180" class="rounded shadow-sm">
                                    </div>
                                    <div class="d-flex flex-column justify-content-between w-100">
                                        <div>
                                            <span class="badge bg-success mb-1">Returned</span>
                                            <h6 class="fw-bold mb-1">{{ $contract->car->fullname() }}</h6>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                {{ \Carbon\Carbon::parse($contract->pickup_date)->translatedFormat('d M Y') }}
                                                -
                                                {{ \Carbon\Carbon::parse($contract->return_date)->translatedFormat('d M Y') }}
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-person-circle me-1"></i>
                                                {{ $contract->customer->fullName() }}
                                            </small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span
                                                class="fw-bold text-success">${{ number_format($contract->total_price, 2) }}
                                                <small class="text-muted">AED</small></span>
                                            <a href="{{ route('rental-requests.edit', $contract->id) }}"
                                                class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-center text-muted">No returned cars found.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Quick Stats --}}
            @php
                $stats = [
                    [
                        'label' => 'Total Discount Codes',
                        'value' => $discountCodesCount,
                        'icon' => 'ticket-perforated-fill',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Used Discount Codes',
                        'value' => "$usedDiscountCodes ($usageRate%)",
                        'icon' => 'check-circle-fill',
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Average Discount %',
                        'value' => "$averageDiscount%",
                        'icon' => 'percent',
                        'color' => 'info',
                    ],
                    [
                        'label' => 'My Discount Codes',
                        'value' => "$userDiscountCodes (Used: $userUsedDiscountCodes)",
                        'icon' => 'gift-fill',
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Total Contracts',
                        'value' => $totalContracts,
                        'icon' => 'file-earmark-text-fill',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Active Contracts',
                        'value' => $activeContracts,
                        'icon' => 'play-circle-fill',
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Completed Contracts',
                        'value' => $completedContracts,
                        'icon' => 'check2-circle',
                        'color' => 'info',
                    ],
                    [
                        'label' => 'Cancelled Contracts',
                        'value' => $cancelledContracts,
                        'icon' => 'x-circle-fill',
                        'color' => 'danger',
                    ],
                    [
                        'label' => 'Contracts Under Review',
                        'value' => $underReviewContracts,
                        'icon' => 'search',
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Avg Contract Price',
                        'value' => '$' . number_format($averageTotalPrice, 2),
                        'icon' => 'cash-stack',
                        'color' => 'dark',
                    ],
                ];
            @endphp

            @foreach ($stats as $stat)
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div
                                class="bg-{{ $stat['color'] }} text-white rounded p-3 d-flex align-items-center justify-content-center">
                                <i class="bi bi-{{ $stat['icon'] }} fs-3"></i>
                            </div>
                            <div>
                                <div class="text-muted small">{{ $stat['label'] }}</div>
                                <div class="h5 fw-bold mb-0">{{ $stat['value'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Latest Contracts --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-info"></i>Latest Contracts</h5>
                        <ul class="list-group list-group-flush">
                            @foreach ($latestContracts as $contract)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-person-fill me-1 text-secondary"></i>
                                        {{ $contract->customer->name }} –
                                        <span class="badge bg-secondary">{{ ucfirst($contract->current_status) }}</span>
                                    </div>
                                    <span class="fw-bold text-success">${{ $contract->total_price }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Last User Contract Status --}}
            <div class="col-12 col-lg-6">
                <div class="card shadow-lg border-0 rounded-4 h-100 text-center">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person-check-fill text-success me-2"></i>My Last Contract
                            Status</h5>
                        <h3 class="text-primary fw-bold">{{ ucfirst($lastUserContractStatus) ?? 'N/A' }}</h3>
                    </div>
                </div>
            </div>

            {{-- Top Cars --}}
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-4 h-100">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Top 3 Cars with Most
                            Contracts</h5>
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="bi bi-car-front me-1"></i>Brand</th>
                                    <th><i class="bi bi-graph-up me-1"></i>Total Contracts</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topBrands as $car)
                                    <tr>
                                        <td>{{ $car['brand'] }}</td>
                                        <td class="fw-bold">{{ $car['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @endcannot

    </div>

</div>
