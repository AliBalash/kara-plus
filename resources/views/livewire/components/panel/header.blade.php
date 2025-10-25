<div>
    <nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
        id="layout-navbar">
        <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
            <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" role="button" aria-controls="layout-menu"
                aria-expanded="false" data-menu-toggle="layout-menu">
                <i class="bx bx-menu bx-sm"></i>
            </a>
        </div>

        <div class="navbar-nav-right d-flex align-items-center w-100 justify-content-end justify-content-lg-between flex-wrap gap-3 position-relative"
            id="navbar-collapse">

            <!-- تاریخ - چپ -->
            <div class="d-none d-lg-flex align-items-center flex-shrink-0">
                <i class="bx bx-calendar fs-4 lh-0"></i>
                <span class="ms-2">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
            </div>

            @php
                $showSearch = !auth()->user()?->hasRole('driver');
            @endphp






            <ul class="navbar-nav flex-row align-items-center flex-shrink-0">
                @if ($showSearch)
                    <li class="nav-item me-2">
                        <button type="button" class="btn btn-icon btn-outline-secondary rounded-circle shadow-sm"
                            data-bs-toggle="offcanvas" data-bs-target="#quickSearchOffcanvas"
                            aria-controls="quickSearchOffcanvas" aria-label="Open quick vehicle search">
                            <i class="bx bx-car"></i>
                        </button>
                    </li>
                    <li class="nav-item me-2">
                        <button type="button" class="btn btn-icon btn-outline-secondary rounded-circle shadow-sm"
                            data-bs-toggle="offcanvas" data-bs-target="#agreementSearchOffcanvas"
                            aria-controls="agreementSearchOffcanvas" aria-label="Open agreement lookup">
                            <i class="bx bx-file-find"></i>
                        </button>
                    </li>
                @endif
                <!-- Place this tag where you want the button to render. -->
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            <img src="{{ Auth::user()->avatar ? asset('storage/' . ltrim(Auth::user()->avatar, '/')) : asset('assets/panel/assets/img/avatars/unknow.jpg') }}"
                                alt="User Avatar" class="w-px-40 h-auto rounded-circle" />

                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.me') }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            <img src="{{ Auth::user()->avatar ? asset('storage/' . ltrim(Auth::user()->avatar, '/')) : asset('assets/panel/assets/img/avatars/unknow.jpg') }}"
                                                alt class="w-px-40 h-auto rounded-circle" />
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold d-block">
                                            {{ Auth::check() ? Auth::user()->fullname() : 'Guest' }}
                                        </span>
                                        <small class="text-muted">Expert</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.me') }}">
                                <i class="bx bx-user me-2"></i>
                                <span class="align-middle">My Profile</span>
                            </a>
                        </li>
                        {{-- <li>
                        <a class="dropdown-item" href="#">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li> --}}
                        {{-- <li>
                        <a class="dropdown-item" href="#">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 bx bx-credit-card me-2"></i>
                                <span class="flex-grow-1 align-middle">Message</span>
                                <span
                                    class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                            </span>
                        </a>
                    </li> --}}
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" wire:click.prevent="logout">
                                <i class="bx bx-power-off me-2"></i>
                                <span class="align-middle">Log Out</span>
                            </a>
                        </li>

                    </ul>
                </li>
                <!--/ User -->
            </ul>
        </div>
    </nav>

    @if ($showSearch)
        <div class="offcanvas offcanvas-end search-offcanvas" tabindex="-1" id="quickSearchOffcanvas" wire:ignore.self
            aria-labelledby="quickSearchLabel" data-bs-scroll="true">
            <div class="offcanvas-header border-bottom">
                <div>
                    <h5 class="offcanvas-title mb-1" id="quickSearchLabel">Quick Vehicle Search</h5>
                    <p class="text-muted small mb-0">Search by plate, vehicle details or assigned customer.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column gap-3">
                @php
                    $queryLength = \Illuminate\Support\Str::length($query ?? '');
                @endphp
                <div @class(['search-input-wrapper', 'has-query' => $queryLength]) role="search">
                    <i class="bx bx-car" aria-hidden="true"></i>
                    <label class="visually-hidden" for="quickSearchInput">Search vehicles</label>
                    <input type="search" wire:model.live.debounce.400ms="query" class="form-control"
                        placeholder="Start typing to search the fleet" aria-label="Search vehicles" autocomplete="off"
                        spellcheck="false" enterkeyhint="search" id="quickSearchInput">
                    @if ($queryLength > 1)
                        <button type="button" class="btn btn-sm btn-link text-muted" wire:click="$set('query', '')">
                            Clear
                        </button>
                    @endif
                </div>

                <div @class(['search-results', 'flex-grow-1', 'position-relative']) role="list" wire:loading.class="is-loading" wire:target="query"
                    aria-live="polite" wire:loading.attr="aria-busy" data-testid="quick-search-results">
                    <div class="search-loading" wire:loading.flex wire:target="query" aria-hidden="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                    </div>
                    @if ($queryLength <= 1)
                        <div class="search-placeholder text-center text-muted py-4" role="status">
                            <i class="bx bx-car display-6 d-block mb-2"></i>
                            <p class="mb-0">Enter at least 2 characters to search vehicles.</p>
                        </div>
                    @else
                        @forelse ($cars as $car)
                            @php
                                $status = $car->status ?? 'unknown';
                                $statusColor = match ($status) {
                                    'available' => 'success',
                                    'reserved' => 'warning',
                                    'unavailable', 'sold', 'maintenance' => 'danger',
                                    default => 'secondary',
                                };
                                $statusIcon = match ($status) {
                                    'available' => 'bx bx-check-circle',
                                    'reserved' => 'bx bx-time-five',
                                    'unavailable', 'sold', 'maintenance' => 'bx bx-error',
                                    default => 'bx bx-car',
                                };
                                $statusLabel = \Illuminate\Support\Str::headline($status);
                                $contract = $car->currentContract ?? null;
                                $plate = $car->plate_number ?? 'Plate TBD';
                                $year = $car->manufacturing_year ?? 'Year —';
                                $mileage = $car->mileage !== null ? number_format($car->mileage) . ' km' : 'Mileage —';
                                $price = $car->price_per_day !== null ? number_format($car->price_per_day) . ' AED / day' : 'Rate —';
                            @endphp
                            <a href="{{ route('car.edit', $car->id) }}" class="result-card" role="listitem"
                                wire:key="search-result-{{ $car->id }}">
                                <div class="result-card-top">
                                    <div class="result-card-heading">
                                        <span class="vehicle-name">{{ $car->fullname() }}</span>
                                        <span class="vehicle-sub text-muted">{{ optional($car->carModel)->brand }}
                                            {{ optional($car->carModel)->model }}</span>
                                    </div>
                                    <span class="status-chip is-{{ $statusColor }}">
                                        <i class="bx {{ $statusIcon }}"></i>
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <div class="result-card-highlights">
                                    <div class="highlight-card">
                                        <span class="highlight-label">Plate</span>
                                        <span class="highlight-value"><i class="bx bx-id-card"></i>{{ $plate }}</span>
                                    </div>
                                    <div class="highlight-card">
                                        <span class="highlight-label">Year</span>
                                        <span class="highlight-value"><i class="bx bx-calendar"></i>{{ $year }}</span>
                                    </div>
                                    <div class="highlight-card">
                                        <span class="highlight-label">Mileage</span>
                                        <span class="highlight-value"><i class="bx bx-road"></i>{{ $mileage }}</span>
                                    </div>
                                    <div class="highlight-card">
                                        <span class="highlight-label">Daily Rate</span>
                                        <span class="highlight-value"><i class="bx bx-dollar-circle"></i>{{ $price }}</span>
                                    </div>
                                </div>

                                @if ($status === 'reserved' && $contract)
                                    <div class="reservation-card">
                                        <div class="reservation-card-heading">
                                            <i class="bx bx-calendar-event"></i>
                                            <div>
                                                <span class="reservation-title">Active reservation</span>
                                                <span class="reservation-subtitle">{{ optional($contract->customer)->fullName() ?? 'Customer TBD' }}</span>
                                            </div>
                                        </div>
                                        <div class="reservation-grid">
                                            <div class="reservation-item">
                                                <span class="reservation-label">Pickup</span>
                                                <span class="reservation-value">
                                                    {{ optional($contract->pickup_date)->format('d M Y · H:i') ?? '—' }}
                                                </span>
                                                <span class="reservation-sub">{{ $contract->pickup_location ?? 'Location TBD' }}</span>
                                            </div>
                                            <div class="reservation-item">
                                                <span class="reservation-label">Return</span>
                                                <span class="reservation-value">
                                                    {{ optional($contract->return_date)->format('d M Y · H:i') ?? '—' }}
                                                </span>
                                                <span class="reservation-sub">{{ $contract->return_location ?? 'Location TBD' }}</span>
                                            </div>
                                            <div class="reservation-item">
                                                <span class="reservation-label">Contact</span>
                                                <span class="reservation-value">{{ optional($contract->customer)->phone ?? '—' }}</span>
                                                <span class="reservation-sub">Customer</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="result-card-footer">
                                    <div class="result-card-footer-meta">
                                        <span class="meta-label">Fleet manager</span>
                                        <span class="meta-value"><i class="bx bx-user-pin"></i>{{ optional($car->user)->shortName() ?? 'Unassigned' }}</span>
                                    </div>
                                    <div class="result-card-footer-meta">
                                        <span class="meta-label">Last update</span>
                                        <span class="meta-value"><i class="bx bx-time-five"></i>{{ $car->updated_at?->diffForHumans() ?? '—' }}</span>
                                    </div>
                                    <span class="result-card-arrow"><i class="bx bx-chevron-right"></i></span>
                                </div>
                            </a>
                        @empty
                            <div class="search-placeholder text-center text-muted py-4" role="status">
                                <i class="bx bx-car display-6 d-block mb-2"></i>
                                <p class="mb-0">No vehicles matched "{{ $query }}"</p>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
        <div class="offcanvas offcanvas-end search-offcanvas" tabindex="-1" id="agreementSearchOffcanvas"
            wire:ignore.self aria-labelledby="agreementSearchLabel" data-bs-scroll="true">
            <div class="offcanvas-header border-bottom">
                <div>
                    <h5 class="offcanvas-title mb-1" id="agreementSearchLabel">Agreement Lookup</h5>
                    <p class="text-muted small mb-0">Search contracts by agreement number to jump straight to the
                        details.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column gap-3">
                @php
                    $agreementQueryLength = \Illuminate\Support\Str::length($agreementQuery ?? '');
                @endphp
                <div @class(['search-input-wrapper', 'has-query' => $agreementQueryLength]) role="search">
                    <i class="bx bx-file"></i>
                    <label class="visually-hidden" for="agreementSearchInput">Search agreements</label>
                    <input type="search" wire:model.live.debounce.400ms="agreementQuery" class="form-control"
                        placeholder="Enter agreement number" aria-label="Search agreements" autocomplete="off"
                        spellcheck="false" enterkeyhint="search" id="agreementSearchInput">
                    @if ($agreementQueryLength > 1)
                        <button type="button" class="btn btn-sm btn-link text-muted"
                            wire:click="resetAgreementSearch">
                            Clear
                        </button>
                    @endif
                </div>

                <div @class(['search-results', 'flex-grow-1', 'position-relative']) role="list"
                    wire:loading.class="is-loading" wire:target="agreementQuery" aria-live="polite"
                    wire:loading.attr="aria-busy" data-testid="agreement-search-results">
                    <div class="search-loading" wire:loading.flex wire:target="agreementQuery" aria-hidden="true">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading…</span>
                        </div>
                    </div>
                    @if ($agreementQueryLength <= 1)
                        <div class="search-placeholder text-center text-muted py-4" role="status">
                            <i class="bx bx-file-find display-6 d-block mb-2"></i>
                            <p class="mb-0">Enter at least 2 digits to search agreements.</p>
                        </div>
                    @else
                        @forelse ($agreementResults as $contract)
                            @php
                                $agreementNumber = optional($contract->pickupDocument)->agreement_number;
                                $status = $contract->current_status ?? 'unknown';
                                $statusColor = match ($status) {
                                    'pending', 'under_review' => 'warning',
                                    'assigned', 'delivery' => 'primary',
                                    'agreement_inspection', 'awaiting_return' => 'info',
                                    'complete', 'completed' => 'success',
                                    'cancelled', 'canceled' => 'danger',
                                    default => 'secondary',
                                };
                                $statusLabel = \Illuminate\Support\Str::headline($status);
                                $car = $contract->car;
                                $customer = $contract->customer;
                                $pickupDate = optional($contract->pickup_date)->format('d M Y · H:i');
                                $returnDate = optional($contract->return_date)->format('d M Y · H:i');
                            @endphp
                            <a href="{{ route('rental-requests.details', [$contract->id]) }}" class="result-card"
                                role="listitem" wire:key="agreement-result-{{ $contract->id }}-{{ $agreementNumber }}">
                                <div class="result-card-header">
                                    <div class="result-card-title">
                                        <span class="result-card-name">Agreement #{{ $agreementNumber ?? '—' }}</span>
                                        <span class="result-card-sub text-muted">{{ optional($car)->fullName() ?? 'Vehicle TBD' }}</span>
                                    </div>
                                    <span class="status-chip bg-{{ $statusColor }}">{{ $statusLabel }}</span>
                                </div>

                                <div class="result-card-meta">
                                    <span><i class="bx bx-user-circle"></i>{{ optional($customer)->fullName() ?? 'Customer TBD' }}</span>
                                    <span><i class="bx bx-phone"></i>{{ optional($customer)->phone ?? '—' }}</span>
                                    <span><i class="bx bx-id-card"></i>{{ optional($car)->plate_number ?? 'Plate —' }}</span>
                                </div>

                                <div class="result-card-timeline">
                                    <div class="timeline-node">
                                        <div class="timeline-title">Pickup</div>
                                        <div class="timeline-value">{{ $pickupDate ?? 'Pickup —' }}</div>
                                        <div class="timeline-sub text-muted">{{ $contract->pickup_location ?? 'Location TBD' }}</div>
                                    </div>
                                    <div class="timeline-node">
                                        <div class="timeline-title">Return</div>
                                        <div class="timeline-value">{{ $returnDate ?? 'Return —' }}</div>
                                        <div class="timeline-sub text-muted">{{ $contract->return_location ?? 'Location TBD' }}</div>
                                    </div>
                                    <div class="timeline-node">
                                        <div class="timeline-title">Agreement</div>
                                        <div class="timeline-value">#{{ $agreementNumber ?? '—' }}</div>
                                        <div class="timeline-sub text-muted">{{ $statusLabel }}</div>
                                    </div>
                                </div>

                                <div class="result-card-footer">
                                    <span><i class="bx bx-time-five me-1"></i>Updated
                                        {{ $contract->updated_at?->diffForHumans() ?? '—' }}</span>
                                    <span class="result-card-arrow"><i class="bx bx-chevron-right"></i></span>
                                </div>
                            </a>
                            <hr>
                        @empty
                            <div class="search-placeholder text-center text-muted py-4" role="status">
                                <i class="bx bx-file-find display-6 d-block mb-2"></i>
                                <p class="mb-0">No agreements matched "{{ $agreementQuery }}"</p>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>
