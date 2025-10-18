<div class="container">
    <div class="row g-3 align-items-center">
        <div class="col-lg-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Contract /</span> Return Document
            </h4>
        </div>
        @if (!empty($contractId))
            @php
                $customerName = optional($contract->customer)->fullName() ?? '—';
                $agreementDisplay = $agreementNumber ?? optional($contract->pickupDocument)->agreement_number;
                $vehicleName = optional($contract->car)->modelName() ?? 'Vehicle not assigned';
                $plateNumber = optional($contract->car)->plate_number;
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
                            <div class="status-card-label"><i class="bi bi-info-circle me-2"></i>Status</div>
                            <div class="status-card-value">
                                {{ \Illuminate\Support\Str::headline($contract->current_status ?? 'draft') }}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-gradient-primary status-action flex-shrink-0"
                        onclick="window.confirm('Mark this contract Returned and move to Payment?') && @this.changeStatusToPayment({{ $contractId }})">
                        <i class="bx bx-check-circle me-1"></i>
                        <span>Complete Return &amp; Pay</span>
                    </button>
                </div>
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
                <h5 class="mb-0">Upload Return Documents</h5>
                <span class="text-muted small">Capture everything about the vehicle on return</span>
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
                                <label class="form-label fw-semibold mb-0">Watcher's Receipt</label>
                                @if (!empty($existingFiles['factorContract']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('factor_contract')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['factorContract']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['factorContract'] }}" class="img-fluid preview-clickable" loading="lazy" decoding="async" fetchpriority="low"
                                        alt="Watcher's Receipt" onclick="openModal('{{ $existingFiles['factorContract'] }}')">
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="factorContract">
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
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
                                    <img src="{{ $existingFiles['carDashboard'] }}" class="img-fluid preview-clickable" loading="lazy" decoding="async" fetchpriority="low"
                                        alt="Dashboard Photo" onclick="openModal('{{ $existingFiles['carDashboard'] }}')">
                                </div>
                            @endif
                            <input type="file" class="form-control" wire:model="carDashboard">
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
                                <label class="form-label fw-semibold mb-0">Inside Car Photos</label>
                                <span class="badge bg-primary-subtle text-primary">Max 12</span>
                            </div>

                            @if (!empty($existingGalleries['inside']))
                                <div class="gallery-grid mb-3">
                                    @foreach ($existingGalleries['inside'] as $photo)
                                        <div class="gallery-thumb position-relative" wire:key="return-inside-{{ md5($photo['path']) }}">
                                            <img src="{{ $photo['url'] }}" class="img-fluid rounded-3 preview-clickable gallery-img" loading="lazy" decoding="async" fetchpriority="low"
                                                alt="Inside car photo" onclick="openModal('{{ $photo['url'] }}')">
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle gallery-remove"
                                                onclick="confirmGalleryRemoval('inside', '{{ addslashes($photo['path']) }}')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-placeholder text-center py-4 mb-3 border rounded-3">
                                    <i class="bx bx-image-alt fs-1 text-muted mb-2 d-block"></i>
                                    <span class="text-muted small d-block">No inside photos uploaded yet.</span>
                                </div>
                            @endif

                            @if (!empty($carInsidePhotos))
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Ready to upload</div>
                                    <div class="gallery-grid">
                                        @foreach ($carInsidePhotos as $index => $photo)
                                            <div class="gallery-thumb" wire:key="return-inside-temp-{{ $index }}">
                                                <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded-3 gallery-img" loading="lazy" decoding="async" fetchpriority="low"
                                                    alt="Inside preview">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="form-label small fw-semibold text-muted mb-2">Add new photos</label>
                                <input type="file" class="form-control" wire:model="carInsidePhotos" accept="image/*"
                                    multiple>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12 photos
                                    in gallery.</small>
                            </div>

                            <div wire:loading wire:target="carInsidePhotos" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>

                            @error('carInsidePhotos')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                            @error('carInsidePhotos.*')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">Outside Car Photos</label>
                                <span class="badge bg-success-subtle text-success">Max 12</span>
                            </div>

                            @if (!empty($existingGalleries['outside']))
                                <div class="gallery-grid mb-3">
                                    @foreach ($existingGalleries['outside'] as $photo)
                                        <div class="gallery-thumb position-relative" wire:key="return-outside-{{ md5($photo['path']) }}">
                                            <img src="{{ $photo['url'] }}" class="img-fluid rounded-3 preview-clickable gallery-img" loading="lazy" decoding="async" fetchpriority="low"
                                                alt="Outside car photo" onclick="openModal('{{ $photo['url'] }}')">
                                            <button type="button" class="btn btn-sm btn-danger rounded-circle gallery-remove"
                                                onclick="confirmGalleryRemoval('outside', '{{ addslashes($photo['path']) }}')">
                                                <i class="bx bx-x"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-placeholder text-center py-4 mb-3 border rounded-3">
                                    <i class="bx bx-image fs-1 text-muted mb-2 d-block"></i>
                                    <span class="text-muted small d-block">No outside photos uploaded yet.</span>
                                </div>
                            @endif

                            @if (!empty($carOutsidePhotos))
                                <div class="mb-3">
                                    <div class="small fw-semibold text-muted mb-2">Ready to upload</div>
                                    <div class="gallery-grid">
                                        @foreach ($carOutsidePhotos as $index => $photo)
                                            <div class="gallery-thumb" wire:key="return-outside-temp-{{ $index }}">
                                                <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded-3 gallery-img" loading="lazy" decoding="async" fetchpriority="low"
                                                    alt="Outside preview">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="form-label small fw-semibold text-muted mb-2">Add new photos</label>
                                <input type="file" class="form-control" wire:model="carOutsidePhotos" accept="image/*"
                                    multiple>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12 photos
                                    in gallery.</small>
                            </div>

                            <div wire:loading wire:target="carOutsidePhotos" class="progress mt-3">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                    style="width: 100%;">Uploading...</div>
                            </div>

                            @error('carOutsidePhotos')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                            @error('carOutsidePhotos.*')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                            <label class="form-label fw-semibold">Mileage</label>
                            <input type="text" class="form-control" wire:model="mileage" placeholder="Km on return">
                            <small class="text-muted d-block mt-2">Odometer reading on return</small>
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
                    <button type="submit" class="btn btn-primary btn-lg px-4">Upload Documents</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .status-toolbar {
            background: #fff;
            border: 1px solid #e0e6ef;
            border-radius: 1rem;
            padding: 1rem 1.2rem;
            box-shadow: 0 6px 16px rgba(33, 56, 86, 0.06);
        }

        .status-overview {
            display: flex;
        }

        .status-card {
            flex: 1 1 170px;
            background: #f8f9fc;
            border: 1px solid #edf1f7;
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            min-width: 160px;
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
        }

        .status-card-value {
            font-size: 1rem;
            font-weight: 600;
            margin-top: 0.35rem;
            color: #1f2a3d;
            word-break: break-word;
        }

        .status-card-sub {
            font-size: 0.78rem;
            margin-top: 0.2rem;
        }

        .status-card--status {
            background: linear-gradient(135deg, #3a86ff, #4361ee);
            color: #fff;
            border: none;
            box-shadow: 0 12px 24px rgba(67, 97, 238, 0.25);
        }

        .status-card--status .status-card-label,
        .status-card--status .status-card-sub {
            color: rgba(255, 255, 255, 0.75);
        }

        .status-card--status .status-card-value {
            color: #fff;
        }

        .status-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.1rem;
            border-radius: 999px;
            border: none;
            color: #fff;
            background: linear-gradient(135deg, #3a86ff, #4361ee);
            box-shadow: 0 12px 24px rgba(67, 97, 238, 0.25);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .status-action:hover {
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

            .status-action {
                width: 100%;
                justify-content: center;
            }

            .status-card {
                min-width: 100%;
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

        .gallery-thumb img {
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

@push('scripts')
    <script>
        function confirmDeletion(fileType) {
            if (confirm('Are you sure you want to delete this file?')) {
                @this.call('removeFile', fileType);
            }
        }

        function confirmGalleryRemoval(section, path) {
            if (confirm('Are you sure you want to delete this photo?')) {
                @this.call('removeGalleryItem', section, path);
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
