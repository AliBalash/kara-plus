@php
    $isDriver = auth()->user()?->hasRole('driver');
@endphp

<ul class="nav nav-pills flex-column flex-md-row mb-3" wire:ignore>
    {{-- Rental Information --}}
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('rental-requests.edit') ? 'active' : '' }}"
            href="{{ isset($contract->id) ? route('rental-requests.edit', $contract->id) : '#' }}">
            <i class="bx bxs-info-square me-1"></i> Rental Information
        </a>
    </li>

    @if (isset($contract->customer))
        @if ($isDriver)
            {{-- Pickup Document --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.pickup-document') ? 'active' : '' }}"
                    href="{{ route('rental-requests.pickup-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-upload me-1"></i> Pickup Document
                    @if ($pickupDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Return Document --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.return-document') ? 'active' : '' }}"
                    href="{{ route('rental-requests.return-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-download me-1"></i> Return Document
                    @if ($returnDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>
        @else
            {{-- Customer Document --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('customer.documents') ? 'active' : '' }}"
                    href="{{ route('customer.documents', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-file me-1"></i> Customer Document
                    @if ($customerDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Pickup Document --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.pickup-document') ? 'active' : '' }}"
                    href="{{ route('rental-requests.pickup-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-upload me-1"></i> Pickup Document
                    @if ($pickupDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Return Document --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.return-document') ? 'active' : '' }}"
                    href="{{ route('rental-requests.return-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-download me-1"></i> Return Document
                    @if ($returnDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Payment --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.payment') ? 'active' : '' }}"
                    href="{{ route('rental-requests.payment', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-money me-1"></i> Payment
                    @if ($paymentsExist ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Status / History --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('rental-requests.history') ? 'active' : '' }}"
                    href="{{ route('rental-requests.history', $contract->id) }}">
                    <i class="bx bx-history me-1"></i> Status & History
                </a>
            </li>
        @endif
    @endif
</ul>
