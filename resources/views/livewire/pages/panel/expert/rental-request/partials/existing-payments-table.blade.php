@php
    $overallCount = $existingPayments->count();
    $overallAed = (float) $existingPayments->sum('amount_in_aed');
    $remainingBalance = $remainingBalance ?? 0;
    $paymentLifecycleSections = $paymentLifecycleSections ?? [[
        'title' => 'Original agreement',
        'subtitle' => 'All entries belong to the original rental period.',
        'contract_amount' => 0,
        'starts_at' => null,
        'ends_at' => null,
        'payments' => $existingPayments,
        'kind' => 'original',
    ]];

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
            <h5 class="payments-title mb-1">Payment timeline</h5>
            <p class="payments-subtitle mb-0">The original agreement and every approved extension have their own ledger, so the full payment history is readable at a glance.</p>
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
            <div class="payments-overview__card payments-overview__card--balance">
                <span class="payments-overview__label">Overall Balance</span>
                <strong class="payments-overview__value {{ $remainingBalance <= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($remainingBalance, 2) }} AED</strong>
            </div>
        </div>
    </div>

    <div class="payment-lifecycle">
        @foreach ($paymentLifecycleSections as $section)
            @php
                $groupPayments = $section['payments'];
                $groupTotalAed = (float) $groupPayments->sum('amount_in_aed');
                $groupCount = $groupPayments->count();
            @endphp
            <section class="ledger-panel ledger-panel--{{ $section['kind'] }}">
                    <header class="ledger-panel__header">
                        <div class="ledger-panel__title-wrap">
                            <div class="ledger-panel__icon">
                                <i class="bi {{ $section['kind'] === 'original' ? 'bi-file-earmark-text' : 'bi-calendar-plus' }}"></i>
                            </div>
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h6 class="ledger-panel__title mb-0">{{ $section['title'] }}</h6>
                                    <span class="ledger-period-badge">
                                        @if ($section['starts_at'] && $section['ends_at'])
                                            {{ \Carbon\Carbon::parse($section['starts_at'])->format('Y-m-d') }} → {{ \Carbon\Carbon::parse($section['ends_at'])->format('Y-m-d') }}
                                        @else
                                            Base rental period
                                        @endif
                                    </span>
                                </div>
                                <p class="ledger-panel__subtitle mb-0">{{ $section['subtitle'] }}</p>
                            </div>
                        </div>
                        <div class="ledger-panel__summary">
                            <span class="ledger-panel__count">{{ $groupCount }} entry{{ $groupCount === 1 ? '' : 'ies' }}</span>
                            <strong class="ledger-panel__total">{{ number_format($groupTotalAed, 2) }} AED recorded</strong>
                            <span class="ledger-panel__contract-total">Contract value: {{ number_format($section['contract_amount'], 2) }} AED</span>
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
                                        @if ($payment->payment_type === 'discount')
                                            <div class="ledger-entry__meta">
                                                <span>Reason: {{ \App\Models\Payment::discountReasonLabel($payment->discount_reason) }}</span>
                                            </div>
                                        @endif
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
                                    <i class="bi bi-journal-text"></i>
                                </div>
                                <div class="ledger-empty__title">No payments recorded in this section</div>
                                <div class="ledger-empty__text">New entries registered in this contract stage will appear here.</div>
                            </div>
                        @endforelse
                    </div>
                </section>
        @endforeach
    </div>
</div>
