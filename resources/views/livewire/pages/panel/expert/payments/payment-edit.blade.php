<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Payments /</span> Edit Payment #{{ $payment->id }}
    </h4>
    <div class="card">
        <div class="card-header">Payment Details</div>
        <div class="card-body">
            <form wire:submit.prevent="updatePayment">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" step="0.01" wire:model="amount">
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <select class="form-select" wire:model.live="currency">
                            <option value="IRR">Rial</option>
                            <option value="USD">Dollar</option>
                            <option value="AED">Dirham</option>
                            <option value="EUR">Euro</option>
                        </select>
                        @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if ($currency !== 'AED')
                        <div class="col-md-4">
                            <label class="form-label">Exchange Rate (to AED)</label>
                            <input type="number" step="0.0001" class="form-control" wire:model="rate">
                            @error('rate') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-md-4">
                        <label class="form-label">Payment Type</label>
                        <select class="form-select" wire:model="payment_type">
                            @foreach ($this->paymentTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

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
