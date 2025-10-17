<div>
    @php use Illuminate\Support\Str; @endphp
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Pickup Document Inspection — Contract #{{ $contractId }}</h5>
            <div class="d-flex gap-2">
                @if ($pickupDocument->tars_approved_at)
                    <span class="badge bg-success-subtle text-success">TARS approved</span>
                @endif
                 @if ($pickupDocument->kardo_approved_at)
                    <span class="badge bg-success-subtle text-success">KARDo approved</span>
                @endif
               
            </div>
        </div>

        <div class="card-body">
            @php
                $hasCustomerDocs = collect($customerDocuments ?? [])->flatten(1)->isNotEmpty();
            @endphp

            @if ($hasCustomerDocs)
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h6 class="fw-semibold mb-0">Customer-submitted Documents</h6>
                        <span class="text-muted small">Preview or download the files uploaded during onboarding.</span>
                    </div>
                    <div class="row g-3">
                        @foreach ($customerDocuments as $type => $documents)
                            @if (!empty($documents))
                                <div class="col-12 col-lg-6 col-xl-3">
                                    <div class="border rounded-4 h-100 p-3 shadow-sm bg-light-subtle">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-semibold">{{ Str::of($type)->replace('_', ' ')->title() }}</span>
                                            <span class="badge bg-primary-subtle text-primary">{{ count($documents) }}</span>
                                        </div>
                                        <ul class="list-unstyled mb-0">
                                            @foreach ($documents as $document)
                                                <li class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <span class="avatar avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                                                <i class="bx {{ $document['is_pdf'] ? 'bx-file' : 'bx-image' }}"></i>
                                                            </span>
                                                            <div>
                                                                <div class="fw-semibold small">{{ $document['label'] }}</div>
                                                                <div class="text-muted text-truncate" style="max-width: 140px;">{{ $document['filename'] }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="btn-group btn-group-sm">
                                                            @unless ($document['is_pdf'])
                                                                <button type="button" class="btn btn-outline-primary"
                                                                    data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                                                    data-preview="{{ $document['url'] }}"
                                                                    data-download="{{ $document['url'] }}"
                                                                    data-title="{{ Str::of($type)->upper() }} — {{ $document['label'] }}">
                                                                    <i class="bx bx-show"></i>
                                                                </button>
                                                            @endunless
                                                            <a href="{{ $document['url'] }}" class="btn btn-outline-secondary"
                                                                target="_blank" rel="noopener" download>
                                                                <i class="bx bx-download"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="document-card h-100 border rounded-4 p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">TARS Contract</h6>
                                <p class="text-muted mb-0 small">Driver-uploaded agreement for handover validation.</p>
                            </div>
                            @if ($pickupDocument->tars_approved_at)
                                <span class="badge bg-success">Approved</span>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if (!empty($existingFiles['tarsContract']))
                                <div class="ratio ratio-4x3 rounded-3 overflow-hidden bg-light border">
                                    <img src="{{ $existingFiles['tarsContract'] }}" class="w-100 h-100 object-fit-cover"
                                        alt="TARS document preview">
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                        data-preview="{{ $existingFiles['tarsContract'] }}"
                                        data-download="{{ $existingFiles['tarsContract'] }}"
                                        data-title="TARS Contract">
                                        <i class="bx bx-show me-1"></i>Preview
                                    </button>
                                    <a href="{{ $existingFiles['tarsContract'] }}" class="btn btn-outline-secondary btn-sm"
                                        target="_blank" rel="noopener" download>
                                        <i class="bx bx-download me-1"></i>Download
                                    </a>
                                    @if (!$pickupDocument->tars_approved_at)
                                        <button wire:click="approveTars" class="btn btn-success btn-sm">
                                            <i class="bx bx-check me-1"></i>Approve TARS
                                        </button>
                                    @endif
                                </div>
                                @if ($pickupDocument->tars_approved_at)
                                    <p class="text-muted small mt-2 mb-0">
                                        Approved on {{ $pickupDocument->tars_approved_at }}
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
                    <div class="document-card h-100 border rounded-4 p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">KARDO Contract</h6>
                                <p class="text-muted mb-0 small">Secondary inspection sheet for regulated deliveries.</p>
                            </div>
                            @if ($contract->kardo_required)
                                <span
                                    class="badge {{ $pickupDocument->kardo_approved_at ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $pickupDocument->kardo_approved_at ? 'Approved' : 'Pending' }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-3">
                            @if ($contract->kardo_required)
                                @if (!empty($existingFiles['kardoContract']))
                                    <div class="ratio ratio-4x3 rounded-3 overflow-hidden bg-light border">
                                        <img src="{{ $existingFiles['kardoContract'] }}" class="w-100 h-100 object-fit-cover"
                                            alt="CARDO document preview">
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                            data-preview="{{ $existingFiles['kardoContract'] }}"
                                            data-download="{{ $existingFiles['kardoContract'] }}"
                                            data-title="CARDO Contract">
                                            <i class="bx bx-show me-1"></i>Preview
                                        </button>
                                        <a href="{{ $existingFiles['kardoContract'] }}" class="btn btn-outline-secondary btn-sm"
                                            target="_blank" rel="noopener" download>
                                            <i class="bx bx-download me-1"></i>Download
                                        </a>
                                        @if (!$pickupDocument->kardo_approved_at)
                                            <button wire:click="approveKardo" class="btn btn-success btn-sm">
                                                <i class="bx bx-check me-1"></i>Approve KARDO
                                            </button>
                                        @endif
                                    </div>
                                    @if (!empty($pickupDocument->agreement_number))
                                        <p class="text-muted small mt-3 mb-0">
                                            Agreement #: {{ $pickupDocument->agreement_number }}
                                        </p>
                                    @endif
                                    @if ($pickupDocument->kardo_approved_at)
                                        <p class="text-muted small mt-1 mb-0">
                                            Approved on {{ $pickupDocument->kardo_approved_at }}
                                        </p>
                                    @endif
                                @else
                                    <div class="alert alert-warning rounded-3 mb-2">
                                        kARDO document has not been uploaded yet.
                                    </div>
                                    @if (!empty($pickupDocument->agreement_number))
                                        <p class="text-muted small mb-0">Provided agreement #: {{ $pickupDocument->agreement_number }}</p>
                                    @endif
                                @endif
                            @else
                                <div class="alert alert-info rounded-3 mb-0">
                                    KARDO inspection is not required for this contract.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-top pt-4 mt-4">
                @if ($pickupDocument->tars_approved_at && (!$contract->kardo_required || $pickupDocument->kardo_approved_at))
                    <button wire:click="completeInspection"
                        wire:confirm="Are you sure you want to complete the inspection and set the status to awaiting_return?"
                        class="btn btn-danger">
                        <i class="bx bx-flag-checkered me-1"></i>Complete Inspection &amp; Move to Awaiting Return
                    </button>
                @else
                    <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                        <i class="bx bx-time-five me-2"></i>
                        <div>
                            Please approve all required documents before completing the inspection.
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success mt-3">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mt-3">{{ session('error') }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- Shared preview modal -->
    <div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentPreviewTitle">Document preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="ratio ratio-4x3 bg-white border rounded-4 overflow-hidden shadow-sm">
                        <img id="documentPreviewImage" src="" alt="Document preview" class="w-100 h-100 object-fit-contain">
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <small class="text-muted">Use download for higher resolution if needed.</small>
                    <a id="documentDownloadLink" href="#" target="_blank" rel="noopener" download
                        class="btn btn-primary">
                        <i class="bx bx-download me-1"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            .document-card img.object-fit-cover {
                object-fit: cover;
            }

            .document-card img.object-fit-contain {
                object-fit: contain;
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            const previewModal = document.getElementById('documentPreviewModal');
            if (previewModal) {
                previewModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const image = previewModal.querySelector('#documentPreviewImage');
                    const downloadLink = previewModal.querySelector('#documentDownloadLink');
                    const title = previewModal.querySelector('#documentPreviewTitle');

                    const previewSrc = button.getAttribute('data-preview');
                    const downloadSrc = button.getAttribute('data-download');
                    const modalTitle = button.getAttribute('data-title') || 'Document preview';

                    if (image && previewSrc) {
                        image.src = previewSrc;
                    }

                    if (downloadLink && downloadSrc) {
                        downloadLink.href = downloadSrc;
                    }

                    if (title) {
                        title.textContent = modalTitle;
                    }
                });

                previewModal.addEventListener('hidden.bs.modal', () => {
                    const image = previewModal.querySelector('#documentPreviewImage');
                    if (image) {
                        image.src = '';
                    }
                });
            }
        </script>
    @endpush
@endonce
