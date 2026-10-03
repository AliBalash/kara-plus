@php
    $options = $car->options->pluck('option_value', 'option_key');
    $booking = $car->currentContract;
    $insurance = $car->latestInsurance;
    $passingDue = $car->passing_date && $car->passing_valid_for_days !== null
        ? $car->passing_date->copy()->addDays($car->passing_valid_for_days) : null;
    $registrationDue = $car->issue_date && $car->registration_valid_for_days !== null
        ? $car->issue_date->copy()->addDays($car->registration_valid_for_days) : null;
    $activeHold = $car->activeScheduledUnavailabilityPeriod();
    $upcomingHold = $car->upcomingScheduledUnavailabilityPeriod();
    $statusNote = $car->operationalStatusContextNote();
@endphp

<div class="car-detail">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <a href="{{ route('car.list') }}" class="car-detail__back"><i class="bx bx-left-arrow-alt"></i> Back to cars</a>
        <a href="{{ route('car.edit', $car->id) }}" class="btn btn-primary"><i class="bx bx-edit-alt me-1"></i> Edit car</a>
    </div>

    <section class="card border-0 shadow-sm mb-4 overflow-hidden car-detail__hero">
        <div class="row g-0">
            <div class="col-lg-4 car-detail__photo-wrap"><img src="{{ $car->primaryImageUrl() }}" alt="{{ $car->modelName() }}" class="car-detail__photo"></div>
            <div class="col-lg-8 card-body p-4 p-xl-5 d-flex flex-column justify-content-center">
                <div class="car-detail__eyebrow">Fleet vehicle / #{{ $car->id }}</div>
                <h2 class="car-detail__title mb-2">{{ $car->modelName() }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <span class="car-detail__plate">{{ $car->plate_number ?: 'Plate not set' }}</span>
                    <span class="badge {{ $car->operationalStatusBadgeClass() }}">{{ $car->operationalStatusLabel() }}</span>
                    <x-car-ownership-badge :car="$car" />
                </div>
                <div class="text-muted">{{ $car->manufacturing_year ?: 'Year not set' }} · {{ $car->color ?: 'Color not set' }}</div>
            </div>
        </div>
    </section>

    @if ($car->unavailabilityReasonLabel() || $statusNote || $activeHold || $upcomingHold)
        <div class="alert alert-warning border-0 d-flex gap-2 mb-4" role="status">
            <i class="bx bx-info-circle fs-4"></i>
            <div>
                <strong>Availability context</strong>
                @if ($car->unavailabilityReasonLabel())<div>Reason: {{ $car->unavailabilityReasonLabel() }}</div>@endif
                @if ($statusNote)<div>{{ $statusNote }}</div>@endif
                @if ($activeHold)<div>Active hold: {{ $activeHold->reasonLabel() }} · {{ $activeHold->dateWindowLabel() }}</div>@endif
                @if ($upcomingHold)<div>Upcoming hold: {{ $upcomingHold->reasonLabel() }} · {{ $upcomingHold->dateWindowLabel() }}</div>@endif
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card h-100 car-detail__metric"><div class="card-body"><div class="car-detail__label">Mileage</div><strong>{{ $car->mileage !== null ? number_format($car->mileage).' km' : 'Not set' }}</strong></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 car-detail__metric"><div class="card-body"><div class="car-detail__label">Daily rate · short</div><strong>{{ $car->price_per_day_short !== null ? number_format((float) $car->price_per_day_short, 2).' AED' : 'Not set' }}</strong></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 car-detail__metric"><div class="card-body"><div class="car-detail__label">Service due</div><strong class="{{ $car->service_due_date?->isPast() ? 'text-danger' : '' }}">{{ $car->service_due_date?->format('Y-m-d') ?? 'Not set' }}</strong></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100 car-detail__metric"><div class="card-body"><div class="car-detail__label">Insurance expiry</div><strong class="{{ $insurance?->expiry_date?->isPast() ? 'text-danger' : '' }}">{{ $insurance?->expiry_date?->format('Y-m-d') ?? 'Not set' }}</strong></div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Booking & availability</h5></div>
                <div class="card-body">
                    @if ($booking)
                        <div class="car-detail__booking p-3 rounded-3 mb-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                <div><div class="car-detail__label">Current / upcoming booking</div><strong>Contract #{{ $booking->id }}</strong></div>
                                <a href="{{ route('rental-requests.details', $booking->id) }}" class="btn btn-sm btn-outline-primary">View contract <i class="bx bx-right-arrow-alt"></i></a>
                            </div>
                            <div>{{ $booking->customer?->fullName() ?? 'Customer not set' }}</div>
                            <div class="text-muted small mt-1">{{ $booking->pickup_date?->format('Y-m-d H:i') ?? 'Pickup not set' }} <i class="bx bx-right-arrow-alt"></i> {{ $booking->return_date?->format('Y-m-d H:i') ?? 'Return not set' }}</div>
                            <div class="text-muted small mt-1">Status: {{ ucwords(str_replace('_', ' ', $booking->current_status ?? 'unknown')) }}</div>
                        </div>
                    @else
                        <div class="car-detail__empty mb-3">No active or upcoming booking is linked to this car.</div>
                    @endif
                    <div class="car-detail__facts">
                        <div><span>Operational status</span><strong>{{ $car->operationalStatusLabel() }}</strong></div>
                        <div><span>Base status</span><strong>{{ \App\Models\Car::manualStatusLabels()[$car->manual_status ?? $car->status] ?? ucfirst((string) $car->status) }}</strong></div>
                        <div><span>Availability</span><strong>{{ $car->isAvailable() ? 'Available for booking' : 'Not available for booking' }}</strong></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Vehicle details</h5></div>
                <div class="card-body car-detail__facts">
                    <div><span>Plate number</span><strong>{{ $car->plate_number ?: 'Not set' }}</strong></div>
                    <div><span>Chassis number</span><strong>{{ $car->chassis_number ?: 'Not set' }}</strong></div>
                    <div><span>Ownership</span><strong>{{ $car->ownershipLabel() }}</strong></div>
                    <div><span>GPS</span><strong>{{ $car->gps === null ? 'Not set' : ($car->gps ? 'Yes' : 'No') }}</strong></div>
                    <div><span>Year / color</span><strong>{{ $car->manufacturing_year ?: '—' }} / {{ $car->color ?: '—' }}</strong></div>
                </div>
            </section>
        </div>

        <div class="col-xl-7">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Daily pricing <small class="text-muted fw-normal">· AED</small></h5></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0 car-detail__prices">
                        <thead><tr><th scope="col">Rate</th><th scope="col">Short</th><th scope="col">Mid</th><th scope="col">Long</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Rental</th><td>{{ $car->price_per_day_short !== null ? number_format((float) $car->price_per_day_short, 2) : '—' }}</td><td>{{ $car->price_per_day_mid !== null ? number_format((float) $car->price_per_day_mid, 2) : '—' }}</td><td>{{ $car->price_per_day_long !== null ? number_format((float) $car->price_per_day_long, 2) : '—' }}</td></tr>
                            <tr><th scope="row">LDW</th><td>{{ $car->ldw_price_short !== null ? number_format((float) $car->ldw_price_short, 2) : '—' }}</td><td>{{ $car->ldw_price_mid !== null ? number_format((float) $car->ldw_price_mid, 2) : '—' }}</td><td>{{ $car->ldw_price_long !== null ? number_format((float) $car->ldw_price_long, 2) : '—' }}</td></tr>
                            <tr><th scope="row">SCDW</th><td>{{ $car->scdw_price_short !== null ? number_format((float) $car->scdw_price_short, 2) : '—' }}</td><td>{{ $car->scdw_price_mid !== null ? number_format((float) $car->scdw_price_mid, 2) : '—' }}</td><td>{{ $car->scdw_price_long !== null ? number_format((float) $car->scdw_price_long, 2) : '—' }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Documents & service</h5></div>
                <div class="card-body car-detail__facts">
                    <div><span>Insurance</span><strong>{{ $insurance?->expiry_date?->format('Y-m-d') ?? 'Not set' }} @if ($insurance?->status)<small class="text-muted">({{ ucfirst($insurance->status) }})</small>@endif</strong></div>
                    <div><span>Service due</span><strong>{{ $car->service_due_date?->format('Y-m-d') ?? 'Not set' }}</strong></div>
                    <div><span>Registration expiry</span><strong>{{ $car->expiry_date?->format('Y-m-d') ?? $registrationDue?->format('Y-m-d') ?? 'Not set' }}</strong></div>
                    <div><span>Passing due</span><strong>{{ $passingDue?->format('Y-m-d') ?? 'Not set' }} @if ($car->passing_status)<small class="text-muted">({{ ucfirst($car->passing_status) }})</small>@endif</strong></div>
                </div>
            </section>
        </div>

        <div class="col-xl-7">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Equipment & rental terms</h5></div>
                <div class="card-body car-detail__facts">
                    <div><span>Transmission</span><strong>{{ $options->get('gear') ? ucfirst($options->get('gear')) : 'Not set' }}</strong></div>
                    <div><span>Fuel type</span><strong>{{ $options->get('fuel_type') ? ucfirst($options->get('fuel_type')) : 'Not set' }}</strong></div>
                    <div><span>Seats / doors / luggage</span><strong>{{ $options->get('seats', '—') }} / {{ $options->get('doors', '—') }} / {{ $options->get('luggage', '—') }}</strong></div>
                    <div><span>Minimum rental</span><strong>{{ $options->get('min_days') ? $options->get('min_days').' days' : 'Not set' }}</strong></div>
                    <div><span>Unlimited kilometers</span><strong>{{ $options->has('unlimited_km') ? ($options->get('unlimited_km') ? 'Yes' : 'No') : 'Not set' }}</strong></div>
                    <div><span>Base insurance</span><strong>{{ $options->has('base_insurance') ? ($options->get('base_insurance') ? 'Included' : 'Not included') : 'Not set' }}</strong></div>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom"><h5 class="mb-0">Internal notes</h5></div>
                <div class="card-body">
                    <div class="car-detail__label">General</div><p class="car-detail__note">{{ $car->notes ?: 'No notes recorded.' }}</p>
                    <div class="car-detail__label">Damage report</div><p class="car-detail__note mb-0">{{ $car->damage_report ?: 'No damage report recorded.' }}</p>
                </div>
            </section>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4"><a href="{{ route('car.edit', $car->id) }}" class="btn btn-outline-primary"><i class="bx bx-edit-alt me-1"></i> Edit vehicle information</a></div>

    <style>
        .car-detail { color: #172b4d; }
        .car-detail__back { display: inline-flex; align-items: center; gap: .35rem; font-weight: 600; }
        .car-detail__back i { font-size: 1.3rem; }
        .car-detail__hero { background: linear-gradient(120deg, #fff 45%, #f1f8ff); }
        .car-detail__photo-wrap { min-height: 245px; background: #edf2f8; }
        .car-detail__photo { display: block; width: 100%; height: 100%; max-height: 350px; min-height: 245px; object-fit: cover; }
        .car-detail__eyebrow, .car-detail__label { color: #6b7c93; font-size: .78rem; font-weight: 600; letter-spacing: .02em; }
        .car-detail__eyebrow { text-transform: uppercase; letter-spacing: .12em; margin-bottom: .55rem; }
        .car-detail__title { font-size: clamp(1.65rem, 3vw, 2.4rem); font-weight: 750; }
        .car-detail__plate { padding: .28rem .65rem; border: 1px solid #d7e0eb; border-radius: .5rem; background: #fff; font-weight: 700; letter-spacing: .04em; }
        .car-detail__metric { border: 1px solid #e4eaf2; box-shadow: 0 .35rem 1.25rem rgba(32, 53, 89, .035); }
        .car-detail__metric strong { display: block; margin-top: .4rem; font-size: 1.13rem; }
        .car-detail__booking { background: #f0f7ff; border: 1px solid #dbeafb; }
        .car-detail__empty { padding: 1rem; border: 1px dashed #cfd9e5; border-radius: .65rem; color: #687b91; }
        .car-detail__facts > div { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: .67rem 0; border-bottom: 1px solid #edf1f5; }
        .car-detail__facts > div:last-child { border-bottom: 0; }
        .car-detail__facts span { color: #6b7c93; }
        .car-detail__facts strong { text-align: right; overflow-wrap: anywhere; }
        .car-detail__prices th, .car-detail__prices td { padding: .7rem .5rem; white-space: nowrap; }
        .car-detail__note { margin-top: .4rem; white-space: pre-line; overflow-wrap: anywhere; }
        @media (max-width: 575px) { .car-detail__facts > div { display: block; } .car-detail__facts strong { display: block; text-align: left; margin-top: .2rem; } }
    </style>
</div>
