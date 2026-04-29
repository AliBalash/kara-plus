@php
    $items = [
        ['route' => 'reports.customer-requests', 'label' => 'Customer Requests', 'icon' => 'bx bx-folder-open'],
        ['route' => 'reports.customer-balances', 'label' => 'Customer Balances', 'icon' => 'bx bx-wallet-alt'],
        ['route' => 'reports.fleet-performance', 'label' => 'Fleet Performance', 'icon' => 'bx bx-car'],
        ['route' => 'reports.payment-collections', 'label' => 'Payment Collections', 'icon' => 'bx bx-receipt'],
    ];
@endphp

<div class="card report-nav-card mb-4">
    <div class="card-body p-2">
        <div class="nav nav-pills report-nav-grid">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                    class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                    <i class="{{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
