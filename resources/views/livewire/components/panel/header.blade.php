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
        {{-- Modern Quick Vehicle Search Offcanvas --}}
        <div class="offcanvas offcanvas-end search-offcanvas-modern" tabindex="-1" id="quickSearchOffcanvas"
            wire:ignore.self aria-labelledby="quickSearchLabel" data-bs-scroll="true" data-bs-backdrop="true">
            <div class="offcanvas-header border-bottom align-items-start gap-2">
                <div class="d-flex flex-column">
                    <h5 class="offcanvas-title mb-1 fw-semibold" id="quickSearchLabel">Quick Vehicle Search</h5>
                    <p class="text-muted small mb-0">Search by plate, vehicle details or assigned customer.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="shortcut-hint text-muted" title="Open with ⌘/Ctrl + K">
                        <kbd class="kbd">⌘</kbd><span class="mx-1">/</span><kbd class="kbd">Ctrl</kbd><span
                            class="mx-1">+</span><kbd class="kbd">K</kbd>
                    </span>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
            </div>

            @php($queryLength = \Illuminate\Support\Str::length($query ?? ''))

            <div class="offcanvas-body d-flex flex-column gap-3 position-relative">
                <div class="search-input-wrapper-modern @if ($queryLength) has-query @endif"
                    role="search">
                    <i class="bx bx-search" aria-hidden="true"></i>
                    <label class="visually-hidden" for="quickSearchInput">Search vehicles</label>
                    <input type="search" wire:model.live.debounce.350ms="query" class="form-control search-input"
                        placeholder="Start typing to search the fleet" aria-label="Search vehicles" autocomplete="off"
                        spellcheck="false" enterkeyhint="search" id="quickSearchInput">
                    @if ($queryLength > 1)
                        <button type="button" class="btn btn-sm btn-link text-muted px-2 ms-1 clear-btn"
                            wire:click="$set('query', '')" title="Clear">
                            Clear
                        </button>
                    @endif
                </div>

                <div class="search-results-modern flex-grow-1 position-relative" role="list"
                    wire:loading.class="is-loading" wire:target="query" aria-live="polite"
                    wire:loading.attr="aria-busy" data-testid="quick-search-results">

                    {{-- Loading State (skeletons) --}}
                    <div class="search-loading d-none" wire:loading.class.remove="d-none" wire:target="query"
                        aria-hidden="true">
                        <div class="skeleton-list">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="skeleton-card">
                                    <div class="skeleton-line w-50"></div>
                                    <div class="skeleton-line w-25"></div>
                                    <div class="skeleton-meta">
                                        <span class="skeleton-dot"></span>
                                        <span class="skeleton-dot"></span>
                                        <span class="skeleton-dot large"></span>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Placeholder before typing --}}
                    @if ($queryLength <= 1)
                        <div class="search-placeholder text-center text-muted py-5" role="status">
                            <i class="bx bx-search-alt-2 display-6 d-block mb-2"></i>
                            <p class="mb-1">Enter at least <strong>2</strong> characters to search vehicles.</p>
                            <p class="small mb-0">Tip: Press <kbd class="kbd">⌘</kbd>/<kbd
                                    class="kbd">Ctrl</kbd>+<kbd class="kbd">K</kbd> to open this panel.</p>
                        </div>
                    @else
                        {{-- Results --}}
                        <ul class="results-list list-unstyled m-0" id="quickSearchResultsList">
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
                                <li role="listitem" class="result-li">
                                    <a href="{{ route('car.edit', $car->id) }}" class="result-card-modern"
                                        wire:key="search-result-{{ $car->id }}"
                                        data-name="{{ $car->fullname() }}" data-plate="{{ $car->plate_number ?? '' }}"
                                        data-brand="{{ optional($car->carModel)->brand }}"
                                        data-model="{{ optional($car->carModel)->model }}">
                                        <div class="result-card-header">
                                            <div class="result-card-title">
                                                <span
                                                    class="result-card-name text-truncate">{{ $car->fullname() }}</span>
                                                <span
                                                    class="result-card-sub text-muted text-truncate">{{ optional($car->carModel)->brand }}
                                                    {{ optional($car->carModel)->model }}</span>
                                            </div>
                                            <span
                                                class="status-chip badge bg-{{ $statusColor }} text-wrap">{{ ucfirst($status) }}</span>
                                        </div>
                                        <div class="result-card-meta">
                                            <span title="Plate"><i
                                                    class="bx bx-id-card"></i>{{ $car->plate_number ?? 'Plate TBD' }}</span>
                                            <span title="Year"><i
                                                    class="bx bx-calendar"></i>{{ $car->manufacturing_year ?? 'Year —' }}</span>
                                            <span title="Mileage"><i
                                                    class="bx bx-gas-pump"></i>{{ number_format($car->mileage ?? 0) }}
                                                km</span>
                                            <span title="Rate"><i
                                                    class="bx bx-dollar-circle"></i>{{ number_format($car->price_per_day) }}
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
                                                        {{ optional($contract->customer)->fullName() ?? 'Customer TBD' }}
                                                    </div>
                                                    <div class="timeline-sub text-muted">
                                                        {{ optional($contract->customer)->phone ?? '—' }}</div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="result-card-footer">
                                            <span><i
                                                    class="bx bx-user-pin me-1"></i>{{ optional($car->user)->shortName() ?? 'Unassigned' }}</span>
                                            <span class="text-muted small"><i class="bx bx-time-five me-1"></i>Updated
                                                {{ $car->updated_at?->diffForHumans() ?? '—' }}</span>
                                            <span class="result-card-arrow" aria-hidden="true"><i
                                                    class="bx bx-chevron-right"></i></span>
                                        </div>
                                    </a>
                                </li>
                            @empty
                                <div class="search-placeholder text-center text-muted py-5" role="status">
                                    <i class="bx bx-search display-6 d-block mb-2"></i>
                                    <p class="mb-1">No vehicles matched "{{ $query }}"</p>
                                    <div class="mt-3">
                                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('car.create') }}">
                                            Add new vehicle
                                        </a>
                                    </div>
                                </div>
                            @endforelse
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

