@php
    $overallCount = $existingPayments->count();
    $remainingBalance = $remainingBalance ?? 0;
    $paymentPeriods = $paymentPeriods ?? [];
    $paymentPeriodFilter = $paymentPeriodFilter ?? 'all';
    $selectedPeriod = collect($paymentPeriods)->firstWhere('key', $paymentPeriodFilter);
    $unassignedPayments = $unassignedPayments ?? $existingPayments->whereNotIn('id', collect($paymentPeriods)->flatMap(fn ($period) => $period['payments']->pluck('id')))->values();
    $isUnassigned = $paymentPeriodFilter === 'unassigned';
    $ledgerPayments = $isUnassigned ? $unassignedPayments : ($selectedPeriod['payments'] ?? $existingPayments);
    $overallLedgerBalance = $overallLedgerBalance ?? $existingPayments->sum(function ($payment) {
        $amount = (float) ($payment->amount_in_aed ?? 0);

        return \App\Models\Payment::isChargePaymentType($payment->payment_type) || $payment->payment_type === 'payment_back'
            ? -$amount
            : $amount;
    });
    $sectionLedgerBalance = $isUnassigned
        ? (float) $unassignedPayments->sum(fn ($payment) => \App\Models\Payment::isChargePaymentType($payment->payment_type) || $payment->payment_type === 'payment_back' ? -(float) $payment->amount_in_aed : (float) $payment->amount_in_aed)
        : (float) ($selectedPeriod['ledger_balance'] ?? $overallLedgerBalance);
    $ledgerTitle = $isUnassigned ? 'Outside saved ranges' : ($selectedPeriod['title'] ?? 'All Entries');
    $ledgerDateRange = $selectedPeriod
        ? $selectedPeriod['starts_at']->format('M d, Y').' → before '.$selectedPeriod['ends_at']->format('M d, Y')
        : ($isUnassigned ? 'Payments not covered by an active saved range' : 'Complete contract payment history');
    $canManagePeriods = ($showActions ?? true) && isset($_instance);
    $defaultPeriod = collect($paymentPeriods)->firstWhere('is_default', true);

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

