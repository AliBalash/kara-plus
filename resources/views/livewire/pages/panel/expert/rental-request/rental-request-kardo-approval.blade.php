<div>
    <div class="container">
    <div class="row g-3 align-items-center">
        <div class="col-lg-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Contract /</span> KARDO Approval
            </h4>
        </div>
        @php
            $customerName = optional($contract->customer)->fullName() ?? '—';
            $agreementDisplay = optional($contract->pickupDocument)->agreement_number;
            $vehicleName = optional($contract->car)->modelName() ?? 'Vehicle not assigned';
            $plateNumber = optional($contract->car)->plate_number;
            $tarsApproved = (bool) $pickupDocument->tars_approved_at;
            $kardoRequired = (bool) $contract->kardo_required;
            $kardoApproved = (bool) $pickupDocument->kardo_approved_at;
            $contractStatus = \Illuminate\Support\Str::headline($contract->current_status ?? 'draft');
            $tarsStatusClass = $tarsApproved ? 'text-success' : 'text-warning';
            $kardoStatusClass = $kardoRequired
                ? ($kardoApproved ? 'text-success' : 'text-warning')
                : 'text-muted';
            $kardoStatusText = $kardoRequired ? ($kardoApproved ? 'Approved' : 'Pending') : 'Not Required';
            $inspectionReady = $tarsApproved && (! $kardoRequired || $kardoApproved);
        @endphp
        <div class="col-lg-8">
            <div class="status-toolbar d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                <div class="status-overview flex-grow-1 d-flex flex-column flex-md-row flex-wrap gap-3">
                    <div class="status-card">
                        <div class="status-card-label"><i class="bi bi-person-circle me-2"></i>Customer</div>
                        <div class="status-card-value">{{ $customerName }}</div>
                    </div>
                    <div class="status-card">
                        <div class="status-card-label"><i class="bi bi-file-earmark-text me-2"></i>Agreement #</div>
                        <div class="status-card-value">{{ $agreementDisplay ? \Illuminate\Support\Str::upper($agreementDisplay) : '—' }}</div>
                    </div>
                    <div class="status-card">
                        <div class="status-card-label"><i class="bi bi-car-front me-2"></i>Vehicle</div>
                        <div class="status-card-value">{{ $vehicleName }}</div>
                        <div class="status-card-sub text-muted">
                            {{ $plateNumber ? 'Plate: ' . \Illuminate\Support\Str::upper($plateNumber) : 'Plate not set' }}
                        </div>
                    </div>
                    <div class="status-card status-card--status">
                        <div class="status-card-label"><i class="bi bi-info-circle me-2"></i>Contract Status</div>
                        <div class="status-card-value">{{ $contractStatus }}</div>
                    </div>
                    <div class="status-card">
                        <div class="status-card-label"><i class="bx bx-check-shield me-2"></i>TARS</div>
                        <div class="status-card-value {{ $tarsStatusClass }}">{{ $tarsApproved ? 'Approved' : 'Pending' }}</div>
                    </div>
                    <div class="status-card">
                        <div class="status-card-label"><i class="bx bx-layer me-2"></i>KARDO</div>
                        <div class="status-card-value {{ $kardoStatusClass }}">{{ $kardoStatusText }}</div>
                    </div>
                </div>
                @if ($inspectionReady)
                    <button type="button" class="btn btn-sm btn-gradient-danger status-action flex-shrink-0"
                        wire:click="completeInspection"
                        wire:confirm="Are you sure you want to complete the inspection and move to awaiting return?">
                        <i class="bx bx-flag-checkered me-1"></i>
                        <span>Complete Inspection</span>
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary status-action flex-shrink-0 disabled"
                        disabled>
                        <i class="bx bx-time-five me-1"></i>
                        <span>{{ $tarsApproved ? 'Approve KARDO to finish' : 'Awaiting TARS approval' }}</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
    <x-detail-rental-request-tabs :contract-id="$contractId" />

    <div class="card shadow-sm border-0 rounded-4 mt-4">
        <div class="card-header bg-white border-0 rounded-top-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <h5 class="mb-0">Finalize KARDO Inspection</h5>
                <span class="text-muted small">Confirm KARDO documentation to finish the pickup inspection.</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="status-card status-card--status h-100">
                        <div class="status-card-label"><i class="bx bx-check-shield me-2"></i>TARS</div>
                        <div class="status-card-value {{ $tarsStatusClass }}">
                            {{ $tarsApproved ? 'Approved' : 'Pending' }}
                        </div>
                        @unless ($tarsApproved)
                            <a href="{{ route('rental-requests.tars-approval', $contractId) }}" class="small mt-2 d-inline-flex align-items-center">
                                <i class="bx bx-link-external me-1"></i>
                                View TARS page
                            </a>
                        @endunless
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="status-card status-card--status h-100">
                        <div class="status-card-label"><i class="bx bx-layer me-2"></i>KARDO</div>
                        <div class="status-card-value {{ $kardoStatusClass }}">
                            {{ $kardoStatusText }}
                        </div>
                    </div>
                </div>
            </div>

            @include('livewire.pages.panel.expert.rental-request.partials.customer-documents')

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="document-card h-100 border rounded-4 p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">KARDO Contract</h6>
                                <p class="text-muted mb-0 small">Secondary inspection sheet for regulated deliveries.</p>
                            </div>
                            @if ($kardoRequired)
                                <span class="badge {{ $kardoApproved ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $kardoApproved ? 'Approved' : 'Pending' }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($kardoRequired)
                                @if (! empty($existingFiles['kardoContract']))
                                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden bg-light border">
                                        <img src="{{ $existingFiles['kardoContract'] }}" class="w-100 h-100 object-fit-cover" loading="lazy" decoding="async" fetchpriority="low"
                                            alt="KARDO document preview">
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                            data-preview="{{ $existingFiles['kardoContract'] }}"
                                            data-download="{{ $existingFiles['kardoContract'] }}"
                                            data-title="KARDO Contract">
                                            <i class="bx bx-show me-1"></i>Preview
                                        </button>
                                        <a href="{{ $existingFiles['kardoContract'] }}" class="btn btn-outline-secondary btn-sm"
                                            target="_blank" rel="noopener" download>
                                            <i class="bx bx-download me-1"></i>Download
                                        </a>
                                        @if ($kardoApproved)
                                            <button type="button"
                                                class="btn btn-outline-danger btn-sm"
                                                onclick="window.confirm('Revoke KARDO approval?') && @this.revokeKardo()">
                                                <i class="bx bx-undo me-1"></i>Revoke KARDO
                                            </button>
                                        @else
                                            <button wire:click="approveKardo" class="btn btn-success btn-sm">
                                                <i class="bx bx-check me-1"></i>Approve KARDO
                                            </button>
                                        @endif
                                    </div>
                                    @if (! empty($pickupDocument->agreement_number))
                                        <p class="text-muted small mt-3 mb-0">
                                            Agreement #: {{ $pickupDocument->agreement_number }}
                                        </p>
                                    @endif
                                    @if ($kardoApproved)
                                        <p class="text-muted small mt-1 mb-0">
                                            Approved on {{ optional($pickupDocument->kardo_approved_at)->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                @else
                                    <div class="bg-warning-subtle border border-warning-subtle text-warning-emphasis rounded-3 mb-2 p-3">
                                        KARDO document has not been uploaded yet.
                                    </div>
                                    @if (! empty($pickupDocument->agreement_number))
                                        <p class="text-muted small mb-0">Provided agreement #: {{ $pickupDocument->agreement_number }}</p>
                                    @endif
                                @endif
                            @else
                                <div class="bg-info-subtle border border-info-subtle text-info-emphasis rounded-3 mb-0 p-3">
                                    KARDO inspection is not required for this contract.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="document-card h-100 border rounded-4 p-4 shadow-sm bg-light-subtle">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">TARS Summary</h6>
                                <p class="text-muted mb-0 small">TARS approval is required before completing KARDO.</p>
                            </div>
                            <span class="badge {{ $tarsApproved ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $tarsApproved ? 'Approved' : 'Pending' }}
                            </span>
                        </div>
                        <div class="mt-3">
                            @if ($tarsApproved)
                                <p class="text-muted small mb-2">Approved on {{ optional($pickupDocument->tars_approved_at)->format('d M Y H:i') }}</p>
                            @else
                                <p class="text-muted small mb-3">Complete the TARS review before finishing KARDO.</p>
                                <a href="{{ route('rental-requests.tars-approval', $contractId) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-link-external me-1"></i>Go to TARS Approval
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if (! $inspectionReady)
                <div class="bg-warning-subtle border border-warning-subtle text-warning-emphasis d-flex align-items-center mt-4 mb-0 rounded-3 p-3">
                    <i class="bx bx-time-five me-2"></i>
                    <div>Please approve all required documents before completing the inspection.</div>
                </div>
            @endif
        </div>
    </div>
    @include('livewire.pages.panel.expert.rental-request.partials.document-preview-modal')
</div>

@once
    @push('styles')
        <style>
            .status-toolbar {
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.7), rgba(244, 244, 255, 0.95));
                border: 1px solid rgba(99, 102, 241, 0.1);
                border-radius: 16px;
                padding: 1.25rem;
            }

            .status-card {
                background: white;
                border-radius: 12px;
                padding: 0.75rem 1rem;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
            }

            .status-card--status {
                border-left: 4px solid rgba(99, 102, 241, 0.45);
            }

            .status-card-label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #64748b;
                margin-bottom: 0.35rem;
            }

            .status-card-value {
                font-weight: 600;
                font-size: 1rem;
            }
        </style>
    @endpush
@endonce
