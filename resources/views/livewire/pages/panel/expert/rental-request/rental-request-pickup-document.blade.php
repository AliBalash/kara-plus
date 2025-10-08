<div class="container">
    <div class="row g-3 align-items-center">
        <div class="col-lg-6">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Contract /</span> Pickup Document
            </h4>
        </div>
        @if (!empty($contractId))
            <div class="col-lg-6 text-lg-end">
                <a class="btn btn-danger fw-semibold shadow-sm"
                    href="javascript:void(0);"
                    onclick="if(confirm('Are you sure you want to set this contract to Delivery?')) { @this.changeStatusToDelivery({{ $contractId }}) }">
                    Set to Delivery (permission :for rider)
                    <i class="bx bxs-log-in-circle ms-1"></i>
                </a>
            </div>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <x-detail-rental-request-tabs :contract-id="$contractId" />

    <div class="card shadow-sm border-0 rounded-4 mt-4">
        <div class="card-header bg-white border-0 rounded-top-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <h5 class="mb-0">Upload Pickup Documents</h5>
                <span class="text-muted small">Upload every pickup document in one place</span>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocuments">
                {{-- Preview Modal --}}
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel">View Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modalImage" src="" class="img-fluid rounded" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">Tars Contract</label>
                                @if (!empty($existingFiles['tarsContract']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('tars_contract')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['tarsContract']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['tarsContract'] }}" class="img-fluid preview-clickable"
                                        alt="Tars Contract" onclick="openModal('{{ $existingFiles['tarsContract'] }}')">
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="tarsContract"
                                @if (empty($existingFiles['tarsContract'])) required @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div wire:loading wire:target="tarsContract" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>
                            @error('tarsContract')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @if ($contract->kardo_required)
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-semibold mb-0">Cardo Contract</label>
                                    @if (!empty($existingFiles['kardoContract']))
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="confirmDeletion('kardo_contract')">Remove</button>
                                    @endif
                                </div>
                                @if (!empty($existingFiles['kardoContract']))
                                    <div class="preview-wrapper mb-3">
                                        <img src="{{ $existingFiles['kardoContract'] }}" class="img-fluid preview-clickable"
                                            alt="Cardo Contract" onclick="openModal('{{ $existingFiles['kardoContract'] }}')">
                                    </div>
                                @endif
                                <input type="file" class="form-control" wire:model="kardoContract"
                                    @if (empty($existingFiles['kardoContract'])) required @endif>
                                <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                                <div wire:loading wire:target="kardoContract" class="progress mt-3">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        style="width: 100%;">Uploading...</div>
                                </div>
                                @error('kardoContract')
                                    <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-light d-flex flex-column align-items-center justify-content-center text-center">
                                <span class="fw-semibold mb-2">Cardo Contract</span>
                                <p class="text-muted small mb-0">CARDO inspection is not required for this contract.</p>
                            </div>
                        </div>
                    @endif

                    <div class="col-12 col-lg-6 col-xl-4">
                        @php $paymentOnDelivery = (bool) ($contract->payment_on_delivery ?? false); @endphp
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm {{ $paymentOnDelivery ? 'bg-white' : 'bg-light opacity-75' }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">Watcher's Receipt</label>
                                @if (!empty($existingFiles['factorContract']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('factor_contract')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['factorContract']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['factorContract'] }}" class="img-fluid preview-clickable"
                                        alt="Watcher's Receipt" onclick="openModal('{{ $existingFiles['factorContract'] }}')">
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="factorContract"
                                @if (! $paymentOnDelivery) disabled @endif
                                @if ($paymentOnDelivery && empty($existingFiles['factorContract'])) required @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            @unless ($paymentOnDelivery)
                                <small class="text-muted d-block">Not needed when Payment on Delivery is disabled.</small>
                            @endunless
                            <div wire:loading wire:target="factorContract" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>
                            @error('factorContract')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">KM/Fuel Photo</label>
                                @if (!empty($existingFiles['carDashboard']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('car_dashboard')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['carDashboard']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['carDashboard'] }}" class="img-fluid preview-clickable"
                                        alt="Dashboard Photo" onclick="openModal('{{ $existingFiles['carDashboard'] }}')">
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="carDashboard"
                                @if (empty($existingFiles['carDashboard'])) required @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div wire:loading wire:target="carDashboard" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>
                            @error('carDashboard')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">Inside Car Video</label>
                                @if (!empty($existingFiles['carVideoInside']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('car_video_inside')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['carVideoInside']))
                                <div class="preview-wrapper ratio ratio-4x3 mb-3">
                                    <video class="w-100 h-100" controls>
                                        <source src="{{ $existingFiles['carVideoInside'] }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="carVideoInside"
                                @if (empty($existingFiles['carVideoInside'])) required @endif>
                            <small class="text-muted d-block mt-2">MP4 up to 20MB</small>
                            <div wire:loading wire:target="carVideoInside" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>
                            @error('carVideoInside')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">Outside Car Video</label>
                                @if (!empty($existingFiles['carVideoOutside']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('car_video_outside')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['carVideoOutside']))
                                <div class="preview-wrapper ratio ratio-4x3 mb-3">
                                    <video class="w-100 h-100" controls>
                                        <source src="{{ $existingFiles['carVideoOutside'] }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="carVideoOutside"
                                @if (empty($existingFiles['carVideoOutside'])) required @endif>
                            <small class="text-muted d-block mt-2">MP4 up to 20MB</small>
                            <div wire:loading wire:target="carVideoOutside" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>
                            @error('carVideoOutside')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <label class="form-label fw-semibold">Mileage</label>
                            <input type="text" class="form-control" wire:model="mileage" placeholder="Km on pickup">
                            <small class="text-muted d-block mt-2">Current odometer at pickup</small>
                            @error('mileage')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <label class="form-label fw-semibold">Note (Optional)</label>
                            <textarea class="form-control" wire:model="note" rows="4" placeholder="Internal note for the team"></textarea>
                            @error('note')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <label class="form-label fw-semibold">Driver Note (Optional)</label>
                            <textarea class="form-control" wire:model="driverNote" rows="4" placeholder="Message for the driver"></textarea>
                            @error('driverNote')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Fuel Level</span>
                                <span class="badge bg-primary">{{ $fuelLevel }}%</span>
                            </div>
                            <div class="card-body">
                                <label for="fuelRange" class="form-label">Select fuel level (%)</label>
                                <input type="range" class="form-range" min="0" max="100" step="10" id="fuelRange"
                                    wire:model.live="fuelLevel">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>0%</span>
                                    <span>50%</span>
                                    <span>100%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($contract->payment_on_delivery)
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div class="card shadow-sm h-100 border-{{ $remainingBalance > 0 ? 'warning' : 'success' }}">
                                <div class="card-body">
                                    <h6 class="card-title fw-semibold mb-3">Payment on Delivery</h6>
                                    @if ($remainingBalance > 0)
                                        <p class="mb-1 text-muted">Remaining balance to collect:</p>
                                        <p class="fw-bold text-warning mb-0">
                                            {{ number_format($remainingBalance, 2) }} {{ $contract->currency }}
                                        </p>
                                    @else
                                        <p class="mb-0 text-success fw-semibold">All payments are completed. ✅</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        Upload Documents
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .document-card .preview-wrapper {
            background: #f1f3f5;
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .document-card .preview-wrapper img,
        .document-card .preview-wrapper video {
            object-fit: cover;
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

@push('scripts')
    <script>
        function confirmDeletion(fileType) {
            if (confirm('Are you sure you want to delete this file?')) {
                @this.call('removeFile', fileType);
            }
        }
    </script>

    <script>
        function openModal(imageUrl) {
            document.getElementById('modalImage').src = imageUrl;
            var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        }
    </script>
@endpush
