<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 py-3 mb-4">
        <div>
            <div class="text-muted small mb-1">Rental Request</div>
            <h4 class="fw-bold mb-0">Contract Detail</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-label-primary text-uppercase">{{ $contract->statusLabel() }}</span>
            <span class="badge bg-label-secondary">#{{ $contract->id }}</span>
        </div>
    </div>

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

        $payments = $contract->relationLoaded('payments')
            ? $contract->payments
            : $contract->payments()->get();
        $paidAmount = (float) $payments->where('is_paid', true)->sum('amount_in_aed');
        $pendingAmount = (float) $payments->where('is_paid', false)->sum('amount_in_aed');
        $rentalCollected = (float) $payments->where('payment_type', 'rental_fee')->sum('amount_in_aed');
        $depositCollected = (float) $payments->where('payment_type', 'security_deposit')->sum('amount_in_aed');
        $fineCharges = (float) $payments->where('payment_type', 'fine')->sum('amount_in_aed');
        $salikCharges = (float) $payments
            ->whereIn('payment_type', ['salik', 'salik_4_aed', 'salik_6_aed', 'salik_other_revenue'])
            ->sum('amount_in_aed');
        $outstandingBalance = $contract->calculateRemainingBalance($payments);

        $incomingTransfers = $contract->relationLoaded('incomingBalanceTransfers')
            ? (float) $contract->incomingBalanceTransfers->sum('amount')
            : (float) $contract->incomingBalanceTransfers()->sum('amount');
        $outgoingTransfers = $contract->relationLoaded('outgoingBalanceTransfers')
            ? (float) $contract->outgoingBalanceTransfers->sum('amount')
            : (float) $contract->outgoingBalanceTransfers()->sum('amount');
        $netTransfers = $incomingTransfers - $outgoingTransfers;
    @endphp

    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                        <div>
                            <div class="text-muted small">Created {{ $createdAt?->format('d M Y · H:i') ?? '—' }}</div>
                            <div class="fw-semibold text-body">Managed by {{ $agentName }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            @if ($paymentMethod)
                                <span class="badge bg-label-info">{{ $paymentMethod }}</span>
                            @endif
                            <span class="badge bg-label-secondary">{{ $submittedBy }}</span>
                        </div>
                    </div>

                    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
                        <div class="col">
                            <div class="p-3 rounded-3 border h-100 bg-light">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-muted text-uppercase fw-medium">Contract value</small>
                                    <span class="badge bg-label-primary">{{ $contract->currency ?? 'AED' }}</span>
                                </div>
                                <div class="fs-4 fw-bold text-body">{{ $totalPriceDisplay }}</div>
                                <div class="text-muted small">Daily rate {{ $dailyRateDisplay ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-3 border h-100">
                                <small class="text-muted text-uppercase fw-medium">Paid to date</small>
                                <div class="fs-4 fw-bold text-success">{{ number_format($paidAmount, 2) }} AED</div>
                                <div class="text-muted small">Pending {{ number_format($pendingAmount, 2) }} AED</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-3 border h-100">
                                <small class="text-muted text-uppercase fw-medium">Outstanding</small>
                                <div class="fs-4 fw-bold {{ $outstandingBalance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($outstandingBalance, 2) }} AED
                                </div>
                                <div class="text-muted small">Includes Salik, parking &amp; fines</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="p-3 rounded-3 border h-100 bg-light">
                                <small class="text-muted text-uppercase fw-medium">Transfers</small>
                                <div class="fs-5 fw-semibold">Net {{ number_format($netTransfers, 2) }} AED</div>
                                <div class="d-flex gap-2 flex-wrap text-muted small">
                                    <span>In {{ number_format($incomingTransfers, 2) }} AED</span>
                                    <span class="text-muted">•</span>
                                    <span>Out {{ number_format($outgoingTransfers, 2) }} AED</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-xl-7">
                            <div class="border rounded-3 h-100 p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="text-muted text-uppercase fw-medium">Payment breakdown</small>
                                        <div class="text-muted small">Follow what was collected vs. due items</div>
                                    </div>
                                    <span class="badge bg-label-info">Live</span>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div class="p-3 rounded-3 bg-light h-100">
                                            <div class="text-muted text-uppercase small mb-1">Rental collected</div>
                                            <div class="fw-bold fs-5 text-body">{{ number_format($rentalCollected, 2) }} AED</div>
                                            <div class="text-muted small">Security deposit {{ number_format($depositCollected, 2) }} AED</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-3 rounded-3 border h-100">
                                            <div class="text-muted text-uppercase small mb-1">Add-ons &amp; penalties</div>
                                            <div class="fw-bold fs-5 text-body">{{ number_format($fineCharges + $salikCharges, 2) }} AED</div>
                                            <div class="text-muted small">Fines {{ number_format($fineCharges, 2) }} · Salik {{ number_format($salikCharges, 2) }}</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-3 rounded-3 border h-100">
                                            <div class="text-muted text-uppercase small mb-1">Drivers</div>
                                            <div class="fw-semibold text-body">Delivery: {{ $deliveryDriverName }}</div>
                                            <div class="text-muted small">Return: {{ $returnDriverName }}</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="p-3 rounded-3 bg-light h-100">
                                            <div class="text-muted text-uppercase small mb-1">Status guardrail</div>
                                            <div class="fw-semibold text-body">{{ $pickupDocument ? 'Pickup inspection done' : 'Pickup inspection missing' }}</div>
                                            <div class="text-muted small">{{ $contract->kardo_required ? 'KARDO required' : 'KARDO not required' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div class="border rounded-3 h-100 p-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <small class="text-muted text-uppercase fw-medium">Timeline</small>
                                        <div class="text-muted small">All critical milestones at a glance</div>
                                    </div>
                                    <span class="badge bg-label-primary">{{ $durationDisplay ?? '—' }}</span>
                                </div>
                                <ul class="list-unstyled mb-0">
                                    <li class="d-flex align-items-start mb-3">
                                        <span class="badge bg-label-primary me-2">Pick</span>
                                        <div>
                                            <div class="fw-semibold text-body">Pickup</div>
                                            <div class="text-muted small">{{ $pickupDate?->format('d M Y · H:i') ?? '—' }} — {{ $pickupLocation }}</div>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <span class="badge bg-label-success me-2">Ret</span>
                                        <div>
                                            <div class="fw-semibold text-body">Return</div>
                                            <div class="text-muted small">{{ $returnDate?->format('d M Y · H:i') ?? '—' }} — {{ $returnLocation }}</div>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start mb-3">
                                        <span class="badge bg-label-info me-2">Docs</span>
                                        <div>
                                            <div class="fw-semibold text-body">Documents</div>
                                            <div class="text-muted small">Customer {{ $customerDocument ? 'received' : 'missing' }} · Pickup {{ $pickupDocument?->agreement_number ?? '—' }}</div>
                                        </div>
                                    </li>
                                    <li class="d-flex align-items-start">
                                        <span class="badge bg-label-warning me-2">Inspect</span>
                                        <div>
                                            <div class="fw-semibold text-body">Return inspection</div>
                                            <div class="text-muted small">{{ $returnDocument ? 'Completed' : 'Pending' }}</div>
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
