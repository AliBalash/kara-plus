<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Customer /</span> Debt</h4>

    <div class="row">
        <div class="col-12">
            <ul class="nav nav-pills flex-column flex-md-row mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.detail') ? 'active' : '' }}"
                        href="{{ route('customer.detail', $customer->id) }}">
                        <i class="bx bx-file me-1"></i> Contract Details
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.history') ? 'active' : '' }}"
                        href="{{ route('customer.history', $customer->id) }}"><i class="bx bx-history me-1"></i> History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.debt') ? 'active' : '' }}"
                        href="{{ route('customer.debt', $customer->id) }}">
                        <i class="bx bx-trending-down me-1"></i> Debt
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bx bx-paperclip me-1"></i> Attachments</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
            <div>
                <p class="text-muted text-uppercase small mb-1">Primary Profile</p>
                <h4 class="mb-1">{{ $customer->fullName() }}</h4>
                <div class="text-muted small">{{ $customer->phone ?? 'No phone on file' }}</div>
                <div class="text-muted small">Active contracts: {{ $customer->contracts->count() }}</div>
            </div>
            <div class="debt-gauge text-center">
                <p class="text-muted text-uppercase small mb-1">Debt Score</p>
                <div class="debt-score display-6 fw-bold">{{ $debtTotals['debt_score'] ?? 0 }}</div>
                <div class="text-muted small mb-2">out of 100</div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar bg-gradient-primary" role="progressbar"
                        style="width: {{ min($debtTotals['debt_score'] ?? 0, 100) }}%"></div>
                </div>
            </div>
            <div class="text-lg-end">
                <p class="text-muted text-uppercase small mb-1">Total Debt</p>
                <h3 class="text-danger mb-1">{{ number_format($debtTotals['total_outstanding'] ?? 0, 2) }} AED</h3>
                <div class="text-muted small">{{ $debtTotals['open_contracts'] ?? 0 }} open request(s)</div>
            </div>
        </div>
    </div>

    @php
        $statCards = [
            [
                'label' => 'Debt',
                'caption' => 'Outstanding exposure',
                'value' => number_format($debtTotals['total_outstanding'] ?? 0, 2) . ' AED',
                'accent' => 'accent-danger'
            ],
            [
                'label' => 'Overdue debt',
                'caption' => 'Needs urgent action',
                'value' => $debtTotals['overdue_contracts'] ?? 0,
                'accent' => 'accent-warning'
            ],
            [
                'label' => 'Largest debt',
                'caption' => 'Single biggest request',
                'value' => number_format($debtTotals['largest_debt'] ?? 0, 2) . ' AED',
                'accent' => 'accent-primary'
            ],
            [
                'label' => 'Credit buffer',
                'caption' => 'Prepaid amount',
                'value' => number_format($debtTotals['credit'] ?? 0, 2) . ' AED',
                'accent' => 'accent-info'
            ],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($statCards as $card)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="debt-stat-card {{ $card['accent'] }}">
                    <p class="text-muted text-uppercase small mb-1">{{ $card['label'] }}</p>
                    <h4 class="mb-1">{{ $card['value'] }}</h4>
                    <span class="text-muted small">{{ $card['caption'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-1">Debt insights</h5>
                    <span class="text-muted small">Automatic snapshot for requests</span>
                </div>
                <div class="card-body pt-0">
                    <div class="insight-item">
                        <p class="text-muted small mb-1">Largest exposure</p>
                        @if ($insights['most_critical'] ?? null)
                            <h6 class="mb-0">{{ $insights['most_critical']['label'] }} ·
                                {{ $insights['most_critical']['car'] }}</h6>
                            <span class="text-danger fw-semibold">{{ number_format($insights['most_critical']['outstanding'], 2) }} AED</span>
                        @else
                            <span class="text-muted small">No open debt</span>
                        @endif
                    </div>
                    <div class="insight-item">
                        <p class="text-muted small mb-1">Oldest debt</p>
                        @if ($insights['oldest_debt'] ?? null)
                            <h6 class="mb-0">{{ $insights['oldest_debt']['label'] }}</h6>
                            <span class="text-muted small">{{ $insights['oldest_debt']['timeline_days'] }} day(s) overdue</span>
                        @else
                            <span class="text-muted small">No overdue requests</span>
                        @endif
                    </div>
                    <div class="insight-item mb-0">
                        <p class="text-muted small mb-1">Credit available</p>
                        @if ($insights['credit_contract'] ?? null)
                            <h6 class="mb-0">{{ $insights['credit_contract']['label'] }}</h6>
                            <span class="text-success fw-semibold">{{ number_format($insights['credit_contract']['credit'], 2) }} AED</span>
                        @else
                            <span class="text-muted small">No prepaid balance</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="mb-1">Requests debt</h5>
                        <span class="text-muted small">Monitor each contract and its open debt</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <select class="form-select form-select-sm" wire:model.live="statusFilter">
                            <option value="all">All status</option>
                            <option value="open">Open debt</option>
                            <option value="overdue">Overdue</option>
                            <option value="settled">Settled</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                <div class="card-body p-0">
                    @php
                        $statusMeta = [
                            'open' => ['label' => 'Open debt', 'class' => 'bg-label-warning text-dark'],
                            'overdue' => ['label' => 'Overdue', 'class' => 'bg-label-danger'],
                            'settled' => ['label' => 'Settled', 'class' => 'bg-label-success'],
                            'credit' => ['label' => 'Credit', 'class' => 'bg-label-info text-dark'],
                        ];
                    @endphp
                    @forelse ($debtContracts as $contract)
                        @php($meta = $statusMeta[$contract['status']] ?? $statusMeta['open'])
                        <div class="debt-row">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                                        <h6 class="mb-0">{{ $contract['label'] }}</h6>
                                    </div>
                                    <div class="text-muted small">
                                        {{ $contract['car'] }} · Pickup {{ $contract['pickup_date'] ?? '---' }}
                                        @if ($contract['timeline_days'])
                                            · <span class="text-danger fw-semibold">{{ $contract['timeline_days'] }} day(s) overdue</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-lg-end">
                                    <p class="text-muted small mb-1">Debt amount</p>
                                    @if ($contract['status'] === 'credit')
                                        <h5 class="text-success mb-0">+{{ number_format($contract['credit'], 2) }} AED</h5>
                                    @else
                                        <h5 class="text-danger mb-0">{{ number_format($contract['outstanding'], 2) }} AED</h5>
                                    @endif
                                    <span class="badge {{ $contract['risk']['class'] ?? 'bg-label-secondary' }} mt-1">
                                        {{ $contract['risk']['label'] ?? 'Debt' }}
                                    </span>
                                </div>
                            </div>
                            <div class="row g-3 mt-3">
                                <div class="col-6 col-md-3">
                                    <p class="text-muted small mb-1">Paid</p>
                                    <div class="fw-semibold">{{ number_format($contract['paid'], 2) }} AED</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-muted small mb-1">Deposit</p>
                                    <div class="fw-semibold">{{ number_format($contract['deposit'], 2) }} AED</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-muted small mb-1">Fines & extras</p>
                                    <div class="fw-semibold">{{ number_format($contract['fines'] + $contract['extras'], 2) }} AED</div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-muted small mb-1">Discounts</p>
                                    <div class="fw-semibold">{{ number_format($contract['discounts'] - $contract['refunds'], 2) }} AED</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1 text-muted small">
                                    <span>Total value {{ number_format($contract['total'], 2) }} AED</span>
                                    <span>{{ min($contract['progress'], 150) }}% collected</span>
                                </div>
                                <div class="progress debt-progress">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: {{ min($contract['progress'], 120) }}%"></div>
                                </div>
                            </div>
                            <div class="debt-breakdown">
                                <span>Latest payment: {{ $contract['latest_payment'] ?? 'Not available' }}</span>
                                @if ($contract['notes'])
                                    <span class="text-muted">Note: {{ \Illuminate\Support\Str::limit($contract['notes'], 70) }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bx bx-check-circle display-5 d-block mb-2"></i>
                            <p class="mb-0">No records found for this filter.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .debt-stat-card {
                border-radius: 1rem;
                padding: 1.25rem;
                border: 1px solid rgba(105, 108, 255, 0.15);
                background: #fff;
                min-height: 140px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            .debt-stat-card h4 {
                font-weight: 700;
            }

            .debt-stat-card.accent-danger {
                border-color: rgba(255, 81, 81, 0.2);
            }

            .debt-stat-card.accent-warning {
                border-color: rgba(255, 177, 66, 0.3);
            }

            .debt-stat-card.accent-primary {
                border-color: rgba(105, 108, 255, 0.35);
            }

            .debt-stat-card.accent-info {
                border-color: rgba(32, 201, 151, 0.35);
            }

            .insight-item {
                padding: 0.9rem 0;
                border-bottom: 1px solid rgba(145, 158, 171, 0.2);
            }

            .insight-item:last-child {
                border-bottom: 0;
            }

            .debt-row {
                padding: 1.75rem;
                border-bottom: 1px solid rgba(145, 158, 171, 0.15);
            }

            .debt-row:last-child {
                border-bottom: 0;
            }

            .debt-progress {
                height: 8px;
                background-color: rgba(145, 158, 171, 0.3);
            }

            .debt-progress .progress-bar {
                background: linear-gradient(90deg, #ff5f6d, #ffc371);
            }

            .debt-breakdown {
                margin-top: 1rem;
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                font-size: 0.85rem;
                color: #6c757d;
            }

            .debt-score {
                color: #0f172a;
            }
        </style>
    @endpush
</div>
