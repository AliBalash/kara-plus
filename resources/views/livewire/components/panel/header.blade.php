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
                            <i class="bx bx-search"></i>
                        </button>
                    </li>
                @endif
                <!-- Place this tag where you want the button to render. -->
                <!-- User -->
                <li class="nav-item navbar-dropdown dropdown-user dropdown">
                    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <div class="avatar avatar-online">
                            <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('assets/panel/assets/img/avatars/unknow.jpg') }}"
                                alt="User Avatar" class="w-px-40 h-auto rounded-circle" />

                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.me') }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-online">
                                            <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('assets/panel/assets/img/avatars/unknow.jpg') }}"
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
                    <i class="bx bx-search" aria-hidden="true"></i>
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
                            <i class="bx bx-search-alt-2 display-6 d-block mb-2"></i>
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
                                $contract = $car->currentContract ?? null;
                            @endphp
                            <a href="{{ route('car.edit', $car->id) }}" class="result-card" role="listitem"
                                wire:key="search-result-{{ $car->id }}">
                                <div class="result-card-header">
                                    <div class="result-card-title">
                                        <span class="result-card-name">{{ $car->fullname() }}</span>
                                        <span class="result-card-sub text-muted">{{ optional($car->carModel)->brand }}
                                            {{ optional($car->carModel)->model }}</span>
                                    </div>
                                    <span class="status-chip bg-{{ $statusColor }}">{{ ucfirst($status) }}</span>
                                </div>

                                <div class="result-card-meta">
                                    <span><i class="bx bx-id-card"></i>{{ $car->plate_number ?? 'Plate TBD' }}</span>
                                    <span><i
                                            class="bx bx-calendar"></i>{{ $car->manufacturing_year ?? 'Year —' }}</span>
                                    <span><i class="bx bx-gas-pump"></i>{{ number_format($car->mileage ?? 0) }}
                                        km</span>
                                    <span><i class="bx bx-dollar-circle"></i>{{ number_format($car->price_per_day) }}
                                        AED/day</span>
                                </div>

                                @if ($status === 'reserved' && $contract)
                                    <div class="result-card-timeline">
                                        <div class="timeline-node">
                                            <div class="timeline-title">Pickup</div>
                                            <div class="timeline-value">
                                                {{ optional($contract->pickup_date)->format('d M Y · H:i') ?? '-' }}
                                            </div>
                                            <div class="timeline-sub text-muted">
                                                {{ $contract->pickup_location ?? 'Location TBD' }}</div>
                                        </div>
                                        <div class="timeline-node">
                                            <div class="timeline-title">Return</div>
                                            <div class="timeline-value">
                                                {{ optional($contract->return_date)->format('d M Y · H:i') ?? '-' }}
                                            </div>
                                            <div class="timeline-sub text-muted">
                                                {{ $contract->return_location ?? 'Location TBD' }}</div>
                                        </div>
                                        <div class="timeline-node">
                                            <div class="timeline-title">Customer</div>
                                            <div class="timeline-value">
                                                {{ optional($contract->customer)->fullName() ?? 'Customer TBD' }}</div>
                                            <div class="timeline-sub text-muted">
                                                {{ optional($contract->customer)->phone ?? '—' }}</div>
                                        </div>
                                    </div>
                                @endif

                                <div class="result-card-footer">
                                    <span><i
                                            class="bx bx-user-pin me-1"></i>{{ optional($car->user)->shortName() ?? 'Unassigned' }}</span>
                                    <span><i class="bx bx-time-five me-1"></i>Updated
                                        {{ $car->updated_at?->diffForHumans() ?? '—' }}</span>
                                    <span class="result-card-arrow"><i class="bx bx-chevron-right"></i></span>
                                </div>
                            </a>
                            <hr>
                        @empty
                            <div class="search-placeholder text-center text-muted py-4" role="status">
                                <i class="bx bx-search display-6 d-block mb-2"></i>
                                <p class="mb-0">No vehicles matched "{{ $query }}"</p>
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>

