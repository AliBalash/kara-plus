<div class="container">
    <div class="row g-3 align-items-center">
        <div class="col-lg-4">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Contract /</span> Pickup Document
            </h4>
        </div>
        @if (!empty($contractId))
            @php
                $customerName = optional($contract->customer)->fullName() ?? '—';
                $agreementDisplay = $agreement_number ?: optional($contract->pickupDocument)->agreement_number;
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
                            <div class="status-card-label"><i class="bi bi-info-circle me-2"></i>Status</div>
                            <div class="status-card-value">
                                {{ \Illuminate\Support\Str::headline($contract->current_status ?? 'draft') }}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-gradient-danger status-action flex-shrink-0"
                        onclick="window.confirm('Set this contract to Delivery for the rider?') && @this.changeStatusToDelivery({{ $contractId }})">
                        <i class="bx bx-send me-1"></i>
                        <span>Move to Delivery</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <x-detail-rental-request-tabs :contract-id="$contractId" />

    @include('livewire.components.waiting-overlay', [
        'target' => 'uploadDocuments',
        'title' => 'Uploading pickup documents',
        'subtitle' => 'We are syncing the files to the contract. Please keep this tab open.',
    ])

    <div class="card shadow-sm border-0 rounded-4 mt-4">
        <div class="card-header bg-white border-0 rounded-top-4 py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <h5 class="mb-0">Upload Pickup Documents</h5>
                <span class="text-muted small">Upload every pickup document in one place</span>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocuments">
                <div class="alert alert-info d-none d-flex align-items-center" data-upload-guard role="status">
                    <i class="bi bi-cloud-arrow-up me-2"></i>
                    <span>Please wait for the current uploads to finish before adding more files.</span>
                </div>
                {{-- Preview Modal --}}
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel">View Image</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modalImage" src="" class="img-fluid rounded" alt="Preview">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="tarsContract">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="tarsContractInput">
                                    Tars Contract
                                    <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                                </label>
                                @if (!empty($existingFiles['tarsContract']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('tars_contract')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['tarsContract']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['tarsContract'] }}" class="img-fluid preview-clickable"
                                        loading="lazy" decoding="async" fetchpriority="low" alt="Tars Contract"
                                        onclick="openModal('{{ $existingFiles['tarsContract'] }}')">
                                </div>
                            @endif
                            <input id="tarsContractInput" type="file"
                                class="form-control @error('tarsContract') is-invalid @enderror"
                                wire:model="tarsContract" wire:loading.attr="disabled"
                                wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                data-upload-field="tarsContract"
                                @if (empty($existingFiles['tarsContract'])) aria-required="true" @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div class="mt-3 d-none" data-progress-container="tarsContract"
                                wire:loading.class.remove="d-none" wire:target="tarsContract">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                                        style="width: 0%;">0%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-1">
                                    <span data-progress-status="tarsContract">Ready for upload</span>
                                    <span data-progress-percent="tarsContract">0%</span>
                                </div>
                            </div>
                            @error('tarsContract')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                            <div class="mt-3" data-validation-field="agreement_number">
                                <label class="form-label mb-1" for="agreementNumberInput">
                                    Agreement Number
                                    @if ($contract->kardo_required)
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    @endif
                                </label>
                                <input id="agreementNumberInput" type="text"
                                    class="form-control @error('agreement_number') is-invalid @enderror"
                                    placeholder="Enter agreement number" wire:model="agreement_number"
                                    @if ($contract->kardo_required) aria-required="true" @endif>
                                <small class="text-muted d-block">Letters and numbers only, up to 30
                                    characters.</small>
                                @error('agreement_number')
                                    <span class="text-danger small d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @if ($contract->kardo_required)
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                                data-validation-field="kardoContract">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-semibold mb-0" for="kardoContractInput">
                                        KARDO Contract
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    </label>
                                    @if (!empty($existingFiles['kardoContract']))
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="confirmDeletion('kardo_contract')">Remove</button>
                                    @endif
                                </div>
                                @if (!empty($existingFiles['kardoContract']))
                                    <div class="preview-wrapper mb-3">
                                        <img src="{{ $existingFiles['kardoContract'] }}" loading="lazy"
                                            decoding="async" fetchpriority="low" class="img-fluid preview-clickable"
                                            alt="Kardo Contract"
                                            onclick="openModal('{{ $existingFiles['kardoContract'] }}')">
                                    </div>
                                @endif
                                <input id="kardoContractInput" type="file"
                                    class="form-control @error('kardoContract') is-invalid @enderror"
                                    wire:model="kardoContract" wire:loading.attr="disabled"
                                    wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                    data-upload-field="kardoContract"
                                    @if (empty($existingFiles['kardoContract'])) aria-required="true" @endif>
                                <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                                <div class="mt-3 d-none" data-progress-container="kardoContract"
                                    wire:loading.class.remove="d-none" wire:target="kardoContract">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                            role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                            aria-valuenow="0" style="width: 0%;">0%
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mt-1">
                                        <span data-progress-status="kardoContract">Ready for upload</span>
                                        <span data-progress-percent="kardoContract">0%</span>
                                    </div>
                                </div>
                                @error('kardoContract')
                                    <span class="text-danger small d-block mt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @else
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div
                                class="document-card border rounded-3 p-3 h-100 shadow-sm bg-light d-flex flex-column align-items-center justify-content-center text-center">
                                <span class="fw-semibold mb-2">KARDO Contract</span>
                                <p class="text-muted small mb-0">KARDO inspection is not required for this contract.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="col-12 col-lg-6 col-xl-4">
                        @php $paymentOnDelivery = (bool) ($contract->payment_on_delivery ?? false); @endphp
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm {{ $paymentOnDelivery ? 'bg-white' : 'bg-light opacity-75' }}"
                            data-validation-field="factorContract">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="factorContractInput">
                                    Watcher's Receipt
                                    @if ($paymentOnDelivery)
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    @endif
                                </label>
                                @if (!empty($existingFiles['factorContract']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('factor_contract')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['factorContract']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['factorContract'] }}" loading="lazy"
                                        decoding="async" fetchpriority="low" class="img-fluid preview-clickable"
                                        alt="Watcher's Receipt"
                                        onclick="openModal('{{ $existingFiles['factorContract'] }}')">
                                </div>
                            @endif
                            <input id="factorContractInput" type="file"
                                class="form-control @error('factorContract') is-invalid @enderror"
                                wire:model="factorContract" wire:loading.attr="disabled"
                                wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                data-upload-field="factorContract" @if (!$paymentOnDelivery) disabled @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            @unless ($paymentOnDelivery)
                                <small class="text-muted d-block">Not needed when Payment on Delivery is disabled.</small>
                            @endunless
                            <div class="mt-3 d-none" data-progress-container="factorContract"
                                wire:loading.class.remove="d-none" wire:target="factorContract">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                                        style="width: 0%;">0%
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
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="carDashboard">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="carDashboardInput">
                                    KM/Fuel Photo
                                    @if (empty($existingFiles['carDashboard']))
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    @endif
                                </label>
                                @if (!empty($existingFiles['carDashboard']))
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                        onclick="confirmDeletion('car_dashboard')">Remove</button>
                                @endif
                            </div>
                            @if (!empty($existingFiles['carDashboard']))
                                <div class="preview-wrapper mb-3">
                                    <img src="{{ $existingFiles['carDashboard'] }}" loading="lazy" decoding="async"
                                        fetchpriority="low" class="img-fluid preview-clickable" alt="Dashboard Photo"
                                        onclick="openModal('{{ $existingFiles['carDashboard'] }}')">
                                </div>
                            @endif
                            <input id="carDashboardInput" type="file"
                                class="form-control @error('carDashboard') is-invalid @enderror"
                                wire:model="carDashboard" wire:loading.attr="disabled"
                                wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                data-upload-field="carDashboard"
                                @if (empty($existingFiles['carDashboard'])) aria-required="true" @endif>
                            <small class="text-muted d-block mt-2">JPG or PNG up to 8MB</small>
                            <div class="mt-3 d-none" data-progress-container="carDashboard"
                                wire:loading.class.remove="d-none" wire:target="carDashboard">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                                        style="width: 0%;">0%
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

                    @php $insideRequired = empty($existingGalleries['inside']); @endphp
                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="carInsidePhotos">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="pickupCarInsideInput">
                                    Inside Car Photos @if ($insideRequired)
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    @endif
                                </label>
                                <span class="badge bg-primary-subtle text-primary">Max 12</span>
                            </div>

                            @if (!empty($existingGalleries['inside']))
                                <div class="gallery-grid mb-3">
                                    @foreach ($existingGalleries['inside'] as $photo)
                                        <div class="gallery-thumb position-relative"
                                            wire:key="inside-existing-{{ md5($photo['path']) }}">
                                            <img src="{{ $photo['url'] }}"
                                                class="img-fluid rounded-3 preview-clickable gallery-img"
                                                loading="lazy" decoding="async" fetchpriority="low"
                                                alt="Inside car photo" onclick="openModal('{{ $photo['url'] }}')">
                                            <button type="button"
                                                class="btn btn-sm btn-danger rounded-circle gallery-remove"
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

                            <div>
                                <label class="form-label small fw-semibold text-muted mb-2">Add new photos</label>
                                <input id="pickupCarInsideInput" type="file"
                                    class="form-control @error('carInsidePhotos') is-invalid @enderror @error('carInsidePhotos.*') is-invalid @enderror"
                                    wire:model="carInsidePhotos" accept="image/*" multiple
                                    wire:loading.attr="disabled"
                                    wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                    data-upload-field="carInsidePhotos"
                                    @if ($insideRequired) aria-required="true" @endif>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12
                                    photos
                                    in gallery.</small>
                            </div>

                            <div class="mt-3 d-none" data-progress-container="carInsidePhotos"
                                wire:loading.class.remove="d-none" wire:target="carInsidePhotos">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                                        style="width: 0%;">0%
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

                    @php $outsideRequired = empty($existingGalleries['outside']); @endphp
                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="carOutsidePhotos">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0" for="pickupCarOutsideInput">
                                    Outside Car Photos @if ($outsideRequired)
                                        <span class="badge bg-danger-subtle text-danger ms-1">Required</span>
                                    @endif
                                </label>
                                <span class="badge bg-success-subtle text-success">Max 12</span>
                            </div>

                            @if (!empty($existingGalleries['outside']))
                                <div class="gallery-grid mb-3">
                                    @foreach ($existingGalleries['outside'] as $photo)
                                        <div class="gallery-thumb position-relative"
                                            wire:key="outside-existing-{{ md5($photo['path']) }}">
                                            <img src="{{ $photo['url'] }}"
                                                class="img-fluid rounded-3 preview-clickable gallery-img"
                                                loading="lazy" decoding="async" fetchpriority="low"
                                                alt="Outside car photo" onclick="openModal('{{ $photo['url'] }}')">
                                            <button type="button"
                                                class="btn btn-sm btn-danger rounded-circle gallery-remove"
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

                            <div>
                                <label class="form-label small fw-semibold text-muted mb-2">Add new photos</label>
                                <input id="pickupCarOutsideInput" type="file"
                                    class="form-control @error('carOutsidePhotos') is-invalid @enderror @error('carOutsidePhotos.*') is-invalid @enderror"
                                    wire:model="carOutsidePhotos" accept="image/*" multiple
                                    wire:loading.attr="disabled"
                                    wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments"
                                    data-upload-field="carOutsidePhotos"
                                    @if ($outsideRequired) aria-required="true" @endif>
                                <small class="text-muted d-block mt-2">JPG, PNG or WEBP up to 8MB each. Maximum 12
                                    photos
                                    in gallery.</small>
                            </div>

                            <div class="mt-3 d-none" data-progress-container="carOutsidePhotos"
                                wire:loading.class.remove="d-none" wire:target="carOutsidePhotos">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
                                        style="width: 0%;">0%
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
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="mileage">
                            <label class="form-label fw-semibold" for="pickupMileageInput">Mileage <span
                                    class="badge bg-danger-subtle text-danger ms-2">Required</span></label>
                            <input id="pickupMileageInput" type="number"
                                class="form-control @error('mileage') is-invalid @enderror" wire:model="mileage"
                                inputmode="numeric" min="0" step="1" placeholder="Km on pickup"
                                aria-required="true">
                            <small class="text-muted d-block mt-2">Current odometer at pickup</small>
                            @error('mileage')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="note">
                            <label class="form-label fw-semibold">Note (Optional)</label>
                            <textarea class="form-control @error('note') is-invalid @enderror" wire:model="note" rows="4"
                                placeholder="Internal note for the team"></textarea>
                            @error('note')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white"
                            data-validation-field="driverNote">
                            <label class="form-label fw-semibold">Driver Note (Optional)</label>
                            <textarea class="form-control @error('driverNote') is-invalid @enderror" wire:model="driverNote" rows="4"
                                placeholder="Message for the driver"></textarea>
                            @error('driverNote')
                                <span class="text-danger small d-block mt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 col-xl-4">
                        <div class="card shadow-sm h-100" data-validation-field="fuelLevel">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>Fuel Level <span
                                        class="badge bg-danger-subtle text-danger ms-2">Required</span></span>
                                <span class="badge bg-primary">{{ $fuelLevel }}%</span>
                            </div>
                            <div class="card-body">
                                <label for="fuelRange" class="form-label">Select fuel level (%)</label>
                                <input type="range" class="form-range @error('fuelLevel') is-invalid @enderror"
                                    min="0" max="100" step="10" id="fuelRange"
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

                    @if (!empty($costBreakdown) || !empty($depositDetails))
                        <div class="col-12 col-xl-6">
                            <div class="document-card border rounded-3 p-3 h-100 shadow-sm bg-white">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-semibold mb-0">Cost Breakdown</h6>
                                        <span class="text-muted small">View pricing details</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#costBreakdownCollapse"
                                        aria-expanded="false" aria-controls="costBreakdownCollapse">
                                        Toggle
                                    </button>
                                </div>
                                <div class="collapse mt-3" id="costBreakdownCollapse">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="d-none d-md-table-cell">Details</th>
                                                    <th class="text-end">Amount (AED)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($costBreakdown as $item)
                                                    <tr>
                                                        <td class="fw-semibold">{{ $item['label'] }}</td>
                                                        <td class="text-muted small d-none d-md-table-cell">
                                                            {{ $item['description'] ?? '—' }}
                                                        </td>
                                                        <td class="text-end fw-semibold">
                                                            {{ number_format($item['amount'], 2) }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="table-light">
                                                    <td class="fw-semibold">Subtotal</td>
                                                    <td class="d-none d-md-table-cell"></td>
                                                    <td class="text-end fw-semibold">
                                                        {{ number_format($costSummary['subtotal'], 2) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="fw-semibold">Tax</td>
                                                    <td class="text-muted small d-none d-md-table-cell">5% VAT</td>
                                                    <td class="text-end fw-semibold">
                                                        {{ number_format($costSummary['tax'], 2) }}</td>
                                                </tr>
                                                <tr class="table-primary">
                                                    <td class="fw-semibold">Total Amount</td>
                                                    <td class="d-none d-md-table-cell"></td>
                                                    <td class="text-end fw-bold">
                                                        {{ number_format($costSummary['total'], 2) }}</td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <td class="fw-semibold">Remaining Balance</td>
                                                    <td class="text-muted small d-none d-md-table-cell">After recorded
                                                        payments</td>
                                                    <td class="text-end fw-bold text-warning">
                                                        {{ number_format($costSummary['remaining'], 2) }}</td>
                                                </tr>
                                                @if (!empty($depositDetails))
                                                    <tr class="table-info">
                                                        <td class="fw-semibold">
                                                            <div>Security Hold Instructions</div>
                                                            @if (!empty($depositDetails['detail']))
                                                                <div class="text-muted small d-md-none mt-1">
                                                                    {{ $depositDetails['detail'] }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="d-none d-md-table-cell">
                                                            <div class="fw-semibold">
                                                                {{ $depositDetails['label'] ?? 'Security Hold' }}</div>
                                                            @if (!empty($depositDetails['detail']))
                                                                <div class="text-muted small">
                                                                    {{ $depositDetails['detail'] }}</div>
                                                            @endif
                                                        </td>
                                                        <td class="text-end fw-semibold">
                                                            @if (($depositDetails['category'] ?? null) === 'cash_aed' && ($depositDetails['amount'] ?? null) !== null)
                                                                {{ number_format($depositDetails['amount'], 2) }} AED
                                                            @else
                                                                <span class="text-muted">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($contract->payment_on_delivery)
                        <div class="col-12 col-lg-6 col-xl-4">
                            <div
                                class="card shadow-sm h-100 border-{{ $remainingBalance > 0 ? 'warning' : 'success' }}">
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
                        wire:target="tarsContract,kardoContract,factorContract,carDashboard,carInsidePhotos,carOutsidePhotos,uploadDocuments">
                        Upload Documents
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (!empty($customerDocuments['passport']) || !empty($customerDocuments['license']))
        <div class="card shadow-sm border-0 rounded-4 mt-4">
            <div
                class="card-header bg-white border-0 rounded-top-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Customer Identity Documents</h5>
                <span class="badge bg-label-primary">Available</span>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @php $docSections = ['passport' => 'Passport', 'license' => 'Driver License']; @endphp
                    @foreach ($docSections as $docKey => $docLabel)
                        @if (!empty($customerDocuments[$docKey]))
                            <div class="col-12 col-lg-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <h6 class="text-muted text-uppercase small mb-3">{{ $docLabel }}</h6>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach ($customerDocuments[$docKey] as $file)
                                            <div class="border rounded-3 p-2 text-center flex-grow-1"
                                                style="min-width: 160px;">
                                                <div class="fw-semibold small mb-2">{{ $file['label'] }}</div>
                                                @if ($file['is_pdf'])
                                                    <a href="{{ $file['url'] }}"
                                                        class="btn btn-outline-primary btn-sm" target="_blank">
                                                        <i class="bx bxs-file-pdf me-1"></i> View PDF
                                                    </a>
                                                @else
                                                    <img src="{{ $file['url'] }}"
                                                        class="img-fluid rounded preview-clickable" loading="lazy"
                                                        decoding="async" fetchpriority="low"
                                                        alt="{{ $docLabel }} {{ $file['label'] }}"
                                                        onclick="openModal('{{ $file['url'] }}')">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif
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

        .status-action {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.1rem;
            border-radius: 999px;
            border: none;
            color: #fff;
            background: linear-gradient(135deg, #ff6f61, #ff3b3b);
            box-shadow: 0 12px 24px rgba(255, 68, 41, 0.25);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .status-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(255, 68, 41, 0.3);
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            min-height: 220px;
        }

        .document-card .preview-wrapper img,
        .document-card .preview-wrapper video {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.35rem;
            aspect-ratio: 4 / 3;
        }

        .gallery-thumb img,
        .gallery-thumb video {
            width: 100%;
            height: 100%;
            object-fit: contain;
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

            .document-card .preview-wrapper {
                min-height: 180px;
                padding: 0.5rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            const resetTimers = {};
            let activeUploads = 0;

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

                const guardAlert = document.querySelector('[data-upload-guard]');

                if (guardAlert) {
                    guardAlert.classList.toggle('d-none', !shouldDisable);
                }

                const submitButton = document.querySelector('[data-upload-submit]');
                setDisabledState(submitButton, shouldDisable);
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

                    const findContainer = () => document.querySelector(
                        `[data-progress-container="${field}"]`);
                    const findBar = () => {
                        const container = findContainer();
                        return container ? container.querySelector('.progress-bar') : null;
                    };
                    const findStatus = () => document.querySelector(`[data-progress-status="${field}"]`);
                    const findPercent = () => document.querySelector(`[data-progress-percent="${field}"]`);

                    input.addEventListener('livewire-upload-start', () => {
                        activeUploads += 1;

                        if (activeUploads === 1) {
                            toggleDisabledState(true);
                        }

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

                        if (activeUploads === 0) {
                            toggleDisabledState(false);
                        }

                        scheduleReset(field, isError ? 2000 : 800);
                    };

                    input.addEventListener('livewire-upload-finish', () => finalizeUpload(false));
                    input.addEventListener('livewire-upload-error', () => finalizeUpload(true));
                    input.addEventListener('livewire-upload-cancel', () => finalizeUpload(true));
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
