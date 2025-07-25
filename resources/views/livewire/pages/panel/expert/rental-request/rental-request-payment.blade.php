<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Rental Request /</span> Payment Information
    </h4>

    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif


    <x-detail-rental-request-tabs :contract-id="$contractId" />


    <div class="card">
        <h5 class="card-header">Make a Payment</h5>
        <div class="card-body">
            <form wire:submit.prevent="submitPayment">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" wire:model="amount" step="0.01" placeholder="$">
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-control" wire:model.live="currency">
                            <option value="IRR">Rial</option>
                            <option value="USD">Dollar</option>
                            <option value="AED">Dirham</option>
                        </select>
                        @error('currency')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    @if ($currency !== 'AED')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Exchange Rate (to AED)</label>
                            <div class="input-group">
                                <input type="number" step="0.0001" class="form-control" required wire:model="rate">
                            </div>
                            @error('rate')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif



                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Type</label>
                        <select class="form-control" wire:model="payment_type">
                            <option value="">Select Payment Type</option>
                            <option value="rental_fee">Rental Fee</option>
                            <option value="prepaid_fine">Prepaid Fine</option>
                            <option value="toll">Toll</option>
                            <option value="fine">Fine</option>
                        </select>
                        @error('payment_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" class="form-control" wire:model="payment_date">
                        @error('payment_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Refundable?</label>
                        <select class="form-control" wire:model="is_refundable">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Receipt Upload (Optional)</label>
                        <input type="file" class="form-control" wire:model="receipt">
                        @error('receipt')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror

                        @if ($receipt)
                            <div class="mt-2">
                                <strong>Preview:</strong><br>
                                <img src="{{ $receipt->temporaryUrl() }}" alt="Receipt Preview" class="img-thumbnail"
                                    width="200">
                            </div>
                        @endif
                    </div>
                </div>



                <button type="submit" class="btn btn-primary mt-3">Submit Payment</button>
            </form>
        </div>
    </div>

    <!-- Payment Overview -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Payment Overview</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="alert alert-info text-center">
                        <strong>Total Price:</strong> <span>{{ number_format($totalPrice, 2) }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-success text-center">
                        <strong>Total Paid:</strong> <span>{{ number_format($rentalPaid, 2) }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-warning text-center">
                        <strong>Remaining Balance:</strong> <span>{{ number_format($remainingBalance, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Payments -->
    <h5>Existing Payments</h5>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Payment Type</th>
                    <th>Refundable</th>
                    <th>Created By</th>
                    <th>Payment Date</th>
                    <th>Receipt</th>

                </tr>
            </thead>
            <tbody>
                @forelse ($existingPayments as $payment)
                    <tr>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->currency }}
                            {{ $payment->currency !== 'AED' ? '( ' . $payment->rate . ' )' : null }}</td>
                        <td>{{ ucfirst($payment->payment_type) }}</td>
                        <td>{{ $payment->is_refundable == true ? 'Yes' : 'No' }}</td>
                        <td>{{ $payment->user?->shortName() ?? '—' }}</td>
                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                        <td>
                            @if ($payment->receipt)
                                <a href="{{ asset('storage/') . '/' . $payment->receipt }}" target="_blank">View</a>
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No payments found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>




</div>
