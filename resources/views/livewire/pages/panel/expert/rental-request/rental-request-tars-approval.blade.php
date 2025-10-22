<div>
    <div class="container">
        <div class="row g-3 align-items-center">
            <div class="col-lg-4">
                <h4 class="fw-bold py-3 mb-0">
                    <span class="text-muted fw-light">Contract /</span> TARS Approval
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
                $kardoStatusClass = $kardoRequired ? ($kardoApproved ? 'text-success' : 'text-warning') : 'text-muted';
                $kardoStatusText = $kardoRequired ? ($kardoApproved ? 'Approved' : 'Pending') : 'Not Required';
                $statusReady = $tarsApproved && (!$kardoRequired || $kardoApproved);
                $statusButtonText = match (true) {
                    $statusReady => 'Move to Delivery',
                    !$tarsApproved => 'Awaiting TARS approval',
                    default => 'Awaiting KARDO approval',
                };
            @endphp
            <div class="col-lg-8">
                <div class="status-toolbar d-flex flex-column flex-lg-row align-items-lg-center gap-3">
                    <div class="status-overview flex-grow-1">
                        <div class="status-card">
                            <div class="status-card-label"><i class="bi bi-person-circle me-2"></i>Customer</div>
                            <div class="status-card-value">{{ $customerName }}</div>
                        </div>
                        <div class="status-card">
                            <div class="status-card-label"><i class="bi bi-file-earmark-text me-2"></i>Agreement #</div>
                            <div class="status-card-value">
                                {{ $agreementDisplay ? \Illuminate\Support\Str::upper($agreementDisplay) : '—' }}</div>
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
                            <div class="status-card-value {{ $tarsStatusClass }}">
                                {{ $tarsApproved ? 'Approved' : 'Pending' }}</div>
                        </div>
                        <div class="status-card">
                            <div class="status-card-label"><i class="bx bx-layer me-2"></i>KARDO</div>
                            <div class="status-card-value {{ $kardoStatusClass }}">{{ $kardoStatusText }}</div>
                        </div>
                    </div>
                    <div class="status-actions d-flex flex-column flex-md-row flex-wrap gap-2 justify-content-md-end">
                        @if ($statusReady)
                            <button type="button" class="btn btn-sm btn-gradient-danger status-action flex-shrink-0"
                                onclick="window.confirm('Set this contract to Delivery for the rider?') && @this.changeStatusToDelivery()">
                                <i class="bx bx-send me-1"></i>
                                <span>{{ $statusButtonText }}</span>
                            </button>
                        @else
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary status-action flex-shrink-0 disabled" disabled>
                                <i class="bx bx-time-five me-1"></i>
                                <span>{{ $statusButtonText }}</span>
                            </button>
                        @endif

                        @if ($kardoRequired)
                            <a href="{{ route('rental-requests.kardo-approval', $contractId) }}"
                                class="btn btn-sm btn-gradient-primary status-action flex-shrink-0">
                                <i class="bx bx-layer me-1"></i>
                                <span>Open KARDO Approval</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('info'))
            <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                {{ session('info') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <x-detail-rental-request-tabs :contract-id="$contractId" />

        <div class="card shadow-sm border-0 rounded-4 mt-4">
            <div class="card-header bg-white border-0 rounded-top-4 py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                    <h5 class="mb-0">Review TARS Documents</h5>
                    <span class="text-muted small">Approve the driver-submitted agreement before proceeding to
                        KARDO.</span>
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
                                    <h6 class="mb-1 fw-semibold">TARS Contract</h6>
                                    <p class="text-muted mb-0 small">Driver-uploaded agreement for handover validation.
                                    </p>
                                </div>
                                @if ($tarsApproved)
                                    <span class="badge bg-success">Approved</span>
                                @endif
                            </div>

                            <div class="mt-3">
                                @if (!empty($existingFiles['tarsContract']))
                                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden bg-light border">
                                        <img src="{{ $existingFiles['tarsContract'] }}"
                                            class="w-100 h-100 object-fit-cover" loading="lazy" decoding="async"
                                            fetchpriority="low" alt="TARS document preview">
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                            data-preview="{{ $existingFiles['tarsContract'] }}"
                                            data-download="{{ $existingFiles['tarsContract'] }}"
                                            data-title="TARS Contract">
                                            <i class="bx bx-show me-1"></i>Preview
                                        </button>
                                        <a href="{{ $existingFiles['tarsContract'] }}"
                                            class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener"
                                            download>
                                            <i class="bx bx-download me-1"></i>Download
                                        </a>
                                        @unless ($tarsApproved)
                                            <button wire:click="approveTars" class="btn btn-success btn-sm">
                                                <i class="bx bx-check me-1"></i>Approve TARS
                                            </button>
                                        @endunless
                                    </div>
                                    @if ($tarsApproved)
                                        <p class="text-muted small mt-2 mb-0">
                                            Approved on
                                            {{ optional($pickupDocument->tars_approved_at)->format('d M Y H:i') }}
                                        </p>
                                    @endif
                                @else
                                    <div class="alert alert-warning rounded-3 mb-0">
                                        TARS document has not been uploaded yet.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="document-card h-100 border rounded-4 p-4 shadow-sm bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-1 fw-semibold">KARDO Approval</h6>
                                    <p class="text-muted mb-0 small">
                                        {{ $kardoRequired ? 'Proceed to the KARDO page after TARS approval.' : 'KARDO is not required for this contract.' }}
                                    </p>
                                </div>
                                @if ($kardoRequired)
                                    <span class="badge {{ $kardoApproved ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $kardoApproved ? 'Approved' : 'Pending' }}
                                    </span>
                                @endif
                            </div>
                            @if ($kardoRequired)
                                <p class="text-muted small mt-3 mb-0">Once TARS is approved, continue with KARDO
                                    inspection.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('livewire.pages.panel.expert.rental-request.partials.document-preview-modal')
    </div>

    @once
        @push('styles')
            <style>
                .status-toolbar {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    flex-wrap: wrap;
                    background: #fff;
                    border: 1px solid #e0e6ef;
                    border-radius: 1rem;
                    padding: 1.1rem 1.25rem;
                    box-shadow: 0 6px 16px rgba(33, 56, 86, 0.06);
                }

                @media (min-width: 992px) {
                    .status-toolbar {
                        flex-direction: row;
                        align-items: center;
                        gap: 1.5rem;
                    }
                }

                .status-overview {
                    display: grid;
                    gap: 0.75rem;
                    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                    flex: 1 1 100%;
                }

                @media (max-width: 575.98px) {
                    .status-overview {
                        grid-template-columns: minmax(0, 1fr);
                    }
                }

                .status-card {
                    min-width: 0;
                    background: #f8f9fc;
                    border: 1px solid #edf1f7;
                    border-radius: 0.9rem;
                    padding: 0.75rem 1rem;
                    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
                }

                .status-card-label {
                    display: flex;
                    align-items: center;
                    gap: 0.35rem;
                    font-size: 0.72rem;
                    text-transform: uppercase;
                    letter-spacing: 0.12em;
                    color: #8a96aa;
                    font-weight: 600;
                    margin-bottom: 0.35rem;
                }

                .status-card-value {
                    font-size: 1rem;
                    font-weight: 600;
                    color: #1f2a3d;
                    word-break: break-word;
                    margin-top: 0.35rem;
                }

                .status-card-sub {
                    font-size: 0.78rem;
                    margin-top: 0.2rem;
                }

                .status-card--status {
                    background: linear-gradient(135deg, #4263eb, #364fc7);
                    color: #fff;
                    border: none;
                    box-shadow: 0 12px 24px rgba(66, 99, 235, 0.2);
                }

                .status-card--status .status-card-label,
                .status-card--status .status-card-sub {
                    color: rgba(255, 255, 255, 0.75);
                }

                .status-card--status .status-card-value {
                    color: #fff;
                }

                .status-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                    width: 100%;
                }

                @media (min-width: 768px) {
                    .status-actions {
                        flex-direction: row;
                        flex-wrap: wrap;
                        justify-content: flex-end;
                        width: auto;
                    }
                }

                .status-action {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.4rem;
                    padding: 0.6rem 1.1rem;
                    border-radius: 999px;
                    transition: transform 0.15s ease, box-shadow 0.15s ease;
                }

                .status-action.btn-gradient-danger {
                    border: none;
                    color: #fff;
                    background: linear-gradient(135deg, #ff6f61, #ff3b3b);
                    box-shadow: 0 12px 24px rgba(255, 68, 41, 0.25);
                }

                .status-action.btn-gradient-primary {
                    border: none;
                    color: #fff;
                    background: linear-gradient(135deg, #3a86ff, #4361ee);
                    box-shadow: 0 12px 24px rgba(67, 97, 238, 0.25);
                }

                .status-action.btn-outline-secondary {
                    background: #fff;
                    color: #6c757d;
                    border: 1px solid rgba(108, 117, 125, 0.35);
                    box-shadow: none;
                }

                .status-action.btn-gradient-danger:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 14px 28px rgba(255, 68, 41, 0.3);
                }

                .status-action.btn-gradient-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 14px 28px rgba(67, 97, 238, 0.28);
                }

                .status-action:active {
                    transform: translateY(0);
                }

                @media (max-width: 575.98px) {
                    .status-toolbar {
                        gap: 0.75rem;
                    }

                    .status-actions .status-action,
                    .status-actions .btn {
                        width: 100%;
                        justify-content: center;
                    }
                }

                .document-card .preview-wrapper {
                    background: #f1f3f5;
                    border-radius: 0.75rem;
                    overflow: hidden;
                }

                .document-card .preview-wrapper img,
                .document-card .preview-wrapper video {
                    object-fit: cover;
                }

                .gallery-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
                    gap: 0.75rem;
                }

                .gallery-thumb {
                    position: relative;
                    overflow: hidden;
                    border-radius: 0.75rem;
                    background: #f1f3f5;
                }

                .gallery-thumb img,
                .gallery-thumb video {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .gallery-remove {
                    position: absolute;
                    top: 0.35rem;
                    right: 0.35rem;
                    width: 1.75rem;
                    height: 1.75rem;
                    display: inline-flex;
                    justify-content: center;
                    align-items: center;
                    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.2);
                }

                .empty-placeholder {
                    border: 1px dashed #ced4da;
                    background: #f8f9fa;
                }

                .preview-clickable {
                    cursor: zoom-in;
                }

                @media (max-width: 575.98px) {
                    .document-card {
                        border-radius: 1rem;
                    }
                }
            </style>
        @endpush
    @endonce
