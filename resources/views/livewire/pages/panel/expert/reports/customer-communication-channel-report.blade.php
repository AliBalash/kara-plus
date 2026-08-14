@php($summary = $report['summary'])

<div class="communication-page communication-page--customer">
    @include('livewire.pages.panel.expert.reports.partials.styles')

    <section class="card report-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div>
                    <span class="report-eyebrow"><i class="bx bx-user-voice"></i> Marketing / Customer Communication Channel</span>
                    <h3 class="mt-3 mb-2 text-white">Customer Communication Channel</h3>
                    <p class="report-subtitle">Measure which channels bring confirmed customers and bookings, with contract value and completion context.</p>
                </div>
                <a href="{{ $exportUrl }}" class="btn btn-light"><i class="bx bx-export me-1"></i> Export Excel</a>
            </div>
        </div>
    </section>

    <div class="communication-context">
        <div class="communication-context__icon"><i class="bx bx-user-check"></i></div>
        <div>
            <span class="communication-context__label">Confirmed customer bookings</span>
            <span class="communication-context__title">Channels recorded on reservations and contracts</span>
            <p class="communication-context__note">Measures booked customers, contract value, and completion by communication channel.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['Contracts', number_format($summary['matching_contracts']), 'Bookings in the current scope.'],
            ['Customers', number_format($summary['unique_customers']), 'Unique matched customers.'],
            ['Contract Value', number_format($summary['contract_value'], 0) . ' AED', 'Total booked value.'],
            ['Completed', number_format($summary['completed_contracts']), 'Completed customer contracts.'],
            ['Top Channel', $summary['top_channel'], 'Highest-volume customer channel.'],
        ] as [$label, $value, $note])
            <div class="col-md-6 col-xl">
                <div class="report-kpi card card-body"><span class="metric-label">{{ $label }}</span><div class="metric-value mt-2" @if ($label === 'Top Channel') style="font-size:1.1rem" @endif>{{ $value }}</div><p class="metric-note mt-2">{{ $note }}</p></div>
            </div>
        @endforeach
    </div>

    <section class="card report-filter-card mb-4"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3"><div><h5 class="mb-1">Filter customer channels</h5><p class="text-muted mb-0">Use the channel recorded on each reservation or contract.</p></div><button type="button" class="btn btn-outline-secondary" wire:click="clearFilters"><i class="bx bx-reset me-1"></i> Reset Filters</button></div>
        <div class="row g-3">
            <div class="col-lg-3"><label class="filter-label" for="customerChannelSearch">Search</label><input id="customerChannelSearch" type="search" class="form-control" placeholder="Customer, phone, contract, vehicle" wire:model.live.debounce.350ms="search"></div>
            <div class="col-lg-2"><label class="filter-label" for="customerChannelDateField">Date Basis</label><select id="customerChannelDateField" class="form-select" wire:model.live="dateField"><option value="created_at">Request Date</option><option value="pickup_date">Pickup Date</option><option value="return_date">Return Date</option></select></div>
            <div class="col-lg-2"><label class="filter-label" for="customerChannelDateFrom">Date From</label><input id="customerChannelDateFrom" type="date" class="form-control" wire:model.live="dateFrom"></div>
            <div class="col-lg-2"><label class="filter-label" for="customerChannelDateTo">Date To</label><input id="customerChannelDateTo" type="date" class="form-control" wire:model.live="dateTo"></div>
            <div class="col-lg-3"><label class="filter-label" for="customerChannel">Communication Channel</label><select id="customerChannel" class="form-select" wire:model.live="channel"><option value="all">All channels</option>@foreach ($channelOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-2"><label class="filter-label" for="customerChannelStatus">Contract Status</label><select id="customerChannelStatus" class="form-select" wire:model.live="status">@foreach ($statusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        </div>
    </div></section>

    <section class="card report-results-card"><div class="report-results-meta"><div><h5 class="mb-1">Customer channel detail</h5><p class="text-muted mb-0">{{ number_format($summary['matching_contracts']) }} contracts ready for review and export.</p></div><div class="report-filter-badges">@foreach ($report['filter_summary'] as $label => $value)<span class="badge">{{ $label }}: {{ $value }}</span>@endforeach</div></div>
        @if ($rows->count())
            <div class="table-responsive"><table class="table table-hover mb-0 report-table"><thead><tr><th>Contract</th><th>Customer</th><th>Communication Channel</th><th>Vehicle</th><th>Outcome</th></tr></thead><tbody>@foreach ($rows as $row)<tr><td><span class="cell-title">#{{ $row['contract_id'] }}</span><span class="cell-subtitle">{{ $row['selected_date_basis'] }}: {{ $row['selected_date'] }}</span><span class="cell-subtitle">Pickup: {{ $row['pickup_date'] }}</span></td><td><span class="cell-title">{{ $row['customer_name'] }}</span><span class="cell-subtitle">{{ $row['customer_phone'] }}</span></td><td><span class="cell-title">{{ $row['channel_label'] }}</span></td><td><span class="cell-title">{{ $row['vehicle'] }}</span><span class="cell-subtitle">Plate: {{ $row['plate_number'] }}</span></td><td><span class="badge bg-label-primary">{{ $row['status_label'] }}</span><span class="cell-subtitle mt-2">{{ number_format($row['total_price'], 2) }} AED</span><span class="cell-subtitle">Return: {{ $row['return_date'] }}</span></td></tr>@endforeach</tbody></table></div><div class="p-3">{{ $rows->links() }}</div>
        @else
            <div class="report-empty"><div><i class="bx bx-user-voice"></i><h5 class="mt-3 mb-2">No customer contracts found</h5><p class="mb-0">Broaden the date range or channel filter.</p></div></div>
        @endif
    </section>
</div>
