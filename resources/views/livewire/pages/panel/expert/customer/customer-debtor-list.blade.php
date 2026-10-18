<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <p class="text-muted text-uppercase small mb-1">Customer</p>
            <h4 class="fw-bold mb-1">Debt customers</h4>
            <span class="text-muted">Monitor overdue balances and jump into their requests</span>
        </div>
    </div>

    <form wire:submit="applyFilters" novalidate class="card card-body mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-4">
                <label for="debt-search" class="form-label">Search customers</label>
                <input id="debt-search" type="search" class="form-control" placeholder="Name, ID or phone"
                    wire:model="searchInput">
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="debt-status" class="form-label">Status</label>
                <select id="debt-status" class="form-select" wire:model.live="status">
                    <option value="all">All statuses</option>
                    <option value="overdue">Overdue</option>
                    <option value="open">Open</option>
                    <option value="credit">Credit</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="debt-date-field" class="form-label">Contract date basis</label>
                <select id="debt-date-field" class="form-select" wire:model="filterInputs.dateField">
                    <option value="pickup_date">Pickup date</option>
                    <option value="created_at">Created date</option>
                </select>
                @error('filterInputs.dateField') <div class="text-danger small" role="alert">{{ $message }}</div> @enderror
            </div>
            @foreach (['dateFrom' => 'From date', 'dateTo' => 'To date'] as $field => $label)
                <div class="col-12 col-md-6 col-xl-2">
                    <label for="debt-{{ $field }}" class="form-label">{{ $label }}</label>
                    <input id="debt-{{ $field }}" type="date" class="form-control w-100" style="min-width: 0"
                        wire:model="filterInputs.{{ $field }}">
                    @error('filterInputs.'.$field) <div class="text-danger small" role="alert">{{ $message }}</div> @enderror
                </div>
            @endforeach
            @foreach (['minOutstanding' => 'Minimum outstanding (AED)', 'maxOutstanding' => 'Maximum outstanding (AED)'] as $field => $label)
                <div class="col-12 col-md-6 col-xl-4">
                    <label for="debt-{{ $field }}" class="form-label">{{ $label }}</label>
                    <input id="debt-{{ $field }}" type="number" step="0.01" min="0" class="form-control"
                        wire:model="filterInputs.{{ $field }}" placeholder="No limit">
                    @error('filterInputs.'.$field) <div class="text-danger small" role="alert">{{ $message }}</div> @enderror
                </div>
            @endforeach
            <div class="col-12 col-xl-4 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Apply Filters</button>
                <button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">Reset Filters</button>
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Current operational balances for contracts selected by date; this is not a historical balance snapshot.</p>
        <div class="small mt-2" aria-live="polite">
            Applied: {{ $search !== '' ? 'Search: '.$search.' · ' : '' }}Status: {{ $status === 'all' ? 'All' : ucfirst($status) }}
            · {{ $dateField === 'created_at' ? 'Created date' : 'Pickup date' }}:
            {{ $dateFrom ?: 'Any start' }} – {{ $dateTo ?: 'Any end' }}
            · Outstanding: {{ $minOutstanding !== '' ? number_format((float) $minOutstanding, 2) : 'No minimum' }}
            – {{ $maxOutstanding !== '' ? number_format((float) $maxOutstanding, 2).' AED' : 'No maximum' }}
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-3">
            <div class="debt-metric-card">
                <p class="text-muted text-uppercase small mb-1">Outstanding debt</p>
                <h3 class="mb-0">{{ number_format($overview['total_debt'], 2) }} AED</h3>
                <span class="badge bg-label-danger mt-2">{{ $overview['customers'] }} customers</span>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="debt-metric-card">
                <p class="text-muted text-uppercase small mb-1">Overdue</p>
                <h4 class="mb-0">{{ $overview['overdue'] }} requests</h4>
                <span class="text-danger small">Requires immediate action</span>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="debt-metric-card">
                <p class="text-muted text-uppercase small mb-1">Open</p>
                <h4 class="mb-0">{{ $overview['open'] }} requests</h4>
                <span class="text-warning small">In progress</span>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="debt-metric-card">
                <p class="text-muted text-uppercase small mb-1">Credit</p>
                <h4 class="mb-0">{{ number_format($overview['credit'], 2) }} AED</h4>
                <span class="text-success small">Prepaid or refundable</span>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-danger rounded-pill">Overdue</span>
                    <span class="badge bg-label-warning text-dark rounded-pill">Open</span>
                    <span class="badge bg-label-info text-dark rounded-pill">Credit</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-center">Requests</th>
                        <th>Key request</th>
                        <th class="text-end">Last activity</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($debtors as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['name'] }}</div>
                                <div class="text-muted small">{{ $row['phone'] ?? 'No phone on file' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $row['status']['class'] }}">{{ $row['status']['label'] }}</span>
                            </td>
                            <td class="text-end">
                                <div class="fw-semibold text-danger">{{ number_format($row['total_outstanding'], 2) }} AED</div>
                                @if ($row['credit'] > 0)
                                    <span class="badge bg-label-success mt-1">+{{ number_format($row['credit'], 2) }} credit</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="fw-semibold">{{ $row['open_requests'] + $row['overdue_requests'] }}</div>
                                <span class="text-muted small">{{ $row['overdue_requests'] }} overdue</span>
                            </td>
                            <td>
                                @if ($row['primary_contract'])
                                    <div class="fw-semibold d-flex align-items-center gap-2">
                                        <span class="badge {{ $row['primary_contract']['risk']['class'] }}">
                                            {{ $row['primary_contract']['risk']['label'] }}
                                        </span>
                                        <span>{{ $row['primary_contract']['label'] }}</span>
                                    </div>
                                    <div class="text-muted small">
                                        {{ $row['primary_contract']['car'] }} · {{ $row['primary_contract']['pickup_date'] ?? '—' }}
                                    </div>
                                @else
                                    <span class="text-muted">No requests</span>
                                @endif
                            </td>
                            <td class="text-end text-muted small">{{ $row['last_activity'] ?? '—' }}</td>
                            <td class="text-end">
                                <div class="btn-group" role="group" aria-label="Actions">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('customer.debt', $row['customer_id']) }}">
                                        Debt
                                    </a>
                                    <a class="btn btn-sm btn-primary" href="{{ route('customer.history', $row['customer_id']) }}">
                                        View requests
                                    </a>
                                    @if ($row['primary_contract'])
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ route('rental-requests.details', $row['primary_contract']['id']) }}">
                                            Contract
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bx bx-check-circle d-block mb-2 fs-1"></i>
                                No customers match the applied filters. Try widening the ranges or resetting filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">{{ $debtors->total() }} matching customers · Showing {{ $debtors->firstItem() ?? 0 }}–{{ $debtors->lastItem() ?? 0 }}</div>
            {{ $debtors->links() }}
        </div>
    </div>

    @push('styles')
        <style>
            .debt-metric-card {
                border: 1px solid rgba(105, 108, 255, 0.12);
                border-radius: 1rem;
                padding: 1.5rem;
                height: 100%;
                background: #fff;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            .debt-metric-card h3,
            .debt-metric-card h4 {
                font-weight: 700;
            }
        </style>
    @endpush
</div>
