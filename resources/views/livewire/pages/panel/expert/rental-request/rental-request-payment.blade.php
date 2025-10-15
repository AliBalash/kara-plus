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
                            @foreach ($this->paymentTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
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
                            <option value="ticket">Ticket</option>
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
        $summaryMetrics = [
            [
                'key' => 'total',
                'label' => 'Total Price',
                'value' => $totalPrice,
                'icon' => 'bi-currency-exchange',
                'accent' => 'bg-primary text-white',
                'value_class' => 'text-primary',
            ],
            [
                'key' => 'paid',
                'label' => 'Paid (after refunds)',
                'value' => $effectivePaid,
                'icon' => 'bi-cash-stack',
                'accent' => 'bg-success text-white',
                'value_class' => 'text-success',
            ],
            [
                'key' => 'payment_back',
                'label' => 'Payment Back',
                'value' => $payment_back,
                'icon' => 'bi-arrow-counterclockwise',
                'accent' => 'bg-warning text-dark',
                'value_class' => 'text-warning',
            ],
            [
                'key' => 'remaining',
                'label' => 'Remaining Balance',
                'value' => $remainingBalance,
                'icon' => 'bi-graph-down',
                'accent' => 'bg-dark text-white',
                'value_class' => $remainingBalance <= 0 ? 'text-success' : 'text-danger',
            ],
        ];

        $subtractItems = [
            [
                'label' => 'Paid (after refunds)',
                'value' => $effectivePaid,
                'detail' => [
                    'collected' => $rentalPaid,
                    'payment_back' => $payment_back,
                ],
            ],
            [
                'label' => 'Discounts',
                'value' => $discounts,
            ],
            [
                'label' => 'Security Deposit',
                'value' => $security_deposit,
            ],
        ];

        $additionItems = [
            ['label' => 'Fines', 'value' => $finePaid],
            ['label' => 'Salik', 'value' => $salik],
            ['label' => 'Carwash', 'value' => $carwash],
            ['label' => 'Fuel', 'value' => $fuel],
            ['label' => 'Parking', 'value' => $parkingPaid],
            ['label' => 'Damage', 'value' => $damagePaid],
        ];
    @endphp

    <div class="row g-3 mt-1">
        @foreach ($summaryMetrics as $metric)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden transform">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase small fw-semibold">{{ $metric['label'] }}</div>
                                <div class="fs-3 fw-bold mt-2 {{ $metric['value_class'] }}">
                                    {{ number_format($metric['value'], 2) }}
                                </div>
                            </div>
                            <span
                                class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $metric['accent'] }} shadow-sm"
                                style="width: 48px; height: 48px;">
                                <i class="bi {{ $metric['icon'] }} fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm mt-4 formula-card">
        <div class="card-header d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calculator fs-5 text-primary"></i>
                <h6 class="mb-0">Remaining Balance Formula</h6>
            </div>
            <span class="badge bg-dark text-white px-3 py-2 fs-6">Remaining: {{ number_format($remainingBalance, 2) }} AED</span>
        </div>
        <div class="card-body">
            <div class="formula-expression mb-4">
                <span class="formula-equal">Remaining</span>
                <span class="formula-operator">=</span>
                <span class="formula-term term-total">
                    <span class="formula-label">Total Price</span>
                    <span class="formula-value">{{ number_format($totalPrice, 2) }}</span>
                </span>
                <span class="formula-operator">−</span>
                <span class="formula-brace formula-brace-open">(</span>
                <span class="formula-group">
                    @foreach ($subtractItems as $item)
                        <span class="formula-term term-subtract">
                            <span class="formula-label">{{ $item['label'] }}</span>
                            <span class="formula-value">{{ number_format($item['value'], 2) }}</span>
                            @if (!empty($item['detail']))
                                <span class="formula-subtext">Collected {{ number_format($item['detail']['collected'], 2) }} − Payment Back {{ number_format($item['detail']['payment_back'], 2) }}</span>
                            @endif
                        </span>
                        @if (! $loop->last)
                            <span class="formula-operator">+</span>
                        @endif
                    @endforeach
                </span>
                <span class="formula-brace formula-brace-close">)</span>
                <span class="formula-operator">+</span>
                <span class="formula-brace formula-brace-open">{</span>
                <span class="formula-group">
                    @foreach ($additionItems as $item)
                        <span class="formula-term term-add">
                            <span class="formula-label">{{ $item['label'] }}</span>
                            <span class="formula-value">{{ number_format($item['value'], 2) }}</span>
                        </span>
                        @if (! $loop->last)
                            <span class="formula-operator">+</span>
                        @endif
                    @endforeach
                </span>
                <span class="formula-brace formula-brace-close">}</span>
                <span class="formula-operator">=</span>
                <span class="formula-term term-result">
                    <span class="formula-label">Balance</span>
                    <span class="formula-value">{{ number_format($remainingBalance, 2) }}</span>
                </span>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="formula-panel negative">
                        <div class="formula-panel-title text-danger">
                            <i class="bi bi-dash-circle"></i>
                            Subtractions from Total
                        </div>
                        @foreach ($subtractItems as $item)
                            <div class="formula-panel-item">
                                <div>
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    @if (!empty($item['detail']))
                                        <div class="formula-panel-detail">
                                            <span class="text-success me-3">Collected: {{ number_format($item['detail']['collected'], 2) }}</span>
                                            <span class="text-warning">Payment Back: {{ number_format($item['detail']['payment_back'], 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                                <span class="formula-panel-value text-danger">−{{ number_format($item['value'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="formula-panel positive">
                        <div class="formula-panel-title text-success">
                            <i class="bi bi-plus-circle"></i>
                            Additions to Total
                        </div>
                        @foreach ($additionItems as $item)
                            <div class="formula-panel-item">
                                <div class="fw-semibold">{{ $item['label'] }}</div>
                                <span class="formula-panel-value text-success">+{{ number_format($item['value'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>









    <!-- Existing Payments -->
    <div class="table-responsive my-3">
        <h5>Existing Payments</h5>
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
                    <th>Actions</th>

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
                                {{ ucwords(str_replace('_', ' ', $payment->payment_type)) }}
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
                        <td>
                            <a class="btn btn-sm btn-outline-primary me-2"
                                href="{{ route('payments.edit', $payment->id) }}">
                                Edit
                            </a>
                            <button class="btn btn-sm btn-outline-danger"
                                onclick="if(confirm('Delete this payment?')) { @this.deletePayment({{ $payment->id }}) }">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center">No payments found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
    <style>
        .transform:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .formula-expression {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem;
            font-size: 1.05rem;
        }

        .formula-term {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 0.65rem 0.9rem;
            border-radius: 0.85rem;
            min-width: 150px;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.04);
            background: #f8f9fa;
        }

        .formula-term.term-total {
            background: linear-gradient(135deg, #4c6ef5, #845ef7);
            color: #fff;
            box-shadow: 0 0.65rem 1.2rem rgba(76, 110, 245, 0.25);
        }

        .formula-term.term-subtract {
            background: #fff3cd;
            border: 1px solid #ffe69c;
        }

        .formula-term.term-add {
            background: #e6fcf5;
            border: 1px solid #c3fae8;
        }

        .formula-term.term-result {
            background: #212529;
            color: #fff;
        }

        .formula-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .formula-value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .formula-subtext {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .formula-operator {
            font-weight: 600;
            font-size: 1.35rem;
            color: #495057;
        }

        .formula-brace {
            font-size: 2.4rem;
            line-height: 1;
            color: #adb5bd;
        }

        .formula-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .formula-panel {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.03);
        }

        .formula-panel.negative {
            background: #fff7e6;
            border-left: 4px solid #f59f00;
        }

        .formula-panel.positive {
            background: #ecf9f1;
            border-left: 4px solid #12b886;
        }

        .formula-panel-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-size: 0.78rem;
            margin-bottom: 1.2rem;
        }

        .formula-panel-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .formula-panel-item:last-child {
            margin-bottom: 0;
        }

        .formula-panel-value {
            font-weight: 600;
            font-size: 1.05rem;
        }

        .formula-panel-detail {
            font-size: 0.78rem;
            margin-top: 0.4rem;
        }

        @media (max-width: 576px) {
            .formula-term {
                min-width: 140px;
            }

            .formula-panel {
                padding: 1.1rem;
            }
        }
    </style>
@endpush
