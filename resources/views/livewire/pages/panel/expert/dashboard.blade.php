<div>
    <div class="row g-4">

       


        @cannot('car')


            {{-- Reserved Cars --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100 shadow-sm border-1">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2"><i class="bi bi-car-front me-2 text-warning"></i>Reserved Cars</h5>
                    </div>
                    <div class="card-body" style="max-height: 550px; overflow-y: auto;" data-simplebar>
                        <ul class="list-unstyled mb-0">
                            @forelse ($reservedCars as $contract)
                                <li class="d-flex mb-4 pb-3 border-bottom">
                                    <div class="me-3">
                                        <img src="{{ $contract->car->carModel->image ? asset('assets/car-pics/' . $contract->car->carModel->image->file_name) : asset('assets/car-pics/car test.webp') }}"
                                            alt="Car Image" width="200" class="rounded">
                                    </div>
                                    <div class="d-flex w-100 flex-column justify-content-between">
                                        <div>
                                            <span class="badge bg-warning mb-1">Reserved</span>
                                            <h6 class="mb-1">{{ $contract->car->fullname() }}</h6>
                                            <small class="text-muted d-block">
                                                {{ \Carbon\Carbon::parse($contract->pickup_date)->translatedFormat('d M Y') }}
                                                -
                                                {{ \Carbon\Carbon::parse($contract->return_date)->translatedFormat('d M Y') }}
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-person-circle me-1"></i>Customer:
                                                {{ $contract->customer->fullName() }}
                                            </small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <div class="text-success fw-bold">
                                                ${{ number_format($contract->total_price, 2) }} <small
                                                    class="text-muted">AED</small></div>
                                            <a href="{{ route('expert.rental-requests.edit', $contract->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-center text-muted">No reserved cars found.</li>
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
                    <div class="card h-100 fi shadow-sm border-1">
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-{{ $stat['color'] }} text-white rounded p-2">
                                    <i class="bi bi-{{ $stat['icon'] }} fs-2 m-1"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">{{ $stat['label'] }}</div>
                                    <div class="h5 fw-semibold mb-0">{{ $stat['value'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Latest Contracts --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3"><i class="bi bi-clock-history me-1 text-info"></i>Latest Contracts</h5>
                        <ul class="list-unstyled">
                            @foreach ($latestContracts as $contract)
                                <li><i class="bi bi-person-fill me-1 text-secondary"></i> {{ $contract->customer->name }} –
                                    {{ ucfirst($contract->current_status) }} – ${{ $contract->total_price }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Last User Contract Status --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="fw-semibold mb-3"><i class="bi bi-person-check-fill text-success me-1"></i>My Last
                            Contract Status</h5>
                        <h3 class="text-primary">{{ ucfirst($lastUserContractStatus) ?? 'N/A' }}</h3>
                    </div>
                </div>
            </div>

            {{-- Top Cars with Most Contracts --}}
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3"><i class="bi bi-trophy-fill text-warning me-1"></i>Top 3 Cars with Most
                            Contracts</h5>
                        <table class="table table-bordered align-middle">
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
                                        <td>{{ $car['total'] }}</td>
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
