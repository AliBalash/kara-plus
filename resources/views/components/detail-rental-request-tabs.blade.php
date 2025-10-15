@php
    $isDriver = auth()->user()?->hasRole('driver');
    $contractId = $contract->id ?? null;
    $customerId = $contract->customer->id ?? null;

    $tabs = [
        [
            'key' => 'info',
            'label' => 'Rental Information',
            'icon' => 'bxs-info-square',
            'routes' => ['rental-requests.edit'],
            'route' => 'rental-requests.edit',
            'params' => $contractId ? [$contractId] : null,
            'status' => null,
            'staff' => true,
            'driver' => true,
        ],
        [
            'key' => 'customer',
            'label' => 'Customer Document',
            'icon' => 'bx-file',
            'routes' => ['customer.documents'],
            'route' => 'customer.documents',
            'params' => ($contractId && $customerId) ? [$contractId, $customerId] : null,
            'status' => $customerDocumentsCompleted ?? null,
            'staff' => true,
            'driver' => false,
        ],
        [
            'key' => 'pickup',
            'label' => 'Delivery Document',
            'icon' => 'bx-upload',
            'routes' => ['rental-requests.pickup-document'],
            'route' => 'rental-requests.pickup-document',
            'params' => $contractId ? [$contractId] : null,
            'status' => $pickupDocumentsCompleted ?? null,
            'staff' => true,
            'driver' => true,
        ],
        [
            'key' => 'return',
            'label' => 'Return Document',
            'icon' => 'bx-download',
            'routes' => ['rental-requests.return-document'],
            'route' => 'rental-requests.return-document',
            'params' => $contractId ? [$contractId] : null,
            'status' => $returnDocumentsCompleted ?? null,
            'staff' => true,
            'driver' => true,
        ],
        [
            'key' => 'payment',
            'label' => 'Payment',
            'icon' => 'bx-money',
            'routes' => ['rental-requests.payment'],
            'route' => 'rental-requests.payment',
            'params' => ($contractId && $customerId) ? [$contractId, $customerId] : null,
            'status' => $paymentsExist ?? null,
            'staff' => true,
            'driver' => false,
        ],
        [
            'key' => 'history',
            'label' => 'Status & History',
            'icon' => 'bx-history',
            'routes' => ['rental-requests.history'],
            'route' => 'rental-requests.history',
            'params' => $contractId ? [$contractId] : null,
            'status' => null,
            'staff' => true,
            'driver' => false,
        ],
    ];

    $tabs = array_filter($tabs, fn($tab) => $isDriver ? ($tab['driver'] ?? false) : ($tab['staff'] ?? true));
@endphp

<div class="rental-steps my-2" role="tablist">
    @foreach ($tabs as $tab)
        @php
            $isActive = isset($tab['routes']) && request()->routeIs(...$tab['routes']);
            $isDisabled = empty($tab['params']);
            $status = $tab['status'];
        @endphp

        <a href="{{ $isDisabled ? '#' : route($tab['route'], $tab['params']) }}"
            class="rental-step {{ $isActive ? 'is-active' : '' }} {{ $status === true ? 'is-complete' : '' }} {{ $isDisabled ? 'is-disabled' : '' }}"
            role="tab"
            @if ($isActive) aria-current="true" @endif
            @if ($isDisabled) aria-disabled="true" @endif>

            <span class="step-icon">
                <i class="bx {{ $tab['icon'] }}"></i>
            </span>

            <span class="step-content">
                <span class="step-label">{{ $tab['label'] }}</span>
                @if (!is_null($status))
                    <span class="step-status {{ $status ? 'status-complete' : 'status-pending' }}">
                        {{ $status ? 'Completed' : 'Pending' }}
                    </span>
                @endif
            </span>

            @if ($isActive)
                <span class="step-marker" aria-hidden="true"></span>
            @endif
        </a>
    @endforeach
</div>

@once
    @push('styles')
        <style>
            .rental-steps {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 0.75rem;
            }

            .rental-step {
                position: relative;
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 0.85rem 1rem;
                border: 1px solid #e0e6ef;
                border-radius: 1rem;
                background: #fff;
                color: #435167;
                text-decoration: none;
                min-height: 62px;
                transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
            }

            .rental-step:hover:not(.is-active):not(.is-disabled) {
                border-color: #cdd7e6;
                box-shadow: 0 8px 20px rgba(32, 56, 90, 0.08);
                transform: translateY(-2px);
            }

            .rental-step.is-active {
                border-color: #1f3f68;
                box-shadow: 0 8px 22px rgba(32, 56, 90, 0.18);
                color: #1f2d3d;
            }

            .rental-step.is-disabled {
                pointer-events: none;
                opacity: 0.55;
            }

            .rental-step.is-complete:not(.is-active) {
                border-color: rgba(40, 167, 69, 0.3);
            }

            .step-icon {
                width: 2.35rem;
                height: 2.35rem;
                border-radius: 50%;
                background: rgba(31, 63, 104, 0.08);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                flex-shrink: 0;
            }

            .rental-step.is-active .step-icon {
                background: rgba(31, 63, 104, 0.18);
                color: #1f3f68;
            }

            .step-content {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
                min-width: 0;
            }

            .step-label {
                font-weight: 600;
                font-size: 0.92rem;
                line-height: 1.25;
            }

            .step-status {
                font-size: 0.72rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                border-radius: 999px;
                padding: 0.16rem 0.55rem;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }

            .step-status::before {
                content: '';
                width: 0.4rem;
                height: 0.4rem;
                border-radius: 50%;
                background: currentColor;
            }

            .status-complete {
                background: rgba(46, 204, 113, 0.18);
                color: #1a8a49;
            }

            .status-pending {
                background: rgba(255, 193, 7, 0.2);
                color: #ab7700;
            }

            .step-marker {
                position: absolute;
                inset: auto 1rem 0.55rem 1rem;
                height: 3px;
                border-radius: 999px;
                background: linear-gradient(90deg, #ffb74d, #ff9f43);
            }

            @media (max-width: 768px) {
                .rental-steps {
                    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                    gap: 0.6rem;
                }

                .rental-step {
                    padding: 0.75rem 0.85rem;
                    gap: 0.6rem;
                }

                .step-icon {
                    width: 2.1rem;
                    height: 2.1rem;
                    font-size: 1.05rem;
                }

                .step-label {
                    font-size: 0.86rem;
                }
            }

            @media (max-width: 480px) {
                .rental-steps {
                    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                }

                .step-label {
                    font-size: 0.8rem;
                }

                .step-status {
                    font-size: 0.66rem;
                }
            }
        </style>
    @endpush
@endonce
