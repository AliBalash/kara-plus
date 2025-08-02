<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center w-100 justify-content-between" id="navbar-collapse">

        <!-- تاریخ - چپ -->
        <div class="d-flex align-items-center">
            <i class="bx bx-calendar fs-4 lh-0"></i>
            <span class="ms-2">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
        </div>

        <!-- Search - Centered and Responsive -->
        <div class="position-absolute start-50 translate-middle-x w-100 px-3" style="max-width: 600px;">
            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                <span class="input-group-text bg-white border-0">
                    <i class="bx bx-search fs-4 lh-0"></i>
                </span>
                <input type="text" wire:model.live.debounce.2000ms="query" class="form-control border-0 shadow-none"
                    placeholder="Search cars..." aria-label="Search" />
            </div>

            @if (strlen($query) > 1)
                <ul class="list-group position-absolute mt-2 w-100 shadow-lg"
                    style="z-index: 1000; max-height: 350px; overflow-y: auto;
                backdrop-filter: blur(12px); background-color: rgba(255, 255, 255, 0.65); 
                border-radius: 1rem;">
                    @forelse ($cars as $car)
                        <a href="{{ route('car.edit', $car->id) }}" class="text-decoration-none text-dark">
                            <li class="list-group-item border-0 py-3 mb-1 shadow-sm"
                                style="background-color: rgba(255, 255, 255, 0.5); 
                               transition: background-color 0.2s ease, transform 0.2s ease;"
                                onmouseover="this.style.backgroundColor='rgba(255,255,255,0.8)'; this.style.transform='scale(1.01)'"
                                onmouseout="this.style.backgroundColor='rgba(255,255,255,0.5)'; this.style.transform='scale(1)'">

                                {{-- Top Row --}}
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold" style="font-size: 1.1rem;">
                                        {{ $car->fullname() }}
                                    </div>
                                    @php
                                        $status = $car->status ?? 'unknown';
                                        $color = match ($status) {
                                            'available' => 'success',
                                            'reserved' => 'warning',
                                            'unavailable', 'sold', 'maintenance' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp

                                    <span class="badge bg-{{ $color }}">
                                        {{ ucfirst($status) }}
                                    </span>

                                </div>


                                @if ($car->status === 'reserved')
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-end">
                                            <small class="text-muted m-1">
                                                <i class="bx bx-calendar-check"></i> Pickup:
                                                {{ optional($car->currentContract)->pickup_date
                                                    ? \Carbon\Carbon::parse($car->currentContract->pickup_date)->translatedFormat('d M Y')
                                                    : '-' }}
                                            </small>
                                            <small class="text-muted m-1">
                                                <i class="bx bx-calendar-minus"></i> Return:
                                                {{ optional($car->currentContract)->return_date
                                                    ? \Carbon\Carbon::parse($car->currentContract->return_date)->translatedFormat('d M Y')
                                                    : '-' }}
                                            </small>
                                        </div>
                                    </div>
                                @endif


                                {{-- Year --}}
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Year: {{ $car->manufacturing_year }}</small>
                                </div>

                                {{-- Price & Insurance --}}
                                <div class="mt-2 d-flex flex-wrap gap-2">
                                    <span class="badge bg-dark">
                                        {{ number_format($car->price_per_day) }} AED / Day
                                    </span>
                                    @php
                                        $insuranceExpired = $car->isInsuranceExpired();
                                    @endphp
                                    <span class="badge bg-{{ $insuranceExpired ? 'danger' : 'primary' }}">
                                        Insurance: {{ $insuranceExpired ? 'Expired' : 'Valid' }}
                                    </span>
                                </div>

                            </li>
                        </a>
                    @empty
                        <li class="list-group-item text-center text-muted">No cars matched your search.</li>
                    @endforelse
                </ul>
            @endif
        </div>



        <ul class="navbar-nav flex-row align-items-center">
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
