<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Payments /</span> Edit Payment #{{ $payment->id }}
    </h4>
    <div class="card">
        <div class="card-header">Payment Details</div>
        <div class="card-body">
            <form wire:submit.prevent="updatePayment">
                <div class="row g-3">
                    <div class="col-md-4" data-validation-field="payment_type">
                        <label class="form-label">Payment Type <span class="badge bg-danger-subtle text-danger ms-2">Required</span></label>
                        <select class="form-select" wire:model="payment_type">
                            @foreach ($this->paymentTypeOptions as $value => $label)
                                @php
                                    $autoOption = $value === 'salik_other_revenue';
                                    $skipOption = $autoOption && $payment_type !== 'salik_other_revenue';
                                @endphp

                                @if ($skipOption)
                                    @continue
                                @endif

                                <option value="{{ $value }}"
                                    @if ($autoOption && $payment_type !== 'salik_other_revenue') disabled @endif>
                                    {{ $label }}@if ($autoOption)
                                        (Auto)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('payment_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4" data-validation-field="amount">
                        <label class="form-label">
                            Amount
                            @if (in_array($payment_type, ['salik_4_aed', 'salik_6_aed']))
                                <span class="badge bg-info-subtle text-info ms-2">Auto</span>
                            @elseif (!blank($payment_type))
                                <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            @endif
                        </label>
                        <input type="number" class="form-control" step="0.01" wire:model="amount"
                            placeholder="0.00"
                            @disabled(blank($payment_type) || in_array($payment_type, ['salik_4_aed', 'salik_6_aed']))>
                        @if (blank($payment_type))
                            <small class="text-muted">Select a payment type to enter the amount.</small>
                        @elseif (in_array($payment_type, ['salik_4_aed', 'salik_6_aed']))
                            @php
                                $salikUnit = $payment_type === 'salik_4_aed' ? 4 : 6;
                            @endphp
                            <small class="text-muted">Calculated automatically: {{ (int) ($salik_trip_count ?: 0) }} trips ×
                                {{ $salikUnit }} AED = {{ number_format($amount ?? 0, 2) }} AED</small>
                        @endif
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4" data-validation-field="currency">
                        <label class="form-label">Currency <span class="badge bg-danger-subtle text-danger ms-2">Required</span></label>
                        <select class="form-select" wire:model.live="currency"
                            @disabled(blank($payment_type) || in_array($payment_type, ['salik', 'salik_4_aed', 'salik_6_aed']))>
                            <option value="IRR">Rial</option>
                            <option value="USD">Dollar</option>
                            <option value="AED">Dirham</option>
                            <option value="EUR">Euro</option>
                            <option value="SAR">Saudi Riyal</option>
                            <option value="OMR">Omani Rial</option>
                        </select>
                        @if (in_array($payment_type, ['salik', 'salik_4_aed', 'salik_6_aed']))
                            <small class="text-muted">Salik payments are always billed in AED.</small>
                        @endif
                        @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if ($currency !== 'AED')
                        <div class="col-md-4">
                            <label class="form-label">Exchange Rate (to AED)</label>
                            <input type="number" step="0.0001" class="form-control" wire:model="rate">
                            @error('rate') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    @if (in_array($payment_type, ['salik_4_aed', 'salik_6_aed']))
                        <div class="col-md-4" data-validation-field="salik_trip_count">
                            <label class="form-label">
                                @switch($payment_type)
                                    @case('salik_4_aed')
                                        Salik (4 AED) Trips
                                        @break
                                    @case('salik_6_aed')
                                        Salik (6 AED) Trips
                                        @break
                                @endswitch
                                <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <input type="number" min="0" class="form-control" wire:model.lazy="salik_trip_count">
                            <small class="text-muted">
                                Amount will be recalculated as trips ×
                                @switch($payment_type)
                                    @case('salik_4_aed')
                                        4 AED
                                        @break
                                    @case('salik_6_aed')
                                        6 AED
                                        @break
                                @endswitch
                            </small>
                            @error('salik_trip_count') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Other Revenue (Auto)</label>
                            <input type="text" class="form-control" value="{{ number_format($salik_other_revenue_preview, 2) }} AED" disabled>
                            <small class="text-muted">Added automatically at 1 AED per salik trip.</small>
                        </div>
                    @elseif ($payment_type === 'salik')
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                Legacy salik payments keep their manually entered amount.
                            </div>
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" wire:model="payment_method">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                            <option value="ticket">Ticket</option>
                        </select>
                        @error('payment_method') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Date</label>
                        <input type="date" class="form-control" wire:model="payment_date">
                        @error('payment_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Refundable?</label>
                        <select class="form-select" wire:model="is_refundable">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                        @error('is_refundable') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Replace Receipt (optional)</label>
                        <input type="file" class="form-control" wire:model="receipt">
                        @error('receipt') <span class="text-danger">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="receipt" class="text-primary mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Uploading...
                        </div>
                    </div>

                    @if ($existingReceipt)
                        <div class="col-md-6">
                            <label class="form-label">Current Receipt</label>
                            <div>
                                <a href="{{ asset('storage/' . ltrim($existingReceipt, '/')) }}" target="_blank">View current receipt</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save Changes</button>
                    <a href="{{ route('rental-requests.payment', [$contract?->id ?? $payment->contract_id, $payment->customer_id]) }}"
                        class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
