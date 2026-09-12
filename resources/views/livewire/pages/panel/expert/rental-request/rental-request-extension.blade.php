<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="text-muted small">Contract #{{ $contract->id }}</div>
            <h4 class="mb-0">Extend Contract</h4>
            <div class="text-muted mt-1">Review the rate source and full calculation before requesting approval.</div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('rental-requests.details', $contract->id) }}">Back to contract</a>
    </div>

    @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-3"><small class="text-muted d-block">Original return</small><span>{{ $contract->original_return_date?->format('Y-m-d H:i') ?? $contract->return_date?->format('Y-m-d H:i') }}</span></div>
                <div class="col-md-3"><small class="text-muted d-block">Current planned return</small><span class="fw-semibold">{{ $contract->return_date?->format('Y-m-d H:i') }}</span></div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Contract daily rate</small>
                    <span class="fw-semibold text-primary">{{ is_numeric($contract->used_daily_rate) ? number_format((float) $contract->used_daily_rate, 2).' AED' : 'Not recorded' }}</span>
                </div>
                <div class="col-md-3"><small class="text-muted d-block">Actual return</small><span>{{ $contract->actual_return_at?->format('Y-m-d H:i') ?? 'Not returned' }}</span></div>
            </div>
            <hr class="my-4">
            <div class="small text-muted mb-2">Current vehicle tariff</div>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-label-secondary">1–6 days: {{ number_format((float) $contract->car->price_per_day_short, 2) }} AED/day</span>
                <span class="badge bg-label-secondary">7–27 days: {{ number_format((float) ($contract->car->price_per_day_mid ?? $contract->car->price_per_day_short), 2) }} AED/day</span>
                <span class="badge bg-label-secondary">28+ days: {{ number_format((float) ($contract->car->price_per_day_long ?? $contract->car->price_per_day_mid ?? $contract->car->price_per_day_short), 2) }} AED/day</span>
            </div>
        </div>
    </div>

    @error('contract')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('amendment')<div class="alert alert-danger">{{ $message }}</div>@enderror
    @error('pricing')<div class="alert alert-danger">{{ $message }}</div>@enderror

    @if ($this->canRequestExtension())
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <h5 class="mb-3">New extension request</h5>
                <form wire:submit="request" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">New planned return</label>
                        <input wire:model="newReturnAt" type="datetime-local" class="form-control">
                        @error('newReturnAt')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Billing policy</label>
                        <select wire:model="pricingPolicy" class="form-select">
                            <option value="daily_ceiling">Daily ceiling — any partial day rounds up</option>
                            <option value="hourly">Hourly</option>
                            <option value="prorated_daily">Prorated daily</option>
                            <option value="grace_then_daily">2-hour grace, then daily</option>
                        </select>
                        @error('pricingPolicy')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Rental rate source</label>
                        <select wire:model="rateSource" class="form-select">
                            @if (is_numeric($contract->used_daily_rate) && (float) $contract->used_daily_rate > 0)
                                <option value="contract_rate">Contract rate — {{ number_format((float) $contract->used_daily_rate, 2) }} AED/day</option>
                            @endif
                            <option value="current_tariff">Current vehicle tariff — based on extension length</option>
                        </select>
                        <small class="text-muted">Contract rate is the safe default. Current tariff uses today's configured vehicle price.</small>
                        @error('rateSource')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-5"><label class="form-label">Reason</label><input wire:model="reason" class="form-control"></div>
                    <div class="col-md-7"><label class="form-label">Notes</label><textarea wire:model="notes" class="form-control" rows="2"></textarea></div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" wire:click="preview" wire:loading.attr="disabled" class="btn btn-outline-primary"><i class="bi bi-calculator me-1"></i>Preview calculation</button>
                        <button wire:loading.attr="disabled" class="btn btn-primary" @disabled($quote === [])>Request reviewed extension</button>
                        @if ($quote === [])<span class="small text-muted">Preview is required before submission.</span>@endif
                    </div>
                    @error('quote')<div class="col-12"><div class="alert alert-warning mb-0">{{ $message }}</div></div>@enderror
                </form>

                @if ($quote !== [])
                    @php
                        $contractRate = $quote['contract_daily_rate'] ?? null;
                        $currentRate = $quote['current_daily_rate'] ?? null;
                        $usesContractRate = ($quote['rate_source'] ?? null) === \App\Services\RentalPricingService::RATE_SOURCE_CONTRACT;
                    @endphp
                    <div class="mt-4 border rounded overflow-hidden">
                        <div class="p-3 {{ !empty($quote['rate_changed']) ? 'bg-warning-subtle' : 'bg-light' }} border-bottom">
                            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $usesContractRate ? 'Contract rate selected' : 'Current vehicle tariff selected' }}</div>
                                    <div class="small">Effective rental rate: <strong>{{ number_format((float) $quote['effective_daily_rate'], 2) }} AED/day</strong></div>
                                </div>
                                @if (!empty($quote['rate_changed']))
                                    <span class="badge bg-warning text-dark fs-6">Rate difference requires attention</span>
                                @else
                                    <span class="badge bg-success fs-6">Rates match</span>
                                @endif
                            </div>
                            @if (!empty($quote['rate_changed']))
                                <div class="alert alert-warning mt-3 mb-0 py-2">
                                    Contract rate is <strong>{{ number_format((float) $contractRate, 2) }} AED/day</strong>; current tariff for this extension is
                                    <strong>{{ number_format((float) $currentRate, 2) }} AED/day</strong>. This quote uses
                                    <strong>{{ $usesContractRate ? 'the contract rate' : 'the current tariff' }}</strong>.
                                </div>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light"><tr><th>Item</th><th>Calculation</th><th class="text-end">Amount before VAT</th></tr></thead>
                                <tbody>
                                    @foreach ($quote['items'] as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item['title'] }}</td>
                                            <td>{{ number_format((float) $item['quantity'], 3) }} {{ \Illuminate\Support\Str::headline($item['unit']) }} × {{ number_format((float) $item['unit_price'], 2) }} AED</td>
                                            <td class="text-end">{{ number_format((float) $item['amount'], 2) }} AED</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="2" class="text-end">Subtotal</th><th class="text-end">{{ number_format((float) $quote['subtotal'], 2) }} AED</th></tr>
                                    <tr><th colspan="2" class="text-end">VAT (5%)</th><th class="text-end">{{ number_format((float) $quote['tax'], 2) }} AED</th></tr>
                                    <tr class="table-primary"><th colspan="2" class="text-end fs-6">Total added to contract</th><th class="text-end fs-5">{{ number_format((float) $quote['total'], 2) }} AED</th></tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @elseif (!in_array($contract->current_status, \App\Models\Contract::AMENDABLE_STATUSES, true))
        <div class="alert alert-warning">Only a delivered rental that has not yet been returned can be extended.</div>
    @else
        <div class="alert alert-info">Resolve the pending extension before creating another request.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5>Amendment history</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th># / Period</th><th>Rate used</th><th>Calculation</th><th>Status</th><th class="text-end">Added total</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($contract->amendments as $amendment)
                            @php
                                $snapshot = (array) $amendment->pricing_snapshot;
                                $rentalItem = collect((array) ($snapshot['items'] ?? []))->firstWhere('code', 'extension_rental');
                                $legacyDailyRate = (float) ($rentalItem['unit_price'] ?? 0) * (($rentalItem['unit'] ?? null) === 'hour' ? 24 : 1);
                                $effectiveRate = (float) ($snapshot['effective_daily_rate'] ?? $legacyDailyRate);
                                $contractRateAtQuote = $snapshot['contract_daily_rate'] ?? $contract->used_daily_rate;
                                $explicitRateSource = $snapshot['rate_source'] ?? null;
                                $usesContractRate = $explicitRateSource === \App\Services\RentalPricingService::RATE_SOURCE_CONTRACT
                                    || ($explicitRateSource === null && is_numeric($contractRateAtQuote) && abs($effectiveRate - (float) $contractRateAtQuote) < 0.005);
                                $rateDiffers = is_numeric($contractRateAtQuote) && abs($effectiveRate - (float) $contractRateAtQuote) > 0.005;
                            @endphp
                            <tr>
                                <td><div class="fw-semibold">#{{ $amendment->sequence_no }}</div><small>{{ $amendment->extension_start_at?->format('Y-m-d H:i') }} → {{ $amendment->extension_end_at?->format('Y-m-d H:i') }}</small></td>
                                <td>
                                    <div class="fw-semibold">{{ number_format($effectiveRate, 2) }} AED/day</div>
                                    <span class="badge {{ $usesContractRate ? 'bg-label-primary' : 'bg-label-warning' }}">{{ $usesContractRate ? 'Contract rate' : ($explicitRateSource ? 'Current tariff' : 'Current tariff · legacy') }}</span>
                                    @if ($rateDiffers)<small class="text-warning d-block mt-1">Contract: {{ number_format((float) $contractRateAtQuote, 2) }} AED/day</small>@endif
                                </td>
                                <td>
                                    @if ($rentalItem)
                                        {{ number_format((float) $rentalItem['quantity'], 3) }} {{ \Illuminate\Support\Str::headline($rentalItem['unit']) }} × {{ number_format((float) $rentalItem['unit_price'], 2) }}
                                        = <strong>{{ number_format((float) $rentalItem['amount'], 2) }} AED</strong>
                                        <small class="text-muted d-block">+ VAT {{ number_format((float) $amendment->tax_amount, 2) }} AED</small>
                                    @else
                                        <span class="text-muted">Pricing details unavailable</span>
                                    @endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::headline($amendment->status) }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $amendment->total_amount, 2) }} {{ $amendment->currency }}</td>
                                <td>
                                    @if ($amendment->isPending())
                                        <div class="d-flex flex-wrap gap-1">
                                            <button wire:click="approve({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-success">Approve</button>
                                            <button wire:click="reject({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-outline-danger">Reject</button>
                                            <button wire:click="cancel({{ $amendment->id }})" wire:loading.attr="disabled" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No amendments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