@once
    @push('styles')
        <style>
            .search-offcanvas {
                width: min(420px, 100vw);
            }

            .search-offcanvas .offcanvas-body {
                padding: 1.75rem 1.5rem;
            }

            .search-input-wrapper {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                border: 1px solid rgba(133, 146, 163, 0.25);
                border-radius: 999px;
                padding: 0.55rem 1rem;
                background: rgba(246, 248, 252, 0.95);
                box-shadow: inset 0 1px 2px rgba(33, 56, 86, 0.08);
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            }

            .search-input-wrapper:focus-within {
                border-color: rgba(105, 108, 255, 0.45);
                box-shadow: 0 0 0 0.25rem rgba(105, 108, 255, 0.08);
                background: #fff;
            }

            .search-input-wrapper.has-query {
                background: rgba(255, 255, 255, 0.95);
                border-color: rgba(133, 146, 163, 0.35);
            }

            .search-input-wrapper i {
                color: #6f7f92;
                font-size: 1.1rem;
            }

            .search-input-wrapper .form-control {
                border: none;
                background: transparent;
                box-shadow: none !important;
                padding: 0;
            }

            .search-input-wrapper .form-control::placeholder {
                color: #94a3b8;
            }

            .search-input-wrapper .form-control:focus {
                outline: none;
            }

            .search-input-wrapper .btn-link {
                text-decoration: none;
                font-size: 0.85rem;
                color: #6f7f92;
                padding: 0;
            }

            .search-input-wrapper .btn-link:hover,
            .search-input-wrapper .btn-link:focus-visible {
                color: #556981;
                text-decoration: underline;
            }

            .search-results {
                position: relative;
                overflow-y: auto;
                border-radius: 1rem;
                border: 1px solid rgba(133, 146, 163, 0.15);
                background: #fff;
                box-shadow: inset 0 1px 2px rgba(17, 38, 68, 0.05);
                padding-block: 0.35rem;
                scrollbar-width: thin;
                scrollbar-color: rgba(133, 146, 163, 0.3) transparent;
                max-height: min(520px, calc(100vh - 220px));
                min-height: 14rem;
            }

            .search-results::-webkit-scrollbar {
                width: 8px;
            }

            .search-results::-webkit-scrollbar-track {
                background: transparent;
            }

            .search-results::-webkit-scrollbar-thumb {
                background-color: rgba(133, 146, 163, 0.35);
                border-radius: 999px;
            }

            .search-results.is-loading {
                filter: saturate(0.85);
            }

            .search-loading {
                position: absolute;
                inset: 0;
                background: rgba(255, 255, 255, 0.88);
                border-radius: 1rem;
                display: none;
                justify-content: center;
                align-items: center;
                z-index: 2;
                backdrop-filter: blur(2px);
            }

            .result-card {
                display: block;
                padding: 1rem 1.2rem;
                border-bottom: 1px solid rgba(133, 146, 163, 0.12);
                text-decoration: none;
                color: inherit;
                background: #fff;
                transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
                border-radius: 1rem;
                margin: 0.4rem 0.4rem;
            }

            .result-card:last-child {
                border-bottom: none;
            }

            .result-card:hover,
            .result-card:focus-visible {
                transform: translateX(4px);
                box-shadow: 0 12px 26px rgba(33, 56, 86, 0.18);
            }

            .result-card:focus-visible {
                outline: none;
            }

            .result-card-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
            }

            .result-card-title {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .result-card-name {
                font-weight: 600;
                font-size: 1rem;
                color: #1f2a37;
            }

            .result-card-sub {
                font-size: 0.82rem;
            }

            .status-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.75rem;
                border-radius: 999px;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #fff;
            }

            .status-chip.bg-warning,
            .status-chip.bg-secondary {
                color: #223145;
            }

            .result-card-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.6rem;
                margin: 0.9rem 0 0.4rem;
                font-size: 0.78rem;
                color: #6f7f92;
            }

            .result-card-meta span {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.25rem 0.55rem;
                border-radius: 0.75rem;
                background: rgba(133, 146, 163, 0.12);
                line-height: 1.2;
            }

            .result-card-meta span i {
                color: #5c6f84;
            }

            .result-card-timeline {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 0.75rem;
                background: rgba(33, 56, 86, 0.04);
                padding: 0.75rem 0.9rem;
                border-radius: 0.85rem;
                margin-bottom: 0.75rem;
            }

            .timeline-node {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
            }

            .timeline-title {
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6f7f92;
                font-weight: 600;
            }

            .timeline-value {
                font-weight: 600;
                color: #223145;
                font-size: 0.85rem;
            }

            .timeline-sub {
                font-size: 0.75rem;
            }

            .result-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.78rem;
                color: #6f7f92;
            }

            .result-card-arrow {
                color: rgba(33, 56, 86, 0.45);
                font-size: 1.2rem;
                transition: transform 0.18s ease, color 0.18s ease;
            }

            .result-card:hover .result-card-arrow,
            .result-card:focus-visible .result-card-arrow {
                transform: translateX(4px);
                color: #223145;
            }

            .search-placeholder {
                color: #6f7f92;
            }

            @media (max-height: 720px) {
                .search-results {
                    max-height: calc(100vh - 180px);
                }
            }

            @media (max-width: 767.98px) {
                .search-offcanvas .offcanvas-body {
                    padding: 1.5rem 1.25rem;
                }

                .result-card {
                    margin: 0.3rem 0.3rem;
                }

                .result-card-meta {
                    gap: 0.45rem;
                }
            }

            @media (max-width: 575.98px) {
                .search-offcanvas {
                    width: 100vw;
                }

                .search-results {
                    max-height: calc(100vh - 170px);
                }
            }

            @media (prefers-reduced-motion: reduce) {

                .search-input-wrapper,
                .search-results,
                .result-card,
                .result-card-arrow {
                    transition: none !important;
                }
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            (() => {
                const MENU_KEY = 'layout-menu';
                const RESIZE_FLAG = '__panelMenuResizeBound';

                const getToggles = () => document.querySelectorAll(`[data-menu-toggle="${MENU_KEY}"]`);
                const getOverlay = () => document.querySelector('.layout-overlay.layout-menu-toggle');
                const getMenu = () => document.getElementById(MENU_KEY);

                const updateState = () => {
                    const menu = getMenu();
                    if (!menu) {
                        return;
                    }

                    const expanded = document.documentElement.classList.contains('layout-menu-expanded');
                    const isSmallScreen = window.matchMedia('(max-width: 1199.98px)').matches;
                    const ariaExpanded = expanded ? 'true' : 'false';

                    getToggles().forEach((toggle) => {
                        toggle.setAttribute('aria-expanded', ariaExpanded);
                    });

                    if (isSmallScreen) {
                        menu.setAttribute('aria-hidden', expanded ? 'false' : 'true');
                    } else {
                        menu.removeAttribute('aria-hidden');
                    }
                };

                const scheduleUpdate = () => window.requestAnimationFrame(updateState);

                const bind = () => {
                    const toggles = getToggles();
                    const overlay = getOverlay();

                    if (!toggles.length) {
                        return;
                    }

                    toggles.forEach((toggle) => {
                        if (toggle.dataset.menuToggleBound === 'true') {
                            return;
                        }

                        toggle.addEventListener('click', scheduleUpdate);
                        toggle.dataset.menuToggleBound = 'true';
                    });

                    if (overlay && overlay.dataset.menuToggleBound !== 'true') {
                        overlay.addEventListener('click', scheduleUpdate);
                        overlay.dataset.menuToggleBound = 'true';
                    }

                    if (!window[RESIZE_FLAG]) {
                        window.addEventListener('resize', scheduleUpdate);
                        window[RESIZE_FLAG] = true;
                    }

                    updateState();
                };

                if (document.readyState !== 'loading') {
                    bind();
                } else {
                    document.addEventListener('DOMContentLoaded', bind, {
                        once: true
                    });
                }

                window.addEventListener('livewire:navigated', bind);
            })
            ();

            document.addEventListener('shown.bs.offcanvas', (event) => {
                if (event.target && event.target.id === 'quickSearchOffcanvas') {
                    const input = event.target.querySelector('#quickSearchInput');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }
            });
        </script>
    @endpush
@endonce