</div>

@once

    {{-- Styles: modern, accessible, dark-mode friendly --}}
    @push('styles')
        <style>
            :root {
                --qs-radius: 16px;
                --qs-shadow: 0 10px 30px rgba(20, 20, 20, .12);
                --qs-border: 1px solid rgba(120, 120, 120, .15);
                --qs-muted: #6b7280;
                /* gray-500 */
                --qs-card-bg: rgba(255, 255, 255, .7);
                --qs-blur: saturate(1.4) blur(6px);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --qs-card-bg: rgba(17, 24, 39, 0.6);
                    --qs-border: 1px solid rgba(255, 255, 255, .08);
                    --qs-shadow: 0 10px 30px rgba(0, 0, 0, .35);
                }
            }

            .search-offcanvas-modern {
                width: min(760px, 100vw);
            }

            .search-input-wrapper-modern {
                position: relative;
                display: flex;
                align-items: center;
                gap: .5rem;
                background: var(--qs-card-bg);
                backdrop-filter: var(--qs-blur);
                border: var(--qs-border);
                border-radius: var(--qs-radius);
                padding: .5rem .75rem;
                box-shadow: var(--qs-shadow);
            }

            .search-input-wrapper-modern i.bx {
                font-size: 1.25rem;
                color: var(--qs-muted);
            }

            .search-input-wrapper-modern .search-input {
                border: 0;
                background: transparent;
                box-shadow: none;
            }

            .search-input-wrapper-modern .search-input:focus {
                outline: none;
                box-shadow: none;
            }

            .search-input-wrapper-modern .clear-btn {
                text-decoration: none;
            }

            .search-results-modern {
                min-height: 320px;
            }

            .results-list {
                display: grid;
                grid-template-columns: 1fr;
                gap: .5rem;
            }

            .result-li {
                list-style: none;
            }

            .result-card-modern {
                display: block;
                text-decoration: none;
                color: inherit;
                padding: .9rem;
                border-radius: var(--qs-radius);
                background: var(--qs-card-bg);
                border: var(--qs-border);
                box-shadow: var(--qs-shadow);
                transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
            }

            .result-card-modern:focus-visible,
            .result-card-modern:hover {
                transform: translateY(-1px);
                box-shadow: 0 14px 34px rgba(0, 0, 0, .14);
                background: rgba(255, 255, 255, .9);
            }

            @media (prefers-color-scheme: dark) {
                .result-card-modern:hover {
                    background: rgba(31, 41, 55, .8);
                }
            }

            .result-card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
            }

            .result-card-title {
                min-width: 0;
                display: grid;
            }

            .result-card-name {
                font-weight: 600;
            }

            .result-card-sub {
                font-size: .875rem;
            }

            .status-chip {
                white-space: nowrap;
                align-self: start;
            }

            .result-card-meta {
                display: flex;
                flex-wrap: wrap;
                gap: .75rem;
                margin-top: .5rem;
                font-size: .9rem;
            }

            .result-card-meta span {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                color: var(--qs-muted);
            }

            .result-card-timeline {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: .75rem;
                margin-top: .75rem;
                padding: .75rem;
                border-radius: 12px;
                background: rgba(148, 163, 184, .12);
            }

            .timeline-title {
                font-size: .75rem;
                color: var(--qs-muted);
            }

            .timeline-value {
                font-weight: 600;
            }

            .timeline-sub {
                font-size: .8rem;
            }

            .result-card-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .75rem;
                margin-top: .6rem;
            }

            .result-card-arrow {
                opacity: .5;
            }

            /* Keyboard-focused state */
            .result-card-modern.is-active {
                outline: 2px solid var(--bs-primary);
                outline-offset: 2px;
            }

            /* Skeletons */
            .skeleton-list {
                display: grid;
                gap: .5rem;
            }

            .skeleton-card {
                padding: .9rem;
                border-radius: var(--qs-radius);
                border: var(--qs-border);
                background: var(--qs-card-bg);
            }

            .skeleton-line {
                height: .8rem;
                background: linear-gradient(90deg, rgba(0, 0, 0, .06), rgba(0, 0, 0, .12), rgba(0, 0, 0, .06));
                border-radius: 6px;
                animation: shimmer 1.4s infinite;
            }

            .skeleton-line+.skeleton-line {
                margin-top: .5rem;
            }

            .skeleton-meta {
                display: flex;
                gap: .5rem;
                margin-top: .7rem;
            }

            .skeleton-dot {
                width: 60px;
                height: .8rem;
                background: rgba(0, 0, 0, .08);
                border-radius: 999px;
            }

            .skeleton-dot.large {
                width: 100px;
            }

            @keyframes shimmer {
                0% {
                    background-position: -100px 0;
                }

                100% {
                    background-position: 100px 0;
                }
            }

            /* Minor RTL support */
            [dir="rtl"] .result-card-arrow i {
                transform: scaleX(-1);
            }

            .kbd {
                border: 1px solid rgba(0, 0, 0, .2);
                border-bottom-width: 2px;
                padding: 2px 6px;
                border-radius: 6px;
                font-size: .75rem;
            }
        </style>
    @endpush

    {{-- Scripts: shortcuts, keyboard navigation, client-side highlighting --}}
    @push('scripts')
        <script>
            (function() {
                const offcanvasId = 'quickSearchOffcanvas';
                let activeIndex = -1;

                function getCards() {
                    return Array.from(document.querySelectorAll('#' + offcanvasId + ' .result-card-modern'));
                }

                function focusCard(idx) {
                    const cards = getCards();
                    cards.forEach(c => c.classList.remove('is-active'));
                    if (idx >= 0 && idx < cards.length) {
                        cards[idx].classList.add('is-active');
                        cards[idx].scrollIntoView({
                            block: 'nearest'
                        });
                    }
                }

                function openOffcanvas() {
                    const el = document.getElementById(offcanvasId);
                    if (!el) return;
                    const bs = bootstrap.Offcanvas.getOrCreateInstance(el);
                    bs.show();
                    setTimeout(() => document.getElementById('quickSearchInput')?.focus(), 150);
                }

                // Global shortcut ⌘/Ctrl + K to open
                document.addEventListener('keydown', (e) => {
                    const mod = e.metaKey || e.ctrlKey;
                    if (mod && (e.key === 'k' || e.key === 'K')) {
                        e.preventDefault();
                        openOffcanvas();
                    }
                });

                // Keyboard navigation within offcanvas
                document.addEventListener('keydown', (e) => {
                    const canvasEl = document.getElementById(offcanvasId);
                    if (!canvasEl?.classList.contains('show')) return;

                    if (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(e.key)) {
                        const cards = getCards();
                        if (!cards.length) return;
                    }
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, getCards().length - 1);
                        focusCard(activeIndex);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                        focusCard(activeIndex);
                    } else if (e.key === 'Enter') {
                        const cards = getCards();
                        if (activeIndex >= 0 && cards[activeIndex]) {
                            e.preventDefault();
                            window.location.href = cards[activeIndex].getAttribute('href');
                        }
                    } else if (e.key === 'Escape') {
                        const bs = bootstrap.Offcanvas.getInstance(canvasEl);
                        bs?.hide();
                    }
                });

                // Lightweight highlighter for matches (name/brand/model/plate)
                function highlightMatches() {
                    const q = (document.getElementById('quickSearchInput')?.value || '').trim();
                    const norm = (s) => s?.toString().toLowerCase() || '';
                    const qn = norm(q);
                    document.querySelectorAll('#' + offcanvasId + ' .result-card-modern .result-card-title, #' +
                            offcanvasId + ' .result-card-modern .result-card-meta')
                        .forEach(section => {
                            section.querySelectorAll('mark.qs').forEach(m => {
                                const parent = m.parentNode;
                                if (!parent) return;
                                parent.replaceChild(document.createTextNode(m.textContent), m);
                            });
                        });
                    if (!q || q.length < 2) return;
                    document.querySelectorAll('#' + offcanvasId + ' .result-card-modern').forEach(card => {
                        ['.result-card-name', '.result-card-sub', '.result-card-meta span']
                        .forEach(sel => {
                            card.querySelectorAll(sel).forEach(node => {
                                const text = node.textContent;
                                const idx = norm(text).indexOf(qn);
                                if (idx >= 0) {
                                    const before = text.slice(0, idx);
                                    const match = text.slice(idx, idx + q.length);
                                    const after = text.slice(idx + q.length);
                                    node.innerHTML =
                                        `${before}<mark class="qs">${match}</mark>${after}`;
                                }
                            });
                        });
                    });
                }

                document.addEventListener('input', (e) => {
                    if ((e.target || {}).id === 'quickSearchInput') highlightMatches();
                });
                document.addEventListener('livewire:update', highlightMatches);
                document.addEventListener('shown.bs.offcanvas', highlightMatches);
            })
            ();
        </script>
    @endpush


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
