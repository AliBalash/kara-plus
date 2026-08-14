<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Debt Transfer</h4>

    <x-detail-rental-request-tabs :contract-id="$contract->id" />

    <div class="row g-4 mt-1">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1">Current outstanding</p>
                    <h2 class="fw-bold mb-2 {{ $currentOutstanding > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($currentOutstanding, 2) }} AED
                    </h2>
                    <p class="text-muted small mb-3">Include unpaid fees, fines, extras and prior transfers.</p>

                    <div class="d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="progress" style="height: 6px;">
                                @php
                                    $progress = $currentOutstanding > 0 ? min($currentOutstanding / 5000 * 100, 100) : 0;
                                @endphp
                                <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $progress }}%"></div>
                            </div>
                        </div>
                        <span class="text-muted small">Exposure</span>
                    </div>

                    <hr>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2"><strong>Contract:</strong> <x-contract-reference :contract="$contract" /></li>
                        <li class="mb-2"><strong>Customer:</strong> {{ $contract->customer?->fullName() }}</li>
                        <li>
                            <strong>Vehicle:</strong> {{ $contract->car?->fullName() ?? '—' }}
                            <x-car-ownership-badge :car="$contract->car" class="ms-2" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                    <div>
                        <h5 class="mb-1">Transfer balance</h5>
                        <span class="text-muted small">Move remaining debt or credit between contracts of the same customer.</span>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if (empty($contractsList))
                        <div class="alert alert-info">
                            Other contracts were not found for this customer, therefore the balance cannot be transferred yet.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('rental-requests.balance-transfer.store', $contract->id) }}" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label d-block">Transfer mode</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="mode" id="mode-send" value="send"
                                    @checked(old('mode', $transferForm['mode']) === 'send')>
                                <label class="btn btn-outline-primary" for="mode-send">Send from this contract</label>

                                <input type="radio" class="btn-check" name="mode" id="mode-receive" value="receive"
                                    @checked(old('mode', $transferForm['mode']) === 'receive')>
                                <label class="btn btn-outline-secondary" for="mode-receive">Receive from another contract</label>
                            </div>
                            @error('mode')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Target contract</label>
                            <select class="form-select" name="target_contract_id"
                                @disabled(empty($contractsList))>
                                <option value="">Select contract...</option>
                                @foreach ($contractsList as $option)
                                    <option value="{{ $option['id'] }}" @selected((string) old('target_contract_id', $transferForm['target_contract_id']) === (string) $option['id'])>
                                        #{{ $option['id'] }} · {{ $option['label'] }}
                                        ({{ number_format($option['outstanding'], 2) }} AED)
                                    </option>
                                @endforeach
                            </select>
                            @error('target_contract_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Amount (AED)</label>
                            <input type="number" step="0.01" class="form-control" placeholder="0.00"
                                name="amount" value="{{ old('amount') }}" />
                            @error('amount')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Reference</label>
                            <input type="text" class="form-control" placeholder="Finance reference"
                                name="reference" value="{{ old('reference') }}">
                            @error('reference')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" rows="3" name="notes"
                                placeholder="Explain why this transfer is required">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary" @disabled(empty($contractsList))>
                                Save transfer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h5 class="mb-1">Transfer history</h5>
                <span class="text-muted small">Track every movement between contracts.</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Direction</th>
                        <th>Amount</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Reference</th>
                        <th>Metadata</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td>{{ $transfer['id'] }}</td>
                            <td>
                                <span class="badge {{ $transfer['direction'] === 'outgoing' ? 'bg-label-warning' : 'bg-label-success' }}">
                                    {{ ucfirst($transfer['direction']) }}
                                </span>
                            </td>
                            <td class="fw-semibold">
                                {{ $transfer['direction'] === 'outgoing' ? '-' : '+' }}{{ number_format($transfer['amount'], 2) }} AED
                            </td>
                            <td>
                                @if ($transfer['from_contract'])
                                    #{{ $transfer['from_contract']->id }} · {{ $transfer['from_contract']->car?->fullName() ?? 'Vehicle' }}
                                    <x-car-ownership-badge :car="$transfer['from_contract']->car" class="ms-2" />
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($transfer['to_contract'])
                                    #{{ $transfer['to_contract']->id }} · {{ $transfer['to_contract']->car?->fullName() ?? 'Vehicle' }}
                                    <x-car-ownership-badge :car="$transfer['to_contract']->car" class="ms-2" />
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $transfer['reference'] ?? '—' }}</td>
                            <td>
                                @if (!empty($transfer['meta']))
                                    <ul class="list-unstyled mb-0 small text-muted">
                                        @foreach ($transfer['meta'] as $key => $value)
                                            <li><strong>{{ $key }}:</strong> {{ $value }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-muted">{{ $transfer['transferred_at'] }}</div>
                                <div class="small">{{ $transfer['created_by'] ?? 'System' }}</div>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('rental-requests.balance-transfer.destroy', [$contract->id, $transfer['id']]) }}" class="d-inline"
                                    onsubmit="return confirm('Remove this transfer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No transfers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('styles')
        <style>
            .btn-icon {
                width: 36px;
                height: 36px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    @endpush
</div>