<div class="payments-workspace my-4" id="payment-date-ranges">
    <div class="payments-workspace__hero">
        <div>
            <div class="payments-kicker">Accounting View</div>
            <h5 class="payments-title mb-1">Payment ledger</h5>
            <p class="payments-subtitle mb-0">Review all payments or save custom date ranges for this contract.</p>
        </div>
        <div class="payments-overview">
            <div class="payments-overview__card">
                <span class="payments-overview__label">All Entries</span>
                <strong class="payments-overview__value">{{ $overallCount }}</strong>
            </div>
            <div class="payments-overview__card payments-overview__card--balance">
                <span class="payments-overview__label">Overall Balance</span>
                <strong class="payments-overview__value {{ $overallLedgerBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($overallLedgerBalance, 2) }} AED</strong>
            </div>
        </div>
    </div>

    @if ($canManagePeriods)
        @include('livewire.pages.panel.expert.rental-request.partials.payment-period-editor')
    @endif

    <div class="payment-period-selector" aria-label="Saved payment date ranges">
        <button type="button" wire:click="selectPaymentPeriod('all')" class="payment-period-selector__button {{ $paymentPeriodFilter === 'all' ? 'is-active' : '' }}" aria-pressed="{{ $paymentPeriodFilter === 'all' ? 'true' : 'false' }}">
            <strong>All entries @if (! $defaultPeriod)<span class="payment-period-default">Default</span>@endif</strong><span>{{ $overallCount }} entries</span>
        </button>
        <button type="button" wire:click="selectPaymentPeriod('unassigned')" class="payment-period-selector__button {{ $isUnassigned ? 'is-active' : '' }}" aria-pressed="{{ $isUnassigned ? 'true' : 'false' }}">
            <strong>Outside saved ranges</strong><span>{{ $unassignedPayments->count() }} entries</span>
        </button>
        @foreach ($paymentPeriods as $period)
            <div class="payment-period-selector__card" wire:key="payment-period-{{ $period['id'] }}">
                <button type="button" wire:click="selectPaymentPeriod('{{ $period['key'] }}')" class="payment-period-selector__button {{ $paymentPeriodFilter === $period['key'] ? 'is-active' : '' }}" aria-pressed="{{ $paymentPeriodFilter === $period['key'] ? 'true' : 'false' }}">
                    <strong>{{ $period['title'] }} @if ($period['is_default'])<span class="payment-period-default">Default</span>@endif</strong>
                    <span>{{ $period['starts_at']->format('M d, Y') }} → before {{ $period['ends_at']->format('M d, Y') }}</span>
                    <span>{{ $period['entry_count'] }} entries · {{ $period['duration_days'] }} days</span>
                    <span class="payment-period-selector__creator">Saved by {{ $period['created_by'] }} · {{ $period['created_at']->format('M d, Y') }}</span>
                    <span class="payment-period-selector__balance">
                        <span>Section Balance</span>
                        <strong class="{{ $period['ledger_balance'] >= 0 ? 'is-positive' : 'is-negative' }}">{{ number_format($period['ledger_balance'], 2) }} AED</strong>
                    </span>
                </button>
                @if ($canManagePeriods)
                    <div class="payment-period-selector__controls">
                        @if ($period['can_edit'] ?? false)
                            <button type="button" class="btn btn-sm btn-light border" wire:click="editPaymentPeriod({{ $period['id'] }})" wire:loading.attr="disabled" aria-label="Edit range: {{ $period['title'] }}">Edit range</button>
                        @else
                            <button type="button" class="btn btn-sm btn-light border" disabled title="Only the last active range by date can be edited.">Edit range</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="deletePaymentPeriod({{ $period['id'] }})" wire:confirm="Delete this range? Payments will remain unchanged. You can restore the range from history." wire:loading.attr="disabled" aria-label="Delete range: {{ $period['title'] }}">Delete range</button>
                    </div>
                    @unless ($period['can_edit'] ?? false)
                        <span class="payment-period-selector__hint">Only the last range can be edited.</span>
                    @endunless
                @endif
            </div>
        @endforeach
    </div>

    @if ($canManagePeriods)
        <div class="payment-period-actions">
            @if ($selectedPeriod)
                <span class="small text-muted">Saved by {{ $selectedPeriod['created_by'] }} · {{ $selectedPeriod['created_at']->format('Y-m-d H:i') }}</span>
                <div class="d-flex flex-wrap gap-2">
                    @unless ($selectedPeriod['is_default'])
                        <button type="button" class="btn btn-sm btn-outline-success" wire:click="setDefaultPaymentPeriod({{ $selectedPeriod['id'] }})" wire:loading.attr="disabled">Make default</button>
                    @endunless
                </div>
            @elseif ($paymentPeriodFilter === 'all' && $defaultPeriod)
                <button type="button" class="btn btn-sm btn-light border" wire:click="setDefaultPaymentPeriod" wire:loading.attr="disabled">Use all entries as default</button>
            @endif
        </div>
        @if (empty($paymentPeriods))
            <p class="small text-muted mb-3">No saved ranges yet. Choose dates above to create the first one. All payments are available below.</p>
        @endif
        @error('periodAction')<div class="alert alert-danger py-2" role="alert">{{ $message }}</div>@enderror
        @include('livewire.pages.panel.expert.rental-request.partials.payment-period-history')
    @endif

    <div class="payment-lifecycle">
        @php
            $groupPayments = $ledgerPayments;
            $groupTotalAed = (float) $groupPayments->sum('amount_in_aed');
            $groupCount = $groupPayments->count();
        @endphp
        <section class="ledger-panel ledger-panel--period">
                    <header class="ledger-panel__header">
                        <div class="ledger-panel__title-wrap">
                            <div class="ledger-panel__icon">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h6 class="ledger-panel__title mb-0">{{ $ledgerTitle }}</h6>
                                    <span class="ledger-period-badge">
                                        {{ $ledgerDateRange }}
                                    </span>
                                </div>
                                <p class="ledger-panel__subtitle mb-0">Customer payments and charges are shown together for this accounting view.</p>
                            </div>
                        </div>
                        <div class="ledger-panel__summary">
                            <span class="ledger-panel__count">{{ $groupCount }} {{ $groupCount === 1 ? 'entry' : 'entries' }}</span>
                            <strong class="ledger-panel__total">{{ number_format($groupTotalAed, 2) }} AED recorded</strong>
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
                                            <span>{{ optional($payment->payment_date)->format('Y-m-d') ?? 'Payment date unavailable' }}</span>
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

                                        @switch($payment->approval_status)
                                            @case('approved')
                                                <span class="ledger-chip ledger-chip--approved">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="ledger-chip ledger-chip--rejected">Rejected</span>
                                                @break
                                            @default
                                                <span class="ledger-chip ledger-chip--pending">Pending approval</span>
                                        @endswitch

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
                                <div class="ledger-empty__title">No entries recorded in this view</div>
                                <div class="ledger-empty__text">Entries remain available in All and are assigned by payment date.</div>
                            </div>
                        @endforelse
                    </div>
                    <footer class="ledger-panel__balance">
                        <div>
                            <span class="ledger-panel__balance-label">Section Balance</span>
                            <span class="ledger-panel__balance-help">Customer credits − charges</span>
                        </div>
                        <strong class="ledger-panel__balance-value {{ $sectionLedgerBalance >= 0 ? 'is-positive' : 'is-negative' }}">
                            {{ number_format($sectionLedgerBalance, 2) }} AED
                        </strong>
                    </footer>
                </section>
    </div>
</div>
