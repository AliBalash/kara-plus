@php
    $customerPayments = $existingPayments->reject(
        fn($payment) => \App\Models\Payment::isChargePaymentType($payment->payment_type)
    )->values();

    $chargePayments = $existingPayments->filter(
        fn($payment) => \App\Models\Payment::isChargePaymentType($payment->payment_type)
    )->values();

    $groups = [
        [
            'title' => 'Customer Payments',
            'subtitle' => 'Rental collections, deposits, discounts, and refunds.',
            'accent' => 'customer',
            'icon' => 'bi-wallet2',
            'payments' => $customerPayments,
            'empty' => 'No customer payments found.',
        ],
        [
            'title' => 'Charges & Costs',
            'subtitle' => 'Salik, fines, parking, damage, fuel, carwash, and similar costs.',
            'accent' => 'charge',
            'icon' => 'bi-receipt-cutoff',
            'payments' => $chargePayments,
            'empty' => 'No charges found.',
        ],
    ];

    $overallCount = $existingPayments->count();
    $overallAed = (float) $existingPayments->sum('amount_in_aed');

    $amountLabel = static function ($paymentType): string {
        return \App\Models\Payment::isChargePaymentType($paymentType)
            ? 'Charge in balance'
            : 'Deducted from balance';
    };

    $typeLabel = static function ($payment): string {
        if ($payment->payment_type === 'security_deposit') {
            return 'Security deposit';
        }

        if ($payment->payment_type === 'toll') {
            return 'Salik';
        }

        return \App\Models\Payment::paymentTypeLabels()[$payment->payment_type]
            ?? ucwords(str_replace('_', ' ', $payment->payment_type));
    };
@endphp

<div class="payments-workspace my-4">
    <div class="payments-workspace__hero">
        <div>
            <div class="payments-kicker">Accounting View</div>
            <h5 class="payments-title mb-1">Existing Payments</h5>
            <p class="payments-subtitle mb-0">Customer inflows and contract charges are separated for cleaner invoice review.</p>
        </div>
        <div class="payments-overview">
            <div class="payments-overview__card">
                <span class="payments-overview__label">All Entries</span>
                <strong class="payments-overview__value">{{ $overallCount }}</strong>
            </div>
            <div class="payments-overview__card">
                <span class="payments-overview__label">Ledger Total</span>
                <strong class="payments-overview__value">{{ number_format($overallAed, 2) }} AED</strong>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($groups as $group)
            @php
                $groupPayments = $group['payments'];
                $groupTotalAed = (float) $groupPayments->sum('amount_in_aed');
                $groupCount = $groupPayments->count();
            @endphp
            <div class="col-12 col-xl-6">
                <section class="ledger-panel ledger-panel--{{ $group['accent'] }}">
                    <header class="ledger-panel__header">
                        <div class="ledger-panel__title-wrap">
                            <div class="ledger-panel__icon">
                                <i class="bi {{ $group['icon'] }}"></i>
                            </div>
                            <div>
                                <h6 class="ledger-panel__title mb-1">{{ $group['title'] }}</h6>
                                <p class="ledger-panel__subtitle mb-0">{{ $group['subtitle'] }}</p>
                            </div>
                        </div>
                        <div class="ledger-panel__summary">
                            <span class="ledger-panel__count">{{ $groupCount }} item{{ $groupCount === 1 ? '' : 's' }}</span>
                            <strong class="ledger-panel__total">{{ number_format($groupTotalAed, 2) }} AED</strong>
                        </div>
                    </header>

                    <div class="ledger-list">
                        @forelse ($groupPayments as $payment)
                            @php
                                $damageImages = $payment->damageImagePaths();
                            @endphp
                            <article class="ledger-entry">
                                <div class="ledger-entry__top">
                                    <div class="ledger-entry__identity">
                                        <div class="ledger-entry__type-row">
                                            <span class="ledger-entry__type">{{ $typeLabel($payment) }}</span>
                                            <span class="ledger-entry__id">#{{ $payment->id }}</span>
                                        </div>
                                        <div class="ledger-entry__meta">
                                            <span>{{ ucfirst($payment->payment_method) }}</span>
                                            <span>{{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') }}</span>
                                            <span>
                                                Registered:
                                                {{ optional($payment->created_at)->format('Y-m-d H:i') ?? '—' }}
                                            </span>
                                            <span>{{ $payment->user?->shortName() ?? '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="ledger-entry__amounts">
                                        <div class="ledger-entry__base-amount">{{ number_format($payment->amount, 2) }}</div>
                                        <div class="ledger-entry__currency">
                                            {{ $payment->currency }}
                                            @if ($payment->currency !== 'AED' && $payment->rate)
                                                <span class="ledger-entry__rate">@ {{ $payment->rate }}</span>
                                            @endif
                                        </div>
                                        @if ($payment->amount_in_aed !== null)
                                            <div class="ledger-entry__aed">
                                                {{ $amountLabel($payment->payment_type) }}: {{ number_format((float) $payment->amount_in_aed, 2) }} AED
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="ledger-entry__bottom">
                                    <div class="ledger-entry__details">
                                        <span class="ledger-chip {{ $payment->is_refundable ? 'ledger-chip--info' : 'ledger-chip--muted' }}">
                                            {{ $payment->is_refundable ? 'Refundable' : 'Non-refundable' }}
                                        </span>

                                        @if ($payment->payment_type === 'damage' && $damageImages !== [])
                                            <span class="ledger-chip ledger-chip--accent">
                                                {{ count($damageImages) }} damage photo{{ count($damageImages) === 1 ? '' : 's' }}
                                            </span>
                                            @foreach ($damageImages as $index => $damageImage)
                                                <a class="ledger-chip ledger-chip--link" href="{{ asset('storage/' . ltrim($damageImage, '/')) }}" target="_blank">
                                                    Photo {{ $index + 1 }}
                                                </a>
                                            @endforeach
                                        @elseif ($payment->receipt)
                                            <a class="ledger-chip ledger-chip--link" href="{{ asset('storage/' . ltrim($payment->receipt, '/')) }}" target="_blank">
                                                Receipt
                                            </a>
                                        @else
                                            <span class="ledger-chip ledger-chip--muted">No receipt</span>
                                        @endif

                                        @if ($payment->isSalikBreakdownEntry())
                                            <span class="ledger-chip ledger-chip--accent">
                                                Trips: {{ $payment->salikTripCount() }} | {{ number_format($payment->salikBreakdownAmount(), 2) }} AED
                                            </span>
                                        @elseif ($payment->payment_type === 'salik')
                                            <span class="ledger-chip ledger-chip--muted">Legacy salik entry</span>
                                        @endif
                                    </div>

                                    <div class="ledger-entry__actions">
                                        @if ($payment->note)
                                            <div class="ledger-entry__note">{{ \Illuminate\Support\Str::limit($payment->note, 120) }}</div>
                                        @endif

                                        @if (($showActions ?? true) && isset($_instance))
                                            <div class="ledger-entry__buttons">
                                                <a class="btn btn-sm btn-light border"
                                                    href="{{ route('payments.edit', $payment->id) }}">
                                                    Edit
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="if(confirm('Delete this payment?')) { @this.deletePayment({{ $payment->id }}) }">
                                                    Delete
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="ledger-empty">
                                <div class="ledger-empty__icon">
                                    <i class="bi {{ $group['icon'] }}"></i>
                                </div>
                                <div class="ledger-empty__title">{{ $group['empty'] }}</div>
                                <div class="ledger-empty__text">New entries will appear here after they are recorded.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        @endforeach
    </div>
</div>
