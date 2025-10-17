<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" role="navigation" aria-label="Main menu"
    tabindex="-1">
    <div class="app-brand demo d-flex align-items-center">
        <a href="{{ route('expert.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img width="120" src="{{ asset('assets/panel/assets/img/logo/logo.png') }}" alt="logo">
            </span>
            {{-- <span class="app-brand-text demo menu-text fw-bolder ms-2">Sneat</span> --}}
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none"
            aria-label="Close menu" aria-controls="layout-menu" data-menu-toggle="layout-menu">
            <i class="bx bx-x bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @php
            $isDriver = auth()->user()?->hasRole('driver');
        @endphp

        @if ($isDriver)
            <li class="menu-item {{ Request::routeIs('expert.dashboard') ? 'active' : '' }}">
                <a href="{{ route('expert.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Dashboard</div>
                </a>
            </li>

            <li class="menu-item {{ Request::routeIs('rental-requests.awaiting.pickup') ? 'active' : '' }}">
                <a href="{{ route('rental-requests.awaiting.pickup') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-log-in"></i>
                    <div data-i18n="Analytics">Awaiting Delivery</div>
                </a>
            </li>

            <li class="menu-item {{ Request::routeIs('rental-requests.awaiting.return') ? 'active' : '' }}">
                <a href="{{ route('rental-requests.awaiting.return') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-log-out"></i>
                    <div data-i18n="Analytics">Awaiting Return</div>
                </a>
            </li>
        @else
            <!-- Dashboard -->
            <li class="menu-item {{ Request::routeIs('expert.dashboard') ? 'active' : '' }}">
                <a href="{{ route('expert.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Dashboard</div>
                </a>
            </li>

            @cannot('car')
                <!-- Rental Request -->
                <li class="menu-item {{ request()->routeIs('rental-requests.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-car"></i>
                        <div data-i18n="Layouts">Rental Requests</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item {{ Request::routeIs('rental-requests.creat') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.creat') }}" class="menu-link">
                                <div data-i18n="Without menu">Add</div>
                            </a>
                        </li>
                        <li class="menu-item {{ Request::routeIs('rental-requests.list') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.list') }}" class="menu-link">
                                <div data-i18n="Without menu">Reserve</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.reserved') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.reserved') }}" class="menu-link">
                                <div data-i18n="Without menu">Booking</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.awaiting.pickup') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.awaiting.pickup') }}" class="menu-link">
                                <div data-i18n="Without menu">Awaiting Delivery</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.inspection-list') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.inspection-list') }}" class="menu-link">
                                <div data-i18n="Without menu">Inspection Contracts</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.awaiting.return') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.awaiting.return') }}" class="menu-link">
                                <div data-i18n="Without menu">Awaiting Return</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.cancelled') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.cancelled') }}" class="menu-link">
                                <div data-i18n="Without menu">Cancelled</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.payment.list') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.payment.list') }}" class="menu-link">
                                <div data-i18n="Without menu">Payment</div>
                            </a>
                        </li>

                        <li
                            class="menu-item {{ Request::routeIs('rental-requests.confirm-payment-list') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.confirm-payment-list') }}" class="menu-link">
                                <div data-i18n="Without menu">Confirm Payments</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('rental-requests.me') ? 'active' : '' }}">
                            <a href="{{ route('rental-requests.me') }}" class="menu-link">
                                <div data-i18n="Without menu">Me</div>
                            </a>
                        </li>

                        <li class="menu-divider"></li>

                        <li class="menu-item {{ Request::routeIs('cashier.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('cashier.dashboard') }}" class="menu-link">
                                <div data-i18n="Without menu">Cashier</div>
                            </a>
                        </li>
                    </ul>
                </li>
            @endcannot

            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">transport</span>
            </li>

            <!-- Car -->
            <li class="menu-item {{ request()->routeIs('car.*') ? 'open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bxs-car-garage"></i>
                    <div data-i18n="Layouts">Cars</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item {{ Request::routeIs('car.create') ? 'active' : '' }}">
                        <a href="{{ route('car.create') }}" class="menu-link">
                            <div data-i18n="Without menu">Add</div>
                        </a>
                    </li>

                    <li class="menu-item {{ Request::routeIs('car.list') ? 'active' : '' }}">
                        <a href="{{ route('car.list') }}" class="menu-link">
                            <div data-i18n="Without menu">List</div>
                        </a>
                    </li>
                </ul>
            </li>

            @cannot('car')
                <!-- Brand -->
                <li class="menu-item {{ request()->routeIs('brand.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-factory"></i>
                        <div data-i18n="Layouts">Brand</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item {{ Request::routeIs('brand.form') ? 'active' : '' }}">
                            <a href="{{ route('brand.form') }}" class="menu-link">
                                <div data-i18n="Without menu">Add</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('brand.list') ? 'active' : '' }}">
                            <a href="{{ route('brand.list') }}" class="menu-link">
                                <div data-i18n="Without menu">List</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">RTA</span>
                </li>
                <!-- Insurance -->
                <li class="menu-item {{ request()->routeIs('insurance.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-car-crash"></i>
                        <div data-i18n="Layouts">Insurance</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item {{ Request::routeIs('insurance.list') ? 'active' : '' }}">
                            <a href="{{ route('insurance.list') }}" class="menu-link">
                                <div data-i18n="Without menu">List</div>
                            </a>
                        </li>

                        <li class="menu-item {{ Request::routeIs('insurance.form') ? 'active' : '' }}">
                            <a href="{{ route('insurance.form') }}" class="menu-link">
                                <div data-i18n="Without menu">Add</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Pages</span>
                </li>

                <!-- Customer -->
                <li class="menu-item {{ request()->routeIs('customer.*') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-body"></i>
                        <div data-i18n="Layouts">Customer</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item {{ Request::routeIs('customer.list') ? 'active' : '' }}">
                            <a href="{{ route('customer.list') }}" class="menu-link">
                                <div data-i18n="Without menu">List</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-item {{ Request::routeIs('discount.codes') ? 'active' : '' }}">
                    <a href="{{ route('discount.codes') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-discount"></i>
                        <div data-i18n="Analytics">Discount Codes</div>
                    </a>
                </li>

                @role('super-admin')
                    <li class="menu-item {{ Request::routeIs('users.roles') ? 'active' : '' }}">
                        <a href="{{ route('users.roles') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-check"></i>
                            <div data-i18n="Analytics">Users &amp; Roles</div>
                        </a>
                    </li>
                @endrole
            @endcannot
        @endif
    </ul>
</aside>
