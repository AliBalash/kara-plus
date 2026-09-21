<div class="container-xxl flex-grow-1 container-p-y">
    @php
        $extensionAmendments = $contract->amendments
            ->where('type', \App\Models\ContractAmendment::TYPE_EXTENSION)
            ->values();
        $extensionReversals = $contract->amendments
            ->where('type', 'adjustment')
            ->where('status', 'approved')
            ->filter(fn ($amendment) => data_get($amendment->pricing_snapshot, 'reverses_amendment_id') !== null)
            ->keyBy(fn ($amendment) => (int) data_get($amendment->pricing_snapshot, 'reverses_amendment_id'));
        $rateSourceLabels = [
            \App\Services\RentalPricingService::RATE_SOURCE_CONTRACT => 'Saved contract rate',
            \App\Services\RentalPricingService::RATE_SOURCE_CURRENT => 'Current tariff · extension length',
            \App\Services\RentalPricingService::RATE_SOURCE_CURRENT_TOTAL_DURATION => 'Current automatic tier · total rental length',
        ];
        $tierLabels = [
            'contract' => 'Saved on contract',
            'daily_1_to_6' => 'Daily tier · 1–6 days',
            'weekly_7_to_27' => 'Weekly tier · 7–27 days',
            'monthly_28_plus' => 'Monthly tier · 28+ days',
        ];
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="text-muted small">Contract #{{ $contract->id }}</div>
            <h4 class="mb-0">Manage Contract Extensions</h4>
            <div class="text-muted mt-1">Preview every date, day-count, price and balance consequence before it becomes effective.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('rental-requests.details', $contract->id) }}">Back to contract</a>
    </div>

    @if (session('message'))
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i>{{ session('message') }}</div>
    @endif

    <div class="alert alert-info border-0 shadow-sm mb-4" role="status">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle fs-5 lh-1"></i>
            <div class="small">
                <div class="fw-semibold">When an approved extension can be revised</div>
                <div class="mt-1">An approved extension may be edited or removed only when all of the following are true:</div>
                <ul class="mb-1 ps-3">
                    <li>The contract is still open and the vehicle has not been returned.</li>
                    <li>It is the latest approved extension on this contract.</li>
                    <li>No extension request is awaiting approval.</li>
                </ul>
                <div>If any condition is not met, the extension is protected to preserve the correct dates, charges, and customer balance. Revise extensions newest-first.</div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3"><small class="text-muted d-block">Original return</small><span>{{ $contract->original_return_date?->format('Y-m-d H:i') ?? $contract->return_date?->format('Y-m-d H:i') }}</span></div>
                <div class="col-md-3"><small class="text-muted d-block">Current planned return</small><span class="fw-semibold">{{ $contract->return_date?->format('Y-m-d H:i') }}</span></div>
                <div class="col-md-3"><small class="text-muted d-block">Current contract total</small><span class="fw-semibold text-primary">{{ number_format((float) $contract->total_price, 2) }} AED</span></div>
                <div class="col-md-3"><small class="text-muted d-block">Current customer balance</small><span class="fw-semibold">{{ number_format((float) $contract->calculateRemainingBalance(), 2) }} AED</span></div>
            </div>
            <hr class="my-4">
            <div class="row g-3 small">
                <div class="col-lg-4"><strong>Saved contract rate:</strong> {{ is_numeric($contract->used_daily_rate) ? number_format((float) $contract->used_daily_rate, 2).' AED/day' : 'Not recorded; current tier fallback applies' }}</div>
                <div class="col-lg-8 text-muted">Approved history is never silently overwritten. Revising or removing the latest approved extension creates a matching reversal in the financial ledger.</div>
            </div>
        </div>
    </div>

    @error('contract')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('amendment')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('pricing')<div class="alert alert-danger">{{ $message }}</div>@enderror

    @if ($confirmationAction && $confirmationImpact !== [])
        @php $isDeleteConfirmation = $confirmationAction === 'delete'; @endphp
        <div class="card mb-4 border-{{ $isDeleteConfirmation ? 'danger' : 'success' }} shadow-sm">
            <div class="card-header bg-{{ $isDeleteConfirmation ? 'danger' : 'success' }}-subtle">
                <h5 class="mb-1">Confirm {{ $isDeleteConfirmation ? 'extension removal' : 'extension approval' }}</h5>
                <div class="small">
                    {{ $isDeleteConfirmation
                        ? 'Review the exact rollback below. Approved records are voided through compensating financial entries; pending records are soft-deleted.'
                        : 'The following schedule and financial changes will become effective immediately.' }}
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><small class="text-muted d-block">Planned return</small><span>{{ $confirmationImpact['return_before'] }}</span> <i class="bi bi-arrow-right mx-1"></i> <strong>{{ $confirmationImpact['return_after'] }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Total rental days</small><span>{{ $confirmationImpact['rental_days_before'] }}</span> <i class="bi bi-arrow-right mx-1"></i> <strong>{{ $confirmationImpact['rental_days_after'] }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Contract total</small><span>{{ number_format($confirmationImpact['contract_total_before'], 2) }}</span> <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($confirmationImpact['contract_total_after'], 2) }} AED</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Extension amount</small><span>{{ number_format($confirmationImpact['old_extension_total'], 2) }}</span> <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($confirmationImpact['new_extension_total'], 2) }} AED</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Change to contract total</small><strong class="{{ $confirmationImpact['total_delta'] < 0 ? 'text-danger' : 'text-success' }}">{{ $confirmationImpact['total_delta'] >= 0 ? '+' : '' }}{{ number_format($confirmationImpact['total_delta'], 2) }} AED</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Customer balance</small><span>{{ number_format($confirmationImpact['balance_before'], 2) }}</span> <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($confirmationImpact['balance_after'], 2) }} AED</strong></div>
                </div>
                <div class="form-check p-3 border rounded bg-light">
                    <input id="confirmationAccepted" class="form-check-input" type="checkbox" wire:model.live="confirmationAccepted">
                    <label class="form-check-label fw-semibold" for="confirmationAccepted">I reviewed the dates, day count, contract total and customer balance shown above.</label>
                    @error('confirmationAccepted')<small class="text-danger d-block">You must confirm the displayed consequences.</small>@enderror
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <button wire:click="confirmPreparedAction" wire:loading.attr="disabled" class="btn btn-{{ $isDeleteConfirmation ? 'danger' : 'success' }}" @disabled(!$confirmationAccepted)>
                        {{ $isDeleteConfirmation ? 'Confirm removal / reversal' : 'Confirm and approve' }}
                    </button>
                    <button wire:click="dismissConfirmation" type="button" class="btn btn-outline-secondary">Keep unchanged</button>
                </div>
            </div>
        </div>
    @endif

    @if ($editingAmendmentId !== null || $this->canRequestExtension())
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">{{ $editingAmendmentId ? 'Edit extension #'.$contract->amendments->firstWhere('id', $editingAmendmentId)?->sequence_no : 'New extension request' }}</h5>
                        <div class="small text-muted">Changing any pricing input invalidates the preview and requires a fresh review.</div>
                    </div>
                    @if ($editingAmendmentId)<button type="button" wire:click="cancelEdit" class="btn btn-sm btn-outline-secondary">Cancel edit</button>@endif
                </div>

                @if ($editingApproved)
                    <div class="alert alert-warning">
                        This is an approved extension. Saving will reverse its existing charges, preserve the original record as superseded, and apply the reviewed replacement atomically.
                    </div>
                @endif

                <form wire:submit="request" class="row g-3">
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">New planned return</label>
                        <input wire:model="newReturnAt" type="datetime-local" class="form-control">
                        @error('newReturnAt')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Billing method</label>
                        <select wire:model="pricingPolicy" class="form-select">
                            <option value="daily_ceiling">Daily ceiling — a partial extension day rounds up</option>
                            <option value="hourly">Hourly — each started hour rounds up</option>
                            <option value="prorated_daily">Prorated daily — exact fraction of a day</option>
                            <option value="grace_then_daily">1-hour grace — then daily ceiling</option>
                        </select>
                        @error('pricingPolicy')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Rental price basis</label>
                        <select wire:model="rateSource" class="form-select">
                            <option value="contract_rate">Saved contract rate</option>
                            <option value="current_tariff">Current tariff based on extension length</option>
                            <option value="current_total_duration_tariff">Current automatic daily / weekly / monthly tier based on resulting total rental length</option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            Saved rate preserves the agreed rate. Current options read the vehicle catalogue at preview and verify it again before approval.
                        </small>
                        @error('rateSource')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-5"><label class="form-label">Reason</label><input wire:model="reason" class="form-control">@error('reason')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-md-7"><label class="form-label">Notes</label><textarea wire:model="notes" class="form-control" rows="2"></textarea>@error('notes')<small class="text-danger">{{ $message }}</small>@enderror</div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" wire:click="preview" wire:loading.attr="disabled" class="btn btn-outline-primary"><i class="bi bi-calculator me-1"></i>Preview complete impact</button>
                        @if ($quote === [])<span class="small text-muted">A fresh preview is required before submission.</span>@endif
                    </div>
                    @error('quote')<div class="col-12"><div class="alert alert-warning mb-0">{{ $message }}</div></div>@enderror

                    @if ($quote !== [])
                        @php
                            $impact = $quote['impact'];
                            $usesContractRate = ($quote['rate_source'] ?? null) === \App\Services\RentalPricingService::RATE_SOURCE_CONTRACT;
                        @endphp
                        <div class="col-12">
                            <div class="border rounded overflow-hidden">
                                <div class="p-3 {{ !empty($quote['rate_changed']) ? 'bg-warning-subtle' : 'bg-light' }} border-bottom">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-lg-4"><small class="text-muted d-block">Price basis</small><strong>{{ $rateSourceLabels[$quote['rate_source']] ?? \Illuminate\Support\Str::headline($quote['rate_source']) }}</strong></div>
                                        <div class="col-lg-3"><small class="text-muted d-block">Applied tier</small><strong>{{ $tierLabels[$quote['rate_tier'] ?? 'contract'] ?? \Illuminate\Support\Str::headline($quote['rate_tier'] ?? 'contract') }}</strong></div>
                                        <div class="col-lg-3"><small class="text-muted d-block">Effective rental rate</small><strong>{{ number_format((float) $quote['effective_daily_rate'], 2) }} AED/day</strong></div>
                                        <div class="col-lg-2 text-lg-end"><span class="badge {{ !empty($quote['rate_changed']) ? 'bg-warning text-dark' : 'bg-success' }}">{{ !empty($quote['rate_changed']) ? 'Different from contract rate' : 'Matches contract rate' }}</span></div>
                                    </div>
                                    <div class="small mt-2 text-muted">Current catalogue comparison: extension-length {{ number_format((float) $quote['current_extension_daily_rate'], 2) }} AED/day · resulting-total tier {{ number_format((float) $quote['current_total_duration_daily_rate'], 2) }} AED/day.</div>
                                </div>

                                <div class="p-3 border-bottom">
                                    <h6 class="mb-3">Visible consequences</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4"><small class="text-muted d-block">Planned return</small>{{ $impact['return_before'] }} <i class="bi bi-arrow-right mx-1"></i> <strong>{{ $impact['return_after'] }}</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Total rental days</small>{{ $impact['rental_days_before'] }} <i class="bi bi-arrow-right mx-1"></i> <strong>{{ $impact['rental_days_after'] }}</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Billable extension quantity</small><strong>{{ number_format((float) $quote['billable_days'], 3) }}</strong> {{ $pricingPolicy === 'hourly' ? 'hour(s)' : 'day(s)' }}</div>
                                        <div class="col-md-4"><small class="text-muted d-block">Extension amount</small>{{ number_format($impact['old_extension_total'], 2) }} <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($impact['new_extension_total'], 2) }} AED</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Contract total</small>{{ number_format($impact['contract_total_before'], 2) }} <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($impact['contract_total_after'], 2) }} AED</strong></div>
                                        <div class="col-md-4"><small class="text-muted d-block">Customer balance</small>{{ number_format($impact['balance_before'], 2) }} <i class="bi bi-arrow-right mx-1"></i> <strong>{{ number_format($impact['balance_after'], 2) }} AED</strong></div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-light"><tr><th>Item</th><th>Calculation</th><th class="text-end">Before VAT</th></tr></thead>
                                        <tbody>
                                            @foreach ($quote['items'] as $item)
                                                <tr><td class="fw-semibold">{{ $item['title'] }}</td><td>{{ number_format((float) $item['quantity'], 3) }} {{ \Illuminate\Support\Str::headline($item['unit']) }} × {{ number_format((float) $item['unit_price'], 2) }} AED</td><td class="text-end">{{ number_format((float) $item['amount'], 2) }} AED</td></tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr><th colspan="2" class="text-end">Subtotal</th><th class="text-end">{{ number_format((float) $quote['subtotal'], 2) }} AED</th></tr>
                                            <tr><th colspan="2" class="text-end">VAT</th><th class="text-end">{{ number_format((float) $quote['tax'], 2) }} AED</th></tr>
                                            <tr class="table-primary"><th colspan="2" class="text-end">New extension total</th><th class="text-end fs-5">{{ number_format((float) $quote['total'], 2) }} AED</th></tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check p-3 border rounded bg-light">
                                <input id="reviewConfirmed" class="form-check-input" type="checkbox" wire:model.live="reviewConfirmed">
                                <label class="form-check-label fw-semibold" for="reviewConfirmed">I reviewed the dates, day count, selected price basis, itemized charges, contract total and customer balance.</label>
                                @error('reviewConfirmed')<small class="text-danger d-block">You must confirm the displayed consequences.</small>@enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <button wire:loading.attr="disabled" class="btn btn-primary" @disabled(!$reviewConfirmed)>
                                {{ $editingAmendmentId ? ($editingApproved ? 'Confirm and apply approved revision' : 'Update reviewed request') : 'Create reviewed extension request' }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    @elseif (!in_array($contract->current_status, \App\Models\Contract::AMENDABLE_STATUSES, true))
        <div class="alert alert-warning">Only a delivered rental that has not yet been returned can be extended.</div>
    @else
        <div class="alert alert-info">Edit, approve, reject, cancel or delete the pending extension before creating another request.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <div><h5 class="mb-1">Extension history</h5><div class="small text-muted">Older approved extensions become editable newest-first so dependent dates and charges always remain consistent.</div></div>
                <span class="badge bg-label-secondary align-self-start">{{ $extensionAmendments->count() }} record(s)</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th># / Period</th><th>Price basis</th><th>Calculation</th><th>Status</th><th class="text-end">Extension total</th><th>Manage</th></tr></thead>
                    <tbody>
                        @forelse ($extensionAmendments as $amendment)
                            @php
                                $snapshot = (array) $amendment->pricing_snapshot;
                                $rentalItem = collect((array) ($snapshot['items'] ?? []))->firstWhere('code', 'extension_rental');
                                $legacyDailyRate = (float) ($rentalItem['unit_price'] ?? 0) * (($rentalItem['unit'] ?? null) === 'hour' ? 24 : 1);
                                $effectiveRate = (float) ($snapshot['effective_daily_rate'] ?? $legacyDailyRate);
                                $rateSource = $snapshot['rate_source'] ?? null;
                                $reversal = $extensionReversals->get((int) $amendment->id);
                                $statusClass = match ($amendment->status) {
                                    'approved' => 'bg-success',
                                    'pending_approval' => 'bg-warning text-dark',
                                    'superseded', 'voided' => 'bg-secondary',
                                    'rejected', 'cancelled' => 'bg-danger',
                                    default => 'bg-label-secondary',
                                };
                            @endphp
                            <tr class="{{ in_array($amendment->status, ['superseded', 'voided'], true) ? 'text-muted' : '' }}">
                                <td><div class="fw-semibold">#{{ $amendment->sequence_no }}</div><small>{{ $amendment->extension_start_at?->format('Y-m-d H:i') }} → {{ $amendment->extension_end_at?->format('Y-m-d H:i') }}</small></td>
                                <td><div class="fw-semibold">{{ number_format($effectiveRate, 2) }} AED/day</div><small>{{ $rateSourceLabels[$rateSource] ?? ($rateSource ? \Illuminate\Support\Str::headline($rateSource) : 'Legacy snapshot') }}</small>@if(isset($snapshot['rate_tier']))<span class="badge bg-label-info d-block mt-1" style="width: fit-content">{{ $tierLabels[$snapshot['rate_tier']] ?? \Illuminate\Support\Str::headline($snapshot['rate_tier']) }}</span>@endif</td>
                                <td>@if ($rentalItem){{ number_format((float) $rentalItem['quantity'], 3) }} {{ \Illuminate\Support\Str::headline($rentalItem['unit']) }} × {{ number_format((float) $rentalItem['unit_price'], 2) }} = <strong>{{ number_format((float) $rentalItem['amount'], 2) }} AED</strong><small class="text-muted d-block">+ VAT {{ number_format((float) $amendment->tax_amount, 2) }} AED</small>@else<span class="text-muted">Pricing details unavailable</span>@endif</td>
                                <td>
                                    <span class="badge {{ $statusClass }}">{{ \Illuminate\Support\Str::headline($amendment->status) }}</span>
                                    @if ($reversal)
                                        <small class="text-muted d-block mt-1">Financial reversal #{{ $reversal->sequence_no }} · {{ number_format((float) $reversal->total_amount, 2) }} {{ $reversal->currency }}</small>
                                    @endif
                                    @if (isset($snapshot['replaces_amendment_id']))
                                        <small class="text-muted d-block mt-1">Replaces extension ID {{ $snapshot['replaces_amendment_id'] }}</small>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ number_format((float) $amendment->total_amount, 2) }} {{ $amendment->currency }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($amendment->isPending())
                                            <button wire:click="prepareApproval({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-success">Review approval</button>
                                            <button wire:click="reject({{ $amendment->id }})" wire:confirm="Reject this extension request without changing the contract?" wire:loading.attr="disabled" class="btn btn-sm btn-outline-danger">Reject</button>
                                            <button wire:click="cancel({{ $amendment->id }})" wire:confirm="Cancel this extension request without changing the contract?" wire:loading.attr="disabled" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                        @endif
                                        @if ($this->canEditAmendment($amendment))<button wire:click="edit({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-outline-primary">Edit</button>@endif
                                        @if ($this->canDeleteAmendment($amendment))<button wire:click="prepareDelete({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-outline-danger">Delete</button>@endif
                                        @if ($amendment->isApproved() && !$this->canEditAmendment($amendment))<small class="text-muted">Dependent history — manage newer extension first</small>@endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No extensions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
