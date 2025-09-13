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
                            <option value="EUR">Euro</option>
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
                            <option value="security_deposit">Security deposit</option>
                            <option value="salik">Salik</option>
                            <option value="fine">Fine</option>
                            <option value="discount">Discount</option>

                        </select>
                        @error('payment_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" wire:model="payment_method">
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                        </select>
                        @error('payment_method')
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

                        {{-- لودینگ برای آپلود فایل --}}
                        <div wire:loading wire:target="receipt" class="text-primary mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Uploading...
                        </div>

                        @if ($receipt)
                            <div class="mt-2">
                                <strong>Preview:</strong><br>
                                <img src="{{ $receipt->temporaryUrl() }}" alt="Receipt Preview" class="img-thumbnail"
                                    width="200">
                            </div>
                        @endif
                    </div>
                </div>

                {{-- دکمه سابمیت --}}
                <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled"
                    wire:target="receipt,submitPayment">
                    Submit Payment
                </button>

            </form>


            @if (session()->has('message-deposite'))
                <div class="alert alert-success mt-3">
                    {{ session('message') }}
                </div>
            @endif

            <form class="my-5" wire:submit.prevent="submitDeposit">

                <div class="col-md-12 mb-3">
                    <label class="form-label">Security Deposit Details (optional)</label>
                    <textarea class="form-control" wire:model.defer="security_note" rows="3"
                        placeholder="E.g., Deposit of 1000 AED, refundable after inspection"></textarea>
                </div>

                @if (!empty($contractMeta['security_deposit_note']))
                    <div class="alert alert-secondary mt-4">
                        <strong>Security Deposit:</strong><br>
                        {{ $contractMeta['security_deposit_note'] }}
                    </div>
                @endif
                <button type="submit" class="btn btn-dark mt-3">Submit Security Deposit</button>

            </form>

        </div>
    </div>

    <!-- Payment Overview -->
    @php
        $metrics = [
            [
                'key' => 'total',
                'label' => 'Total Price',
                'value' => $totalPrice,
                'icon' => 'bi-currency-exchange',
                'color' => 'primary',
            ],
            [
                'key' => 'paid',
                'label' => 'Paid',
                'value' => $rentalPaid,
                'icon' => 'bi-cash-stack',
                'color' => 'success',
            ],
            [
                'key' => 'discount',
                'label' => 'Discounts',
                'value' => $discounts,
                'icon' => 'bi-percent',
                'color' => 'warning',
            ],
            [
                'key' => 'fine',
                'label' => 'Fines',
                'value' => $finePaid,
                'icon' => 'bi-exclamation-triangle-fill',
                'color' => 'danger',
            ],
            [
                'key' => 'security_deposit',
                'label' => 'Security deposit',
                'value' => $security_deposit,
                'icon' => 'bi-wallet2',
                'color' => 'info',
            ],
            ['key' => 'salik', 'label' => 'Salik', 'value' => $salik, 'icon' => 'bi-coin', 'color' => 'secondary'],
            [
                'key' => 'remaining',
                'label' => 'Remaining',
                'value' => $remainingBalance,
                'icon' => 'bi-hourglass-split',
                'color' => 'dark',
            ],
        ];
    @endphp

    <div class="row g-3 mt-1 mb-4">
        @foreach ($metrics as $m)
            @php
                // کارت remaining بزرگ‌تر باشد
                $colClass = $m['key'] === 'remaining' ? 'col-12 col-md-6 col-lg-12' : 'col-6 col-md-4 col-lg-2';
            @endphp

            <div class="{{ $colClass }}">
                <div class="card shadow-sm border-0 text-{{ $m['color'] }} h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-start">
                        <div class="d-flex align-items-center mb-2 w-100">
                            <i class="bi {{ $m['icon'] }} fs-2 me-3"></i>
                            <div>
                                <div class="h5 mb-1">{{ $m['label'] }}</div>
                                @if ($m['key'] === 'remaining')
                                    <div class="fs-5 lh-sm">
                                        <div>
                                            <span class="text-primary">{{ number_format($totalPrice, 2) }}</span>
                                            – (
                                            <span class="text-success">{{ number_format($rentalPaid, 2) }}</span> +
                                            <span class="text-warning">{{ number_format($discounts, 2) }}</span> +
                                            <span class="text-info">{{ number_format($security_deposit, 2) }}</span>
                                            )
                                            + <span class="text-danger">{{ number_format($finePaid, 2) }}</span>
                                            + <span class="text-secondary">{{ number_format($salik, 2) }}</span>
                                            <span class="fw-bold fs-3 mt-2">
                                                = {{ number_format($remainingBalance, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    {{-- مقدار ساده --}}
                                    <div class="fs-4 fw-bold">{{ number_format($m['value'], 2) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>









    <!-- Existing Payments -->
    <h5>Existing Payments</h5>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Payment Type</th>
                    <th>Method</th>
                    <th>Refundable</th>
                    <th>Created By</th>
                    <th>Payment Date</th>
                    <th>Receipt</th>

                </tr>
            </thead>
            <tbody>
                @forelse ($existingPayments as $payment)
                    <tr>
                        <td>{{ $payment->id }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->currency }}
                            {{ $payment->currency !== 'AED' ? '( ' . $payment->rate . ' )' : null }}</td>
                        <td>
                            @if ($payment->payment_type === 'security_deposit')
                                Security deposit
                            @elseif ($payment->payment_type === 'toll')
                                Salik
                            @else
                                {{ ucfirst($payment->payment_type) }}
                            @endif
                        </td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
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

@push('styles')
    <style>
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
@endpush
