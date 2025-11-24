<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Detail</h4>

    <x-detail-rental-request-tabs :contract-id="$contract->id" />

    @if (session()->has('message'))
        <div class="alert alert-success my-4">
            {{ session('message') }}
        </div>
    @endif

    @php
        $pickupDate = $contract->pickup_date instanceof \Illuminate\Support\Carbon
            ? $contract->pickup_date
            : ($contract->pickup_date
                ? \Illuminate\Support\Carbon::parse($contract->pickup_date)
                : null);
        $returnDate = $contract->return_date instanceof \Illuminate\Support\Carbon
            ? $contract->return_date
            : ($contract->return_date
                ? \Illuminate\Support\Carbon::parse($contract->return_date)
                : null);
        $createdAt = $contract->created_at instanceof \Illuminate\Support\Carbon
            ? $contract->created_at
            : ($contract->created_at
                ? \Illuminate\Support\Carbon::parse($contract->created_at)
                : null);

        $durationDisplay = null;
        if ($pickupDate && $returnDate) {
            $diffInHours = $pickupDate->diffInHours($returnDate);
            $durationDisplay = $diffInHours >= 24
                ? round($diffInHours / 24, 1) . ' days'
                : $diffInHours . ' hrs';
        } elseif ($pickupDate) {
            $durationDisplay = 'Ongoing';
        }

        $statusLabel = $contract->statusLabel();
        $totalPriceDisplay = is_numeric($contract->total_price)
            ? number_format((float) $contract->total_price, 2)
            : ($contract->total_price ?? '—');
        if ($totalPriceDisplay !== '—' && !empty($contract->currency)) {
            $totalPriceDisplay .= ' ' . $contract->currency;
        }

        $dailyRateDisplay = is_numeric($contract->used_daily_rate)
            ? number_format((float) $contract->used_daily_rate, 2)
            : null;

        $paymentMethod = $contract->payment_on_delivery === null
            ? null
            : ($contract->payment_on_delivery
                ? 'Pay on delivery'
                : 'Prepaid');

        $agentName = optional($contract->user)->fullName()
            ?? optional($contract->user)->name
            ?? ($contract->submitted_by_name ?? '—');

        $deliveryDriverName = optional($contract->deliveryDriver)->fullName()
            ?? optional($contract->deliveryDriver)->name
            ?? 'Unassigned';
        $returnDriverName = optional($contract->returnDriver)->fullName()
            ?? optional($contract->returnDriver)->name
            ?? 'Unassigned';

        $pickupDocument = $contract->relationLoaded('pickupDocument')
            ? $contract->pickupDocument
            : $contract->pickupDocument()->first();
        $returnDocument = $contract->relationLoaded('ReturnDocument')
            ? $contract->getRelation('ReturnDocument')
            : $contract->ReturnDocument()->first();
        $customerDocument = $contract->relationLoaded('customerDocument')
            ? $contract->customerDocument
            : $contract->customerDocument()->first();

        $pickupLocation = $contract->pickup_location ?? '—';
        $returnLocation = $contract->return_location ?? '—';
        $submittedBy = $contract->submitted_by_name
            ?? (optional($contract->user)->fullName() ?? optional($contract->user)->name ?? 'Website');
    @endphp

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">Contract #{{ $contract->id }}</h5>
                        <small class="text-muted">Created {{ $createdAt?->format('d M Y · H:i') ?? '—' }}</small>
                    </div>
                    <span class="badge bg-label-primary text-uppercase">{{ $statusLabel }}</span>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                        <div class="col">
                            <div class="border rounded-3 p-3 h-100">
                                <small class="text-muted text-uppercase fw-medium">Total amount</small>
                                <div class="fs-5 fw-semibold text-body">{{ $totalPriceDisplay }}</div>
                                @if ($dailyRateDisplay)
                                    <div class="text-muted small">Daily rate {{ $dailyRateDisplay }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 h-100">
                                <small class="text-muted text-uppercase fw-medium">Trip duration</small>
                                <div class="fs-5 fw-semibold text-body">{{ $durationDisplay ?? '—' }}</div>
                                <div class="text-muted small">Pickup {{ $pickupDate?->format('d M Y') ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 h-100">
                                <small class="text-muted text-uppercase fw-medium">Payment</small>
                                <div class="fs-5 fw-semibold text-body">{{ $paymentMethod ?? '—' }}</div>
                                <div class="text-muted small">Submitted by {{ $submittedBy }}</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="border rounded-3 p-3 h-100">
                                <small class="text-muted text-uppercase fw-medium">Account manager</small>
                                <div class="fs-5 fw-semibold text-body">{{ $agentName }}</div>
                                <div class="text-muted small">Delivery driver {{ $deliveryDriverName }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-3 h-100 p-3 bg-light">
                                <small class="text-muted text-uppercase fw-medium d-block mb-2">Pickup</small>
                                <div class="fw-semibold text-body fs-6 mb-1">{{ $pickupDate?->format('d M Y · H:i') ?? '—' }}</div>
                                <div class="text-muted small">{{ $pickupLocation }}</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <div class="border rounded-3 h-100 p-3 bg-light">
                                <small class="text-muted text-uppercase fw-medium d-block mb-2">Return</small>
                                <div class="fw-semibold text-body fs-6 mb-1">{{ $returnDate?->format('d M Y · H:i') ?? '—' }}</div>
                                <div class="text-muted small">{{ $returnLocation }}</div>
                            </div>
                        </div>
                        <div class="col-md-12 col-xl-4">
                            <div class="border rounded-3 h-100 p-3">
                                <small class="text-muted text-uppercase fw-medium d-block mb-2">Logistics &amp; documents</small>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-3">
                                        <div class="text-muted text-uppercase small">Return driver</div>
                                        <div class="fw-semibold text-body">{{ $returnDriverName }}</div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="text-muted text-uppercase small">KARDO requirement</div>
                                        <div class="fw-semibold text-body">{{ $contract->kardo_required === null ? '—' : ($contract->kardo_required ? 'Required' : 'Not required') }}</div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="text-muted text-uppercase small">Customer documents</div>
                                        <div class="fw-semibold {{ $customerDocument ? 'text-success' : 'text-danger' }}">
                                            {{ $customerDocument ? 'Received' : 'Missing' }}
                                        </div>
                                    </li>
                                    <li class="mb-3">
                                        <div class="text-muted text-uppercase small">Pickup agreement</div>
                                        <div class="fw-semibold text-body">
                                            {{ $pickupDocument?->agreement_number ?? '—' }}
                                        </div>
                                    </li>
                                    <li>
                                        <div class="text-muted text-uppercase small">Return inspection</div>
                                        <div class="fw-semibold {{ $returnDocument ? 'text-success' : 'text-muted' }}">
                                            {{ $returnDocument ? 'Completed' : 'Pending' }}
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    @if (filled($contract->notes) || filled($contract->discount_note))
                        <hr class="my-4">
                        <div class="row g-4">
                            @if (filled($contract->notes))
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small mb-2">Internal notes</h6>
                                    <p class="mb-0 text-body">{{ $contract->notes }}</p>
                                </div>
                            @endif
                            @if (filled($contract->discount_note))
                                <div class="col-md-6">
                                    <h6 class="text-muted text-uppercase small mb-2">Discount note</h6>
                                    <p class="mb-0 text-body">{{ $contract->discount_note }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header border-0">
                    <h5 class="mb-0">Customer</h5>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-sm-2 g-3">
                        <div class="col">
                            <span class="text-muted text-uppercase small">First name</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->first_name ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Last name</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->last_name ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Email</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->email ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Phone</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->phone ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Nationality</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->nationality ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">National code</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->national_code ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Passport number</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->passport_number ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Passport expiry</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->passport_expiry_date ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">License number</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->license_number ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Licensed driver name</span>
                            <div class="fw-semibold text-body">{{ $contract->licensed_driver_name ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <span class="text-muted text-uppercase small">Address</span>
                            <div class="fw-semibold text-body">{{ $contract->customer->address ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header border-0">
                    <h5 class="mb-0">Vehicle</h5>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-sm-2 g-3">
                        <div class="col">
                            <span class="text-muted text-uppercase small">Brand</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->carModel?->brand ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Model</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->carModel?->model ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Plate number</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->plate_number ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Manufacturing year</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->manufacturing_year ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Daily price</span>
                            <div class="fw-semibold text-body">
                                @if (is_numeric(optional($contract->car)->price_per_day))
                                    {{ number_format((float) $contract->car->price_per_day, 2) }}
                                @else
                                    {{ $contract->car->price_per_day ?? '—' }}
                                @endif
                            </div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Service due</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->service_due_date ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Used daily rate</span>
                            <div class="fw-semibold text-body">{{ $dailyRateDisplay ?? '—' }}</div>
                        </div>
                        <div class="col">
                            <span class="text-muted text-uppercase small">Status</span>
                            <div class="fw-semibold text-body">{{ $contract->car?->status ? \Illuminate\Support\Str::headline($contract->car->status) : '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
