<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" role="navigation" aria-label="Main menu"
    tabindex="-1">
    <div class="app-brand demo d-flex align-items-center">
        <a href="{{ route('expert.dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img width="120" src="{{ asset('assets/panel/assets/img/logo/logo.png') }}" alt="logo"
                    decoding="async">
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
            $rentalLifecycle = [
                ['route' => 'rental-requests.creat', 'label' => 'Add'],
                ['route' => 'rental-requests.list', 'label' => 'Reserve'],
                ['route' => 'rental-requests.reserved', 'label' => 'Booking'],
                ['route' => 'rental-requests.awaiting.pickup', 'label' => 'Awaiting Delivery'],
                ['route' => 'rental-requests.awaiting.return', 'label' => 'Awaiting Return'],
                ['route' => 'rental-requests.cancelled', 'label' => 'Cancelled'],
                ['route' => 'rental-requests.me', 'label' => 'Me'],
            ];
            $inspectionRoutes = [
                ['route' => 'rental-requests.tars-inspection-list', 'label' => 'Inspection Contracts (TARS)'],
                ['route' => 'rental-requests.kardo-inspection-list', 'label' => 'Inspection Contracts (KARDO)'],
            ];
            $paymentRoutes = [
                ['route' => 'rental-requests.payment.list', 'label' => 'Payment'],
                ['route' => 'rental-requests.confirm-payment-list', 'label' => 'Confirm Payments'],
                ['route' => 'rental-requests.processed-payments', 'label' => 'Processed Payments'],
            ];
            $rentalPaymentRoutes = [
                'rental-requests.payment.list',
                'rental-requests.payment',
                'rental-requests.confirm-payment-list',
                'rental-requests.processed-payments',
            ];
            $rentalMenuOpen = request()->routeIs('rental-requests.*') && !request()->routeIs($rentalPaymentRoutes);
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
            <li class="menu-item {{ Request::routeIs('expert.dashboard') ? 'active' : '' }}">
                <a href="{{ route('expert.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-home-circle"></i>
                    <div data-i18n="Analytics">Dashboard</div>
                </a>
            </li>

            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Operations</span>
            </li>

            @cannot('car')
                <li class="menu-item {{ $rentalMenuOpen ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bxs-car"></i>
                        <div data-i18n="Layouts">Rental Requests</div>
                    </a>

                    <ul class="menu-sub">
                        <li class="menu-item pt-0 pb-1 px-3 text-uppercase text-muted fw-semibold small">Lifecycle</li>
                        @foreach ($rentalLifecycle as $item)
                            <li class="menu-item {{ Request::routeIs($item['route']) ? 'active' : '' }}">
                                <a href="{{ route($item['route']) }}" class="menu-link">
                                    <div data-i18n="Without menu">{{ $item['label'] }}</div>
                                </a>
                            </li>
                        @endforeach

                        <li class="menu-divider"></li>
                        <li class="menu-item pt-0 pb-1 px-3 text-uppercase text-muted fw-semibold small">Inspections</li>
                        @foreach ($inspectionRoutes as $item)
                            <li class="menu-item {{ Request::routeIs($item['route']) ? 'active' : '' }}">
                                <a href="{{ route($item['route']) }}" class="menu-link">
                                    <div data-i18n="Without menu">{{ $item['label'] }}</div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                <li
                    class="menu-item {{ request()->routeIs('rental-requests.payment.list', 'rental-requests.payment', 'rental-requests.confirm-payment-list', 'rental-requests.processed-payments', 'cashier.dashboard') ? 'open' : '' }}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons bx bx-credit-card"></i>
                        <div data-i18n="Layouts">Payments</div>
                    </a>

                    <ul class="menu-sub">
                        @foreach ($paymentRoutes as $item)
                            <li class="menu-item {{ Request::routeIs($item['route']) ? 'active' : '' }}">
                                <a href="{{ route($item['route']) }}" class="menu-link">
                                    <div data-i18n="Without menu">{{ $item['label'] }}</div>
                                </a>
                            </li>
                        @endforeach
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
                <span class="menu-header-text">Fleet &amp; Compliance</span>
            </li>

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
                    <span class="menu-header-text">Customers</span>
                </li>

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
                        <li class="menu-item {{ Request::routeIs('customer.debtor-list') ? 'active' : '' }}">
                            <a href="{{ route('customer.debtor-list') }}" class="menu-link">
                                <div data-i18n="Without menu">Debt customers</div>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text">Administration</span>
                </li>


                @role('super-admin')
                    <li class="menu-item {{ Request::routeIs('users.create') ? 'active' : '' }}">
                        <a href="{{ route('users.create') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-plus"></i>
                            <div data-i18n="Analytics">Create user</div>
                        </a>
                    </li>

                    <li class="menu-item {{ Request::routeIs('users.roles') ? 'active' : '' }}">
                        <a href="{{ route('users.roles') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-user-check"></i>
                            <div data-i18n="Analytics">Users &amp; Roles</div>
                        </a>
                    </li>
                @endrole


                <li class="menu-item {{ Request::routeIs('location-costs.index') ? 'active' : '' }}">
                    <a href="{{ route('location-costs.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-map"></i>
                        <div data-i18n="Analytics">Location Costs</div>
                    </a>
                </li>
                <li class="menu-item {{ Request::routeIs('agents.index') ? 'active' : '' }}">
                    <a href="{{ route('agents.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-id-card"></i>
                        <div data-i18n="Analytics">Sales Agents</div>
                    </a>
                </li>
            @endcannot

        @endif

        {{-- <li class="menu-item {{ Request::routeIs('discount.codes') ? 'active' : '' }}">
                    <a href="{{ route('discount.codes') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bxs-discount"></i>
                        <div data-i18n="Analytics">Discount Codes</div>
                    </a>
                </li> --}}
    </ul>


</aside>
