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

    <x-detail-rental-request-tabs :contract-id="$contractId" />

    @include('livewire.components.waiting-overlay', [
        'target' => 'uploadDocuments',
        'title' => 'Uploading return documents',
        'subtitle' => 'We are storing the return evidence. This can take a few moments for larger files.',
    ])

    <div class="card shadow-sm border-0 rounded-4 mt-4">
        <div class="card-header bg-white border-0 rounded-top-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <h5 class="mb-0">Upload Return Documents</h5>
                <span class="text-muted small">Capture everything about the vehicle on return</span>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocuments">
                <div class="alert alert-info d-none d-flex align-items-center" data-upload-guard role="status">
                    <i class="bi bi-cloud-arrow-up me-2"></i>
                    <span data-upload-guard-text>Please wait for the current uploads to finish before adding more files.</span>
                </div>
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
                    @php $carDashboardRequired = empty($existingFiles['carDashboard']); @endphp
                    @php $insideRequired = empty($existingGalleries['inside']); @endphp
                    @php $outsideRequired = empty($existingGalleries['outside']); @endphp
                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="factorContract">
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
                            <input type="file" class="form-control @error('factorContract') is-invalid @enderror"
                                wire:model="factorContract" wire:loading.attr="disabled"
                                wire:target="factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                data-upload-field="factorContract">
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div class="mt-3 d-none" data-progress-container="factorContract"
                                wire:loading.class.remove="d-none" wire:target="factorContract">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">0%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span data-progress-status="factorContract">Ready for upload</span>
                                    <span data-progress-percent="factorContract">0%</span>
                                </div>
                            </div>
                            @error('factorContract')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="carDashboard">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="returnCarDashboardInput">
                                    KM/Fuel Photo @if ($carDashboardRequired)<span class="badge bg-danger-subtle text-danger ms-1">Required</span>@endif
                                </label>
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
                            <input id="returnCarDashboardInput" type="file" class="form-control @error('carDashboard') is-invalid @enderror"
                                wire:model="carDashboard" wire:loading.attr="disabled"
                                wire:target="factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                data-upload-field="carDashboard"
                                @if ($carDashboardRequired) aria-required="true" @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div class="mt-3 d-none" data-progress-container="carDashboard"
                                wire:loading.class.remove="d-none" wire:target="carDashboard">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">0%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span data-progress-status="carDashboard">Ready for upload</span>
                                    <span data-progress-percent="carDashboard">0%</span>
                                </div>
                            </div>
                            @error('carDashboard')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="carInsidePhotos">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="returnCarInsideInput">
                                    Inside Car Photos @if ($insideRequired)<span class="badge bg-danger-subtle text-danger ms-1">Required</span>@endif
                                </label>
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
                                <input id="returnCarInsideInput" type="file" class="form-control @error('carInsidePhotos') is-invalid @enderror @error('carInsidePhotos.*') is-invalid @enderror" wire:model="carInsidePhotos" accept="image/*"
                                    multiple
                                    wire:loading.attr="disabled"
                                    wire:target="factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                    data-upload-field="carInsidePhotos"
                                    @if ($insideRequired) aria-required="true" @endif>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12 photos
                                    in gallery.</small>
                            </div>

                            <div class="mt-3 d-none" data-progress-container="carInsidePhotos"
                                wire:loading.class.remove="d-none" wire:target="carInsidePhotos">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">0%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span data-progress-status="carInsidePhotos">Ready for upload</span>
                                    <span data-progress-percent="carInsidePhotos">0%</span>
                                </div>
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
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="carOutsidePhotos">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="returnCarOutsideInput">
                                    Outside Car Photos @if ($outsideRequired)<span class="badge bg-danger-subtle text-danger ms-1">Required</span>@endif
                                </label>
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
                                <input id="returnCarOutsideInput" type="file" class="form-control @error('carOutsidePhotos') is-invalid @enderror @error('carOutsidePhotos.*') is-invalid @enderror" wire:model="carOutsidePhotos" accept="image/*"
                                    multiple
                                    wire:loading.attr="disabled"
                                    wire:target="factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                    data-upload-field="carOutsidePhotos"
                                    @if ($outsideRequired) aria-required="true" @endif>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12 photos
                                    in gallery.</small>
                            </div>

                            <div class="mt-3 d-none" data-progress-container="carOutsidePhotos"
                                wire:loading.class.remove="d-none" wire:target="carOutsidePhotos">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">0%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span data-progress-status="carOutsidePhotos">Ready for upload</span>
                                    <span data-progress-percent="carOutsidePhotos">0%</span>
                                </div>
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
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="mileage">
                            <label class="form-label fw-semibold">Mileage</label>
                            <input type="number" class="form-control @error('mileage') is-invalid @enderror" wire:model="mileage" inputmode="numeric"
                                min="0" step="1" placeholder="Km on return">
                            <small class="text-muted d-block mt-2">Odometer reading on return</small>
                            @error('mileage')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="note">
                            <label class="form-label fw-semibold">Note (Optional)</label>
                            <textarea class="form-control @error('note') is-invalid @enderror" wire:model="note" rows="4" placeholder="Internal note for the team"></textarea>
                            @error('note')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white" data-validation-field="driverNote">
                            <label class="form-label fw-semibold">Driver Note (Optional)</label>
                            <textarea class="form-control @error('driverNote') is-invalid @enderror" wire:model="driverNote" rows="4" placeholder="Message for the driver"></textarea>
                            @error('driverNote')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="card shadow-sm h-100" data-validation-field="fuelLevel">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Fuel Level <span class="badge bg-danger-subtle text-danger ms-2">Required</span></span>
                                <span class="badge bg-primary">{{ $fuelLevel }}%</span>
                            </div>
                            <div class="card-body">
                                <label for="fuelRange" class="form-label">Select fuel level (%)</label>
                                <input type="range" class="form-range @error('fuelLevel') is-invalid @enderror" min="0" max="100" step="10" id="fuelRange"
                                    wire:model.live="fuelLevel" aria-required="true">
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>0%</span>
                                    <span>50%</span>
                                    <span>100%</span>
                                </div>
                                @error('fuelLevel')
                                    <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
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
                    <button type="submit" class="btn btn-primary btn-lg px-4" data-upload-submit
                        wire:loading.attr="disabled"
                        wire:target="factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments">Upload Documents</button>
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

        .validation-error-highlight {
            animation: validationPulse 1.4s ease;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.35) !important;
        }

        @keyframes validationPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.3);
            }

            70% {
                box-shadow: 0 0 0 0.6rem rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
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
        (() => {
            const resetTimers = {};
            let activeUploads = 0;
            const uploadErrors = new Set();
            const pendingSelections = new Set();

            const getUploadInputs = () => Array.from(document.querySelectorAll('[data-upload-field]'));

            const storeInitialDisabledState = (element) => {
                if (!element || element.dataset.uploadInitiallyDisabled !== undefined) {
                    return;
                }

                element.dataset.uploadInitiallyDisabled = element.disabled ? 'true' : 'false';
            };

            const setDisabledState = (element, shouldDisable) => {
                if (!element) {
                    return;
                }

                storeInitialDisabledState(element);

                if (shouldDisable) {
                    element.setAttribute('disabled', 'disabled');
                    return;
                }

                if ((element.dataset.uploadInitiallyDisabled ?? 'false') === 'true') {
                    element.setAttribute('disabled', 'disabled');
                } else {
                    element.removeAttribute('disabled');
                }
            };

            const toggleDisabledState = (shouldDisable) => {
                getUploadInputs().forEach((input) => {
                    setDisabledState(input, shouldDisable);
                });

                const submitButton = document.querySelector('[data-upload-submit]');
                setDisabledState(submitButton, shouldDisable);
            };

            const updateGuardAlert = () => {
                const guardAlert = document.querySelector('[data-upload-guard]');
                const guardText = document.querySelector('[data-upload-guard-text]');

                if (!guardAlert || !guardText) {
                    return;
                }

                if (uploadErrors.size > 0) {
                    guardText.textContent = 'Upload failed. Please reselect the files and try again.';
                    guardAlert.classList.remove('alert-info');
                    guardAlert.classList.add('alert-danger');
                    guardAlert.classList.remove('d-none');
                    return;
                }

                if (activeUploads > 0) {
                    guardText.textContent = 'Please wait for the current uploads to finish before adding more files.';
                    guardAlert.classList.remove('alert-danger');
                    guardAlert.classList.add('alert-info');
                    guardAlert.classList.remove('d-none');
                    return;
                }

                if (pendingSelections.size > 0) {
                    guardText.textContent = 'Preparing uploads... please wait.';
                    guardAlert.classList.remove('alert-danger');
                    guardAlert.classList.add('alert-info');
                    guardAlert.classList.remove('d-none');
                    return;
                }

                guardAlert.classList.add('d-none');
                guardAlert.classList.remove('alert-danger');
                guardAlert.classList.add('alert-info');
            };

            const updateFormLock = () => {
                const shouldDisable = activeUploads > 0 || uploadErrors.size > 0 || pendingSelections.size > 0;
                toggleDisabledState(shouldDisable);
                updateGuardAlert();
            };

            const scheduleReset = (field, delay) => {
                if (resetTimers[field]) {
                    window.clearTimeout(resetTimers[field]);
                }

                resetTimers[field] = window.setTimeout(() => {
                    const container = document.querySelector(`[data-progress-container="${field}"]`);
                    const bar = container ? container.querySelector('.progress-bar') : null;
                    const status = document.querySelector(`[data-progress-status="${field}"]`);
                    const percent = document.querySelector(`[data-progress-percent="${field}"]`);

                    if (!container) {
                        delete resetTimers[field];
                        return;
                    }

                    container.classList.add('d-none');

                    if (status) {
                        status.textContent = 'Ready for upload';
                    }

                    if (percent) {
                        percent.textContent = '0%';
                    }

                    if (bar) {
                        bar.style.width = '0%';
                        bar.setAttribute('aria-valuenow', '0');
                        bar.textContent = '0%';
                    }

                    delete resetTimers[field];
                }, delay);
            };

            const bindUploadHandlers = () => {
                getUploadInputs().forEach((input) => {
                    if (input.dataset.uploadHandlerBound === 'true') {
                        return;
                    }

                    input.dataset.uploadHandlerBound = 'true';
                    storeInitialDisabledState(input);

                    const field = input.dataset.uploadField;

                    const findContainer = () => document.querySelector(`[data-progress-container="${field}"]`);
                    const findBar = () => {
                        const container = findContainer();
                        return container ? container.querySelector('.progress-bar') : null;
                    };
                    const findStatus = () => document.querySelector(`[data-progress-status="${field}"]`);
                    const findPercent = () => document.querySelector(`[data-progress-percent="${field}"]`);

                    input.addEventListener('livewire-upload-start', () => {
                        pendingSelections.delete(field);
                        activeUploads += 1;
                        updateFormLock();

                        if (resetTimers[field]) {
                            window.clearTimeout(resetTimers[field]);
                            delete resetTimers[field];
                        }

                        const container = findContainer();
                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (container) {
                            container.classList.remove('d-none');
                        }

                        if (status) {
                            status.textContent = 'Uploading...';
                        }

                        if (percent) {
                            percent.textContent = '0%';
                        }

                        if (bar) {
                            bar.style.width = '0%';
                            bar.setAttribute('aria-valuenow', '0');
                            bar.textContent = '0%';
                        }
                    });

                    input.addEventListener('livewire-upload-progress', (event) => {
                        const progress = event.detail.progress ?? 0;

                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (bar) {
                            bar.style.width = `${progress}%`;
                            bar.setAttribute('aria-valuenow', String(progress));
                            bar.textContent = `${progress}%`;
                        }

                        if (percent) {
                            percent.textContent = `${progress}%`;
                        }

                        if (status) {
                            status.textContent = progress >= 100 ? 'Finishing...' : 'Uploading...';
                        }
                    });

                    const finalizeUpload = (isError = false) => {
                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (status) {
                            status.textContent = isError ? 'Upload failed' : 'Upload complete';
                        }

                        if (percent) {
                            percent.textContent = isError ? '0%' : '100%';
                        }

                        if (bar) {
                            const value = isError ? 0 : 100;
                            bar.style.width = `${value}%`;
                            bar.setAttribute('aria-valuenow', String(value));
                            bar.textContent = `${value}%`;
                        }

                        activeUploads = Math.max(activeUploads - 1, 0);

                        updateFormLock();

                        scheduleReset(field, isError ? 2000 : 800);
                    };

                    input.addEventListener('livewire-upload-finish', () => {
                        pendingSelections.delete(field);
                        uploadErrors.delete(field);
                        finalizeUpload(false);
                    });
                    input.addEventListener('livewire-upload-error', () => {
                        pendingSelections.add(field);
                        uploadErrors.add(field);
                        finalizeUpload(true);
                    });
                    input.addEventListener('livewire-upload-cancel', () => {
                        pendingSelections.add(field);
                        uploadErrors.add(field);
                        finalizeUpload(true);
                    });
                    input.addEventListener('change', () => {
                        if (input.files && input.files.length > 0) {
                            pendingSelections.add(field);
                        } else {
                            pendingSelections.delete(field);
                        }

                        if (uploadErrors.delete(field)) {
                            updateFormLock();
                            return;
                        }

                        updateFormLock();
                    });
                });
            };

            document.addEventListener('DOMContentLoaded', bindUploadHandlers);
            document.addEventListener('livewire:load', bindUploadHandlers);
            document.addEventListener('livewire:update', bindUploadHandlers);
            document.addEventListener('livewire:navigated', bindUploadHandlers);

            if (document.readyState !== 'loading') {
                bindUploadHandlers();
            }

            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('message.processed', bindUploadHandlers);
            }

            const submitButton = document.querySelector('[data-upload-submit]');
            storeInitialDisabledState(submitButton);
            updateFormLock();
        })();
    </script>
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

@include('components.panel.form-error-highlighter')
