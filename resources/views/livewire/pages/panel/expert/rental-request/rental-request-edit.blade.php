<div>
    <div class="row g-3 align-items-center">
        <div class="col-lg-6">
            <h4 class="fw-bold py-3 mb-0">
                <span class="text-muted fw-light">Rental Request /</span> Edit Request
            </h4>
        </div>

        <div class="col-lg-6">
            @if (is_null($contract->user_id))
                <div class="assign-toolbar d-flex flex-column flex-sm-row align-items-sm-center justify-content-lg-end gap-3">
                    <div class="assign-pill text-sm-start text-lg-end">
                        <span class="assign-pill-label">Assignee</span>
                        <span class="assign-pill-value assign-pill-value--warning">
                            <i class="bx bx-time-five"></i> Unassigned
                        </span>
                    </div>
                    <button type="button" class="btn assign-action btn-gradient-ocean"
                        onclick="window.confirm('Assign this contract to yourself?') && @this.assignToMe({{ $contract->id }})">
                        <i class="bx bx-user-plus me-1"></i>
                        <span>Assign to Me</span>
                    </button>
                </div>
            @else
                <div class="assign-toolbar d-flex flex-column flex-sm-row align-items-sm-center justify-content-lg-end gap-3">
                    <div class="assign-pill text-sm-start text-lg-end">
                        <span class="assign-pill-label">Assigned to</span>
                        <span class="assign-pill-value assign-pill-value--primary">
                            <i class="bx bx-user-circle"></i>
                            {{ optional($contract->user)->shortName() ?? 'Team Member' }}
                        </span>
                    </div>
                    <button type="button" class="btn assign-action btn-gradient-sunset"
                        onclick="window.confirm('Set this contract status to Booking?') && @this.changeStatusToReserve({{ $contract->id }})">
                        <i class="bx bxs-log-in-circle me-1"></i>
                        <span>Set to Booking</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if (session()->has('info'))
        <div class="alert alert-info animate__animated animate__fadeIn">
            {{ session('info') }}
        </div>
    @endif

    @if (session()->has('success'))
        <div class="alert alert-success animate__animated animate__fadeIn">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger animate__animated animate__fadeIn">
            {{ session('error') }}
        </div>
    @endif

    @include('livewire.components.waiting-overlay', [
        'target' => 'submit',
        'title' => 'Updating rental request',
        'subtitle' => 'We are syncing your latest changes. Hang tight for a moment.',
    ])

    <div class="card info-launch-card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar flex-shrink-0 avatar-md">
                    <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-copy"></i></span>
                </div>
                <div>
                    <p class="text-uppercase text-muted fw-semibold small mb-1">Customer-ready</p>
                    <h5 class="mb-1">Delivery & Return Brief</h5>
                    <p class="text-muted mb-0 small">Open the modal to review and copy the exact text we will share.</p>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-lg-end">
                <button type="button" class="btn btn-outline-primary info-launch-action"
                    data-bs-toggle="modal" data-bs-target="#infoPreviewModal" data-info-type="delivery"
                    data-info-title="Delivery Information" data-info-source="#deliveryInfoSource"
                    data-empty-label="No delivery text provided yet">
                    <i class="bx bx-send"></i>
                    <span>View delivery note</span>
                </button>

                <button type="button" class="btn btn-outline-warning info-launch-action"
                    data-bs-toggle="modal" data-bs-target="#infoPreviewModal" data-info-type="return"
                    data-info-title="Return Information" data-info-source="#returnInfoSource"
                    data-empty-label="No return text provided yet">
                    <i class="bx bx-reset"></i>
                    <span>View return note</span>
                </button>
            </div>
        </div>
    </div>

    <div class="visually-hidden" aria-hidden="true">
        <div id="deliveryInfoSource">{{ e(trim((string) $deliveryInformation)) }}</div>
        <div id="returnInfoSource">{{ e(trim((string) $returnInformation)) }}</div>
    </div>

    <div class="modal fade" id="infoPreviewModal" tabindex="-1" aria-labelledby="infoPreviewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0 rounded-3">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <p class="text-uppercase small text-muted fw-semibold mb-1">Customer-ready note</p>
                        <h5 class="modal-title fw-bold mb-0" id="infoPreviewModalLabel">Information Preview</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div
                        class="info-preview-topper p-3 rounded-3 mb-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <span
                                class="badge rounded-pill bg-label-primary text-uppercase small fw-semibold info-preview-type"
                                data-info-type-label>Delivery</span>
                            <div class="text-muted small info-preview-meta">
                                <span data-info-meta="length">0 chars</span>
                                <span class="mx-2">•</span>
                                <span data-info-meta="words">0 words</span>
                            </div>
                        </div>
                        <span class="badge bg-label-secondary text-muted info-preview-state" data-info-state>Ready to share</span>
                    </div>

                    <div class="info-preview-box">
                        <div class="info-empty-placeholder text-center p-4 d-none" data-info-empty>
                            <div class="w-100">
                                <i class="bx bx-chat text-warning fs-1 mb-2"></i>
                                <p class="fw-semibold mb-1">No content available</p>
                                <span class="text-muted small">Provide text to preview and copy.</span>
                            </div>
                        </div>
                        <div class="info-preview-text" data-info-preview></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bx bx-info-circle"></i>
                        <span>Text shown here matches what will be copied.</span>
                    </div>
                    <button type="button" id="copyInfoModalButton"
                        class="btn btn-gradient-ocean d-flex align-items-center gap-2"
                        data-default-label="Copy text" data-success-label="Copied!"
                        data-empty-label="No text to copy">
                        <i class="bx bx-clipboard copy-icon"></i>
                        <i class="bx bx-check-circle success-icon d-none"></i>
                        <span class="status-text">Copy text</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <x-detail-rental-request-tabs :contract-id="$contract->id" />

    <form wire:submit.prevent="submit" novalidate>
        <div class="row">
            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm border-0">
                    <h5 class="card-header bg-primary text-white mb-3 rounded-top">Customer Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="mb-3 mt-3" data-validation-field="first_name">
                            <label class="form-label fw-semibold mb-1" for="editFirstNameInput">
                                First Name <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-user"></i></span>
                                <input id="editFirstNameInput" type="text"
                                    class="form-control @error('first_name') is-invalid @enderror"
                                    placeholder="First Name" wire:model="first_name" data-bs-toggle="tooltip"
                                    title="Enter customer's first name">
                            </div>
                            @error('first_name')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="last_name">
                            <label class="form-label fw-semibold mb-1" for="editLastNameInput">
                                Last Name <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-user"></i></span>
                                <input id="editLastNameInput" type="text"
                                    class="form-control @error('last_name') is-invalid @enderror"
                                    placeholder="Last Name" wire:model="last_name" data-bs-toggle="tooltip"
                                    title="Enter customer's last name">
                            </div>
                            @error('last_name')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="email">
                            <label class="form-label fw-semibold mb-1" for="editEmailInput">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                <input id="editEmailInput" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="Email" wire:model="email" data-bs-toggle="tooltip"
                                    title="Enter customer's email address">
                            </div>
                            @error('email')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="phone">
                            <label class="form-label fw-semibold mb-1" for="editPhoneInput">
                                Phone <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                <input id="editPhoneInput" type="tel"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    placeholder="Phone" wire:model="phone" data-bs-toggle="tooltip"
                                    title="Enter customer's phone number">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="messenger_phone">
                            <label class="form-label fw-semibold mb-1" for="editMessengerPhoneInput">
                                Messenger Phone <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-chat"></i></span>
                                <input id="editMessengerPhoneInput" type="tel"
                                    class="form-control @error('messenger_phone') is-invalid @enderror"
                                    placeholder="Messenger Phone" wire:model="messenger_phone" data-bs-toggle="tooltip"
                                    title="Enter customer's messenger phone number">
                            </div>
                            @error('messenger_phone')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="address">
                            <label class="form-label fw-semibold mb-1" for="editAddressInput">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-home"></i></span>
                                <input id="editAddressInput" type="text" class="form-control @error('address') is-invalid @enderror"
                                    placeholder="Address" wire:model="address" data-bs-toggle="tooltip"
                                    title="Enter customer's address">
                            </div>
                            @error('address')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="birth_date">
                            <label class="form-label fw-semibold mb-1" for="editBirthDateInput">Birth Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="editBirthDateInput" type="date"
                                    class="form-control @error('birth_date') is-invalid @enderror"
                                    max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" wire:model="birth_date"
                                    data-bs-toggle="tooltip" title="Enter customer's birth date">
                            </div>
                            @error('birth_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="national_code">
                            <label class="form-label fw-semibold mb-1" for="editNationalCodeInput">
                                National Code <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-id-card"></i></span>
                                <input id="editNationalCodeInput" type="text"
                                    class="form-control @error('national_code') is-invalid @enderror"
                                    placeholder="National Code" wire:model="national_code" data-bs-toggle="tooltip"
                                    title="Enter customer's national code">
                            </div>
                            @error('national_code')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="passport_number">
                            <label class="form-label fw-semibold mb-1" for="editPassportNumberInput">Passport Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bxs-plane-take-off"></i></span>
                                <input id="editPassportNumberInput" type="text"
                                    class="form-control @error('passport_number') is-invalid @enderror"
                                    placeholder="Passport Number" wire:model="passport_number" data-bs-toggle="tooltip"
                                    title="Enter customer's passport number">
                            </div>
                            @error('passport_number')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="passport_expiry_date">
                            <label class="form-label fw-semibold mb-1" for="editPassportExpiryInput">Passport Expiry Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="editPassportExpiryInput" type="date"
                                    class="form-control @error('passport_expiry_date') is-invalid @enderror"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" wire:model="passport_expiry_date"
                                    data-bs-toggle="tooltip" title="Enter passport expiry date">
                            </div>
                            @error('passport_expiry_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="nationality">
                            <label class="form-label fw-semibold mb-1" for="editNationalityInput">
                                Nationality <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-flag"></i></span>
                                <input id="editNationalityInput" type="text"
                                    class="form-control @error('nationality') is-invalid @enderror"
                                    placeholder="Nationality" wire:model="nationality" data-bs-toggle="tooltip"
                                    title="Enter customer's nationality">
                            </div>
                            @error('nationality')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-card"></i></span>
                            <input type="text" class="form-control @error('license_number') is-invalid @enderror"
                                placeholder="License Number" wire:model="license_number" data-bs-toggle="tooltip"
                                title="Enter customer's license number">
                            @error('license_number')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-user-check"></i></span>
                            <input type="text"
                                class="form-control @error('licensed_driver_name') is-invalid @enderror"
                                placeholder="Licensed Driver Name" wire:model="licensed_driver_name"
                                data-bs-toggle="tooltip"
                                title="Enter the name of the licensed driver handling the booking">
                            @error('licensed_driver_name')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-check-circle"></i></span>
                            <div class="form-check form-check-inline mt-2 ms-2">
                                <input type="checkbox" class="form-check-input" wire:model="kardo_required"
                                    id="kardo_required">
                                <label class="form-check-label" for="kardo_required">KARDO Required</label>
                            </div>
                            @error('kardo_required')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-money"></i></span>
                            <div class="form-check form-check-inline mt-2 ms-2">
                                <input type="checkbox" class="form-check-input" wire:model="payment_on_delivery"
                                    id="payment_on_delivery">
                                <label class="form-check-label" for="payment_on_delivery">Payment on Delivery</label>
                            </div>
                            @error('payment_on_delivery')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($payment_on_delivery)
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bx bx-chat"></i></span>
                                <textarea class="form-control @error('driver_note') is-invalid @enderror" rows="2"
                                    wire:model="driver_note" placeholder="Driver Note for Pickup" data-bs-toggle="tooltip"
                                    title="Note shown to the driver on pickup document"></textarea>
                                @error('driver_note')
                                    <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-shield"></i></span>
                            <textarea class="form-control @error('deposit') is-invalid @enderror" rows="2" wire:model="deposit"
                                placeholder="Deposit instructions" data-bs-toggle="tooltip"
                                title="Notes about security deposit shown on pickup document"></textarea>
                            @error('deposit')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contract Information -->
            <div class="col-md-6">
                <!-- Car Information -->
                <div class="card mb-4 shadow-sm border-0">
                    <h5 class="card-header bg-primary text-white mb-3 rounded-top">Car Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="mb-3" data-validation-field="selectedBrand">
                            <label class="form-label fw-semibold mb-1" for="editSelectedBrandInput">
                                Car Brand <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-car"></i></span>
                                <select id="editSelectedBrandInput"
                                    class="form-control @error('selectedBrand') is-invalid @enderror"
                                    wire:model.live="selectedBrand" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select car brand">
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('selectedBrand')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        @if ($selectedBrand)
                            <div class="mb-3" data-validation-field="selectedModelId">
                                <label class="form-label fw-semibold mb-1" for="editSelectedModelInput">
                                    Car Model <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-car"></i></span>
                                    <select id="editSelectedModelInput"
                                        class="form-control @error('selectedModelId') is-invalid @enderror"
                                        wire:model.live="selectedModelId" aria-required="true" data-bs-toggle="tooltip"
                                        title="Select car model">
                                        <option value="">Select Model</option>
                                        @foreach ($models as $model)
                                            <option value="{{ $model->id }}">{{ $model->model }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('selectedModelId')
                                    <div class="invalid-feedback animate__animated animate__fadeIn">
                                        {{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($selectedModelId)
                            <div class="mb-3" data-validation-field="selectedCarId">
                                <label class="form-label fw-semibold mb-1" for="editSelectedCarInput">
                                    Car <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-car"></i></span>
                                    <select id="editSelectedCarInput"
                                        class="form-control @error('selectedCarId') is-invalid @enderror"
                                        wire:model.live="selectedCarId" aria-required="true" data-bs-toggle="tooltip"
                                        title="Select available car">
                                        <option value="">Select Car</option>
                                        @foreach ($carsForModel as $car)
                                            <option value="{{ $car['id'] }}"
                                                @class([
                                                    'text-warning' => $car['status'] !== 'available' || ! $car['availability'],
                                                ])>
                                                {{ $car['plate_number'] }} - {{ $car['manufacturing_year'] }} -
                                                {{ $car['color'] ?? 'N/A' }} -
                                                [{{ ucfirst(str_replace('_', ' ', $car['status'])) }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('selectedCarId')
                                    <div class="invalid-feedback animate__animated animate__fadeIn">
                                        {{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($selectedCarId)
                            @php
                                $selectedCar = App\Models\Car::with(['carModel', 'currentContract.customer'])->find($selectedCarId);
                                $reservations = $this->getCarReservations($selectedCarId);
                            @endphp
                            <div class="mt-3 p-3 border rounded bg-light">
                                <h6 class="text-primary">Selected Car Details</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Plate Number:</strong> {{ $selectedCar->plate_number }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Year:</strong> {{ $selectedCar->manufacturing_year }}
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Color:</strong> {{ $selectedCar->color ?? 'N/A' }}
                                    </div>
                                    <div class="col-md-4 mt-2">
                                        <strong>Fleet Status:</strong>
                                        <span class="badge bg-label-{{ $selectedCar->availability ? 'success' : 'danger' }}">
                                            {{ $selectedCar->availability ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <div class="col-md-4 mt-2">
                                        <strong>Internal Status:</strong>
                                        <span class="badge bg-label-secondary text-uppercase">
                                            {{ str_replace('_', ' ', $selectedCar->status) }}
                                        </span>
                                    </div>
                                </div>
                                @if ($selectedCar->currentContract)
                                    @php
                                        $activeContract = $selectedCar->currentContract;
                                        $statusLabel = App\Support\ContractStatus::label($activeContract->current_status);
                                    @endphp
                                    <div class="row mt-2">
                                        <div class="col-md-12">
                                            <strong>Active Contract:</strong>
                                            <div class="alert alert-warning py-2 mb-0">
                                                <div class="fw-semibold">#{{ $activeContract->id }} · {{ $statusLabel }}</div>
                                                <div class="small text-muted">
                                                    {{ optional($activeContract->pickup_date)->format('Y-m-d H:i') ?? '—' }}
                                                    →
                                                    {{ optional($activeContract->return_date)->format('Y-m-d H:i') ?? '—' }}
                                                    @if ($activeContract->customer)
                                                        · {{ $activeContract->customer->fullName() }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <strong>Price Tiers:</strong>
                                        <div class="d-flex flex-wrap">
                                            <span class="badge bg-secondary m-1">1-6 days:
                                                {{ number_format((float) $selectedCar->price_per_day_short, 2) }} AED</span>
                                            <span class="badge bg-secondary m-1">7-28 days:
                                                {{ number_format((float) $selectedCar->price_per_day_mid, 2) }} AED</span>
                                            <span class="badge bg-secondary m-1">28+ days:
                                                {{ number_format((float) $selectedCar->price_per_day_long, 2) }} AED</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input"
                                            wire:model.live="apply_discount" id="apply_discount">
                                        <label class="form-check-label" for="apply_discount">Apply Discount
                                            (Custom Daily Rate)</label>
                                    </div>
                                    @if ($apply_discount)
                                        <div class="input-group mb-3">
                                            <span class="input-group-text"><i class="bx bx-discount"></i></span>
                                            <input type="number" step="0.01"
                                                class="form-control @error('custom_daily_rate') is-invalid @enderror"
                                                wire:model.live="custom_daily_rate"
                                                placeholder="Enter discounted daily rate (e.g. 180 AED)">
                                            @error('custom_daily_rate')
                                                <div class="invalid-feedback animate__animated animate__fadeIn">
                                                    {{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <strong>Future Reservations:</strong>
                                        @if (count($reservations) > 0)
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>From</th>
                                                            <th>To</th>
                                                            <th>Status</th>
                                                            <th>Contract</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($reservations as $reservation)
                                                            @php
                                                                $now = Carbon\Carbon::now();
                                                                $pickup = Carbon\Carbon::parse(
                                                                    $reservation['pickup_date'],
                                                                );
                                                                $return = Carbon\Carbon::parse(
                                                                    $reservation['return_date'],
                                                                );
                                                                $isCurrent =
                                                                    $pickup->lessThanOrEqualTo($now) &&
                                                                    $return->greaterThanOrEqualTo($now);
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $reservation['pickup_date'] }}</td>
                                                                <td>{{ $reservation['return_date'] }}</td>
                                                                <td>
                                                                    @php
                                                                        $statusLabel = App\Support\ContractStatus::label($reservation['status']);
                                                                        $statusBadge = App\Support\ContractStatus::badgeClass($reservation['status']);
                                                                    @endphp
                                                                    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                                                </td>
                                                                <td class="text-nowrap">
                                                                    <span class="fw-semibold">#{{ $reservation['id'] }}</span>
                                                                    @if ($isCurrent)
                                                                        <span class="badge bg-danger ms-2">In Progress</span>
                                                                    @else
                                                                        <span class="badge bg-warning text-dark ms-2">Scheduled</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <p class="text-success">No future reservations for this car.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Contract Details -->
                <div class="card mb-4 shadow-sm border-0">
                    <h5 class="card-header bg-primary text-white mb-3 rounded-top">Contract Details</h5>
                    <div class="card-body">
                        <!-- Location & Dates -->
                        <h6 class="text-primary mb-3">Location & Dates</h6>
                        <div class="mb-3" data-validation-field="pickup_location">
                            <label class="form-label fw-semibold mb-1" for="editPickupLocationInput">
                                Pickup Location <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-map"></i></span>
                                <select id="editPickupLocationInput"
                                    class="form-control @error('pickup_location') is-invalid @enderror"
                                    wire:model.live="pickup_location" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select pickup location">
                                    <option value="">Pickup Location</option>
                                    @foreach ($locationOptions as $location)
                                        <option value="{{ $location }}">{{ $location }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('pickup_location')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="return_location">
                            <label class="form-label fw-semibold mb-1" for="editReturnLocationInput">
                                Return Location <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-map"></i></span>
                                <select id="editReturnLocationInput"
                                    class="form-control @error('return_location') is-invalid @enderror"
                                    wire:model.live="return_location" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select return location">
                                    <option value="">Return Location</option>
                                    @foreach ($locationOptions as $location)
                                        <option value="{{ $location }}">{{ $location }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('return_location')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="pickup_date">
                            <label class="form-label fw-semibold mb-1" for="editPickupDateInput">
                                Pickup Date & Time <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="editPickupDateInput" type="datetime-local"
                                    class="form-control @error('pickup_date') is-invalid @enderror"
                                    wire:model.live="pickup_date" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select pickup date and time">
                            </div>
                            @error('pickup_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="return_date">
                            <label class="form-label fw-semibold mb-1" for="editReturnDateInput">
                                Return Date & Time <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="editReturnDateInput" type="datetime-local"
                                    class="form-control @error('return_date') is-invalid @enderror"
                                    wire:model.live="return_date" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select return date and time">
                            </div>
                            @error('return_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-money"></i></span>
                            <input type="text" class="form-control" value="{{ number_format($final_total, 2) }} AED"
                                disabled data-bs-toggle="tooltip" title="Total contract amount">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-user"></i></span>
                            <select class="form-control @error('agent_sale') is-invalid @enderror"
                                wire:model="agent_sale" data-bs-toggle="tooltip" title="Select agent or Website">
                                @foreach ($salesAgents as $agent)
                                    <option value="{{ $agent }}">{{ $agent }}</option>
                                @endforeach
                            </select>
                            @error('agent_sale')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-note"></i></span>
                            <textarea class="form-control" wire:model="notes" placeholder="Contract Notes" data-bs-toggle="tooltip"
                                title="Add any contract notes">{{ $contract?->notes }}</textarea>
                        </div>

                        <!-- Services & Insurance -->
                        <h6 class="text-primary mb-3 mt-4">Services & Insurance</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-2">Additional Services</h6>
                                @foreach ($services as $key => $service)
                                    @if (!in_array($key, ['ldw_insurance', 'scdw_insurance']))
                                        @if ($key === 'child_seat')
                                            <div class="mb-3" data-validation-field="service_quantities.child_seat">
                                                <label class="form-label fw-semibold mb-1" for="child-seat-quantity">
                                                    <i class="fa {{ $service['icon'] }} me-2"></i>
                                                    {{ $service['label_en'] }} (per day)
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bx bx-child"></i></span>
                                                    <input id="child-seat-quantity" type="number" min="0"
                                                        class="form-control @error('service_quantities.child_seat') is-invalid @enderror"
                                                        wire:model.live="service_quantities.child_seat" placeholder="0"
                                                        data-bs-toggle="tooltip"
                                                        title="Enter the number of child seats to include">
                                                    <span class="input-group-text">AED/day</span>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    Total for {{ max($rental_days, 1) }} day(s):
                                                    <span class="fw-semibold">{{ number_format(($service_quantities['child_seat'] ?? 0) * $service['amount'] * max($rental_days, 1), 2) }} AED</span>
                                                </div>
                                                @error('service_quantities.child_seat')
                                                    <div class="invalid-feedback d-block animate__animated animate__fadeIn">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        @else
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox"
                                                    wire:model.live="selected_services" value="{{ $key }}"
                                                    id="service-{{ $key }}"
                                                    @if (in_array($key, $selected_services)) checked @endif
                                                    data-bs-toggle="tooltip" title="{{ $service['label_en'] }} details">
                                                <label class="form-check-label" for="service-{{ $key }}">
                                                    <i class="fa {{ $service['icon'] }} me-2"></i>
                                                    {{ $service['label_en'] }} -
                                                    @if ($service['amount'] > 0)
                                                        {{ number_format($service['amount'], 2) }} AED
                                                        @if ($service['per_day'])
                                                            /day
                                                        @endif
                                                    @else
                                                        Free
                                                    @endif
                                                </label>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-2">Insurance</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                        wire:model.live="selected_insurance" value="basic_insurance"
                                        id="insurance-basic" checked disabled data-bs-toggle="tooltip"
                                        title="Basic Insurance (Included)">
                                    <label class="form-check-label" for="insurance-basic">
                                        <i class="fa fa-shield-alt me-2"></i>
                                        Basic Insurance - Free
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                        wire:model.live="selected_insurance" value="" id="insurance-none"
                                        @if (is_null($selected_insurance)) checked @endif data-bs-toggle="tooltip"
                                        title="No Additional Insurance">
                                    <label class="form-check-label" for="insurance-none">
                                        <i class="fa fa-ban me-2"></i>
                                        No Additional Insurance - Free
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                        wire:model.live="selected_insurance" value="ldw_insurance" id="insurance-ldw"
                                        @if ($selected_insurance === 'ldw_insurance') checked @endif data-bs-toggle="tooltip"
                                        title="Loss Damage Waiver Insurance">
                                    <label class="form-check-label" for="insurance-ldw">
                                        <i class="fa {{ $services['ldw_insurance']['icon'] }} me-2"></i>
                                        {{ $services['ldw_insurance']['label_en'] }} -
                                        @if ($selectedCarId && $ldw_daily_rate > 0)
                                            {{ number_format($ldw_daily_rate, 2) }} AED/day
                                        @else
                                            --
                                        @endif
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio"
                                        wire:model.live="selected_insurance" value="scdw_insurance"
                                        id="insurance-scdw" @if ($selected_insurance === 'scdw_insurance') checked @endif
                                        data-bs-toggle="tooltip" title="Super Collision Damage Waiver Insurance">
                                    <label class="form-check-label" for="insurance-scdw">
                                        <i class="fa {{ $services['scdw_insurance']['icon'] }} me-2"></i>
                                        {{ $services['scdw_insurance']['label_en'] }} -
                                        @if ($selectedCarId && $scdw_daily_rate > 0)
                                            {{ number_format($scdw_daily_rate, 2) }} AED/day
                                        @else
                                            --
                                        @endif
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6 class="text-primary mb-3">Driving License (Optional)</h6>
                            <div class="list-group mb-3" data-validation-field="driving_license_option">
                                <label class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" value=""
                                        wire:model.live="driving_license_option">
                                    <span class="fw-semibold">No Driving License Processing</span>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" value="one_year"
                                        wire:model.live="driving_license_option">
                                    <div>
                                        <div class="fw-semibold">Driving License (1 Year)</div>
                                        <div class="text-muted small">{{ number_format($driving_license_options['one_year']['amount'], 2) }} AED</div>
                                    </div>
                                </label>
                                <label class="list-group-item d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" value="three_year"
                                        wire:model.live="driving_license_option">
                                    <div>
                                        <div class="fw-semibold">Driving License (3 Years)</div>
                                        <div class="text-muted small">{{ number_format($driving_license_options['three_year']['amount'], 2) }} AED</div>
                                    </div>
                                </label>
                            </div>

                            <h6 class="text-primary mb-3">Driver Service (Optional)</h6>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-6" data-validation-field="driver_hours">
                                    <label class="form-label fw-semibold mb-1" for="driverHoursInput">
                                        Driver Hours
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bx bx-time-five"></i></span>
                                        <input id="driverHoursInput" type="number" step="0.5" min="0"
                                            class="form-control @error('driver_hours') is-invalid @enderror"
                                            placeholder="e.g. 6 or 10" wire:model.live="driver_hours"
                                            data-bs-toggle="tooltip"
                                            title="Enter the total number of hours the driver is required">
                                    </div>
                                    @error('driver_hours')
                                        <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info shadow-sm mb-0">
                                        <div class="fw-semibold text-primary">Pricing details</div>
                                        <div class="small text-muted">
                                            First 8 hours cost 250 AED. Each extra hour adds 40 AED.
                                            Current driver charge:
                                            <span class="fw-semibold text-dark">{{ number_format($driver_cost, 2) }} AED</span>.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="my-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="text-primary mb-1">Cost Breakdown</h5>
                    <p class="text-muted small mb-0">Monitor the live totals and compare them with the original contract values.</p>
                </div>
                <span class="badge bg-label-primary text-primary d-inline-flex align-items-center gap-1">
                    <i class="bx bx-refresh"></i>
                    Live update
                </span>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header border-0 bg-transparent pb-0">
                            <h6 class="fw-semibold text-primary mb-0">Updated Amounts</h6>
                            <span class="text-muted small">Calculated from the current selections.</span>
                        </div>
                        <div class="card-body pb-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0 cost-breakdown-table">
                                    <tr>
                                        <th>Daily Rate @if ($apply_discount)<span class="badge bg-label-warning text-warning ms-1">Discounted</span>@endif</th>
                                        <td class="text-end">{{ number_format($dailyRate, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Base Rental Cost <span class="text-muted fw-normal">({{ $rental_days }} days)</span></th>
                                        <td class="text-end">{{ number_format($base_price, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Pickup Transfer Cost</th>
                                        <td class="text-end">{{ number_format($transfer_costs['pickup'], 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Return Transfer Cost</th>
                                        <td class="text-end">{{ number_format($transfer_costs['return'], 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Additional Services</th>
                                        <td class="text-end">{{ number_format($services_total, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Insurance</th>
                                        <td class="text-end">{{ number_format($insurance_total, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Driving License</th>
                                        <td class="text-end">{{ number_format($driving_license_cost, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Driver Service</th>
                                        <td class="text-end">{{ number_format($driver_cost, 2) }} AED</td>
                                    </tr>
                                    <tr class="fw-semibold text-primary">
                                        <th>Subtotal</th>
                                        <td class="text-end">{{ number_format($subtotal, 2) }} AED</td>
                                    </tr>
                                    <tr>
                                        <th>Tax (5%)</th>
                                        <td class="text-end">{{ number_format($tax_amount, 2) }} AED</td>
                                    </tr>
                                    <tr class="table-total-row">
                                        <th>Total Amount</th>
                                        <td class="text-end">{{ number_format($final_total, 2) }} AED</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if (!empty($comparisonRows))
                    <div class="col-lg-6">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-header border-0 bg-transparent pb-0 d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <h6 class="fw-semibold text-primary mb-0">Change Summary</h6>
                                    <span class="text-muted small">Original contract vs. current edits.</span>
                                </div>
                                <span class="badge bg-label-secondary text-muted">Comparative view</span>
                            </div>
                            <div class="card-body pb-0">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0 cost-comparison-table">
                                        @foreach ($comparisonRows as $row)
                                            <tr class="{{ $row['changed'] ? 'comparison-row--changed' : '' }}">
                                                <th class="small text-uppercase text-muted fw-semibold">{{ $row['label'] }}</th>
                                                <td class="text-muted">{{ $row['original'] }}</td>
                                                <td class="text-end">
                                                    <div class="d-flex flex-column align-items-end">
                                                        <span class="fw-semibold">{{ $row['current'] }}</span>
                                                        @if (!empty($row['change']))
                                                            @php
                                                                $changeType = $row['change']['type'];
                                                                $changeText = $row['change']['text'];
                                                            @endphp
                                                            @if (in_array($changeType, ['increase', 'decrease', 'changed']))
                                                                @php
                                                                    $badgeClass = [
                                                                        'increase' => 'bg-label-danger text-danger',
                                                                        'decrease' => 'bg-label-success text-success',
                                                                        'changed' => 'bg-label-primary text-primary',
                                                                    ][$changeType] ?? 'bg-label-secondary text-muted';
                                                                @endphp
                                                                <span class="badge rounded-pill mt-1 {{ $badgeClass }}">{{ $changeText }}</span>
                                                            @else
                                                                <span class="small text-muted mt-1 text-end">{{ $changeText }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="fixed-bottom bg-white p-3 shadow-lg d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-lg transition-all duration-300 hover:bg-primary-dark">
                <i class="bx bx-save me-2"></i> Update Contract
            </button>
        </div>
    </form>
</div>

@once
    @push('styles')
        <style>
            .assign-toolbar {
                background: #fff;
                border: 1px solid #e0e6ef;
                border-radius: 1rem;
                padding: 0.85rem 1rem;
                box-shadow: 0 6px 16px rgba(33, 56, 86, 0.06);
            }

            .assign-pill {
                display: flex;
                flex-direction: column;
                gap: 0.2rem;
            }

            .assign-pill-label {
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: #8a96aa;
                font-weight: 600;
            }

            .info-launch-card {
                background: linear-gradient(135deg, #f9fbff 0%, #f7f9ff 60%, #f2f5ff 100%);
                border: 1px solid #e5ecfa;
            }

            .info-launch-card .avatar.avatar-md {
                width: 56px;
                height: 56px;
                font-size: 1.25rem;
                background: #eef2ff;
            }

            .info-launch-action {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                border-radius: 999px;
                padding: 0.55rem 1rem;
                font-weight: 600;
                transition: all 0.16s ease;
                box-shadow: 0 6px 18px rgba(63, 136, 248, 0.12);
            }

            .info-launch-action:hover {
                transform: translateY(-1px);
                box-shadow: 0 10px 28px rgba(63, 136, 248, 0.16);
            }

            .info-preview-box {
                position: relative;
                border: 1px solid #dfe6f7;
                border-radius: 0.75rem;
                background: linear-gradient(180deg, #f8f9ff 0%, #ffffff 40%);
                min-height: 200px;
                overflow: hidden;
            }

            .info-preview-text {
                font-family: 'SFMono-Regular', 'Consolas', 'Liberation Mono', 'Menlo', monospace;
                white-space: pre-wrap;
                color: #2f3f68;
                background: #fff;
                padding: 1rem 1.25rem;
                border-top: 1px dashed #d4ddf2;
                min-height: 160px;
            }

            .info-empty-placeholder {
                position: absolute;
                inset: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                border-radius: 0.75rem;
                background: rgba(255, 193, 7, 0.04);
            }

            .info-preview-topper {
                background: #f4f6ff;
                border: 1px solid #e5eafe;
            }

            #copyInfoModalButton.is-copied {
                background: linear-gradient(135deg, #28c76f, #48da89);
                box-shadow: 0 10px 30px rgba(40, 199, 111, 0.32);
            }

            .assign-pill-value {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.28rem 0.75rem;
                border-radius: 999px;
                font-weight: 600;
            }

            .assign-pill-value--warning {
                background: rgba(255, 193, 7, 0.18);
                color: #a06a00;
            }

            .assign-pill-value--primary {
                background: rgba(63, 136, 248, 0.16);
                color: #1f4c97;
            }

            .assign-action {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.6rem 1.1rem;
                border-radius: 999px;
                border: none;
                color: #fff;
                font-weight: 600;
                box-shadow: 0 12px 24px rgba(32, 56, 90, 0.18);
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }

            .assign-action:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 28px rgba(32, 56, 90, 0.22);
            }

            .assign-action:active {
                transform: translateY(0);
            }

            .btn-gradient-ocean {
                background: linear-gradient(135deg, #3a86ff, #4361ee);
                color: #00d4ff;
            }

            .btn-gradient-sunset {
                background: linear-gradient(135deg, #ff9f43, #ff6f61);
            }

            .btn-gradient-sunset:hover,
            .btn-gradient-ocean:hover {
                color: #fff;
            }

            .cost-breakdown-table th,
            .cost-breakdown-table td,
            .cost-comparison-table th,
            .cost-comparison-table td {
                padding: 0.65rem 0.25rem;
                border-bottom: 1px dashed #e5e9f2;
            }

            .cost-breakdown-table tr:last-child th,
            .cost-breakdown-table tr:last-child td,
            .cost-comparison-table tr:last-child th,
            .cost-comparison-table tr:last-child td {
                border-bottom: none;
            }

            .cost-comparison-table td {
                min-width: 140px;
            }

            .comparison-row--changed th,
            .comparison-row--changed td {
                background: rgba(63, 136, 248, 0.06);
            }

            .table-total-row th,
            .table-total-row td {
                font-size: 1.05rem;
                font-weight: 700;
                color: #3f50f6;
            }

            @media (max-width: 575.98px) {
                .assign-toolbar {
                    gap: 0.75rem;
                }

                .assign-action {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
@endpush
@endonce

@include('components.panel.form-error-highlighter')

@section('styles')
    <style>
        .transition-all {
            transition: all 0.3s ease;
        }

        .hover\:bg-primary-dark:hover {
            background-color: #0052cc;
        }

        .hover\:bg-info-dark:hover {
            background-color: #17a2b8;
        }

        .hover\:bg-danger-dark:hover {
            background-color: #c82333;
        }

        .card-header.bg-primary {
            background: linear-gradient(45deg, #007bff, #00d4ff);
        }

        .fixed-bottom {
            z-index: 1000;
        }

        .badge.bg-secondary {
            font-size: 0.9rem;
            padding: 0.5rem;
        }

        .card {
            border-radius: 0.5rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
@endsection

@push('scripts')
    <script>

        (() => {
            const onReady = (callback) => {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', callback, { once: true });
                } else {
                    callback();
                }
            };

            const initTooltips = () => {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach((triggerEl) => new bootstrap.Tooltip(triggerEl));
            };

            const getTextFromSource = (selector) => {
                if (!selector) {
                    return '';
                }

                const node = document.querySelector(selector);
                return (node?.textContent || '').trim();
            };

            const copyHandlerFactory = (button) => {
                if (!button || button.dataset.bound === 'true') {
                    return;
                }

                button.dataset.bound = 'true';
                button.dataset.defaultEmptyLabel = button.dataset.emptyLabel || 'No text to copy';

                const label = button.querySelector('.status-text');
                const copyIcon = button.querySelector('.copy-icon');
                const successIcon = button.querySelector('.success-icon');
                const defaultLabel = button.dataset.defaultLabel || 'Copy';
                const successLabel = button.dataset.successLabel || 'Copied!';
                const getEmptyLabel = () => button.dataset.emptyLabel || button.dataset.defaultEmptyLabel || 'Nothing to copy';

                const setState = (state = 'default') => {
                    button.classList.remove('is-copied');
                    copyIcon?.classList.remove('d-none');
                    successIcon?.classList.add('d-none');

                    if (!label) {
                        return;
                    }

                    if (state === 'copied') {
                        button.classList.add('is-copied');
                        copyIcon?.classList.add('d-none');
                        successIcon?.classList.remove('d-none');
                        label.textContent = successLabel;
                    } else if (state === 'empty') {
                        label.textContent = getEmptyLabel();
                    } else {
                        label.textContent = defaultLabel;
                    }
                };

                const copyCurrentText = () => {
                    const text = (button.dataset.copyText || '').trim();

                    if (!text) {
                        setState('empty');
                        return;
                    }

                    const handleSuccess = () => {
                        setState('copied');
                        setTimeout(() => setState('default'), 2000);
                    };

                    const fallbackCopy = () => {
                        const temp = document.createElement('textarea');
                        temp.value = text;
                        temp.style.position = 'fixed';
                        temp.style.opacity = '0';
                        document.body.appendChild(temp);
                        temp.focus();
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        handleSuccess();
                    };

                    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                        navigator.clipboard.writeText(text).then(handleSuccess).catch(fallbackCopy);
                        return;
                    }

                    fallbackCopy();
                };

                button.addEventListener('click', copyCurrentText);
                button.addEventListener('blur', () => setState('default'));
                button._setCopyState = setState;
            };

            const updateModalContent = (modalEl, config) => {
                const { type, title, sourceSelector, emptyLabel } = config;
                const previewTarget = modalEl.querySelector('[data-info-preview]');
                const emptyPlaceholder = modalEl.querySelector('[data-info-empty]');
                const typeBadge = modalEl.querySelector('[data-info-type-label]');
                const stateBadge = modalEl.querySelector('[data-info-state]');
                const metaLength = modalEl.querySelector('[data-info-meta="length"]');
                const metaWords = modalEl.querySelector('[data-info-meta="words"]');
                const modalTitle = modalEl.querySelector('#infoPreviewModalLabel');
                const copyButton = modalEl.querySelector('#copyInfoModalButton');

                const text = getTextFromSource(sourceSelector);
                const hasText = text.length > 0;
                const wordCount = hasText ? text.split(/\s+/).filter(Boolean).length : 0;

                if (modalTitle) {
                    modalTitle.textContent = title || 'Information Preview';
                }

                if (typeBadge) {
                    const isReturn = type === 'return';
                    typeBadge.textContent = isReturn ? 'Return Note' : 'Delivery Note';
                    typeBadge.classList.toggle('bg-label-warning', isReturn);
                    typeBadge.classList.toggle('bg-label-primary', !isReturn);
                }

                if (stateBadge) {
                    stateBadge.textContent = hasText ? 'Ready to share' : 'Waiting for content';
                }

                if (metaLength) {
                    metaLength.textContent = `${text.length} chars`;
                }

                if (metaWords) {
                    metaWords.textContent = `${wordCount} words`;
                }

                if (previewTarget) {
                    previewTarget.textContent = text;
                }

                if (emptyPlaceholder) {
                    emptyPlaceholder.classList.toggle('d-none', hasText);
                }

                if (copyButton) {
                    copyButton.dataset.copyText = text;
                    copyButton.dataset.emptyLabel = emptyLabel || copyButton.dataset.defaultEmptyLabel || 'No text to copy';
                    copyButton._setCopyState?.('default');
                }

                return { text, hasText };
            };

            const handleModalShow = (event) => {
                const trigger = event.relatedTarget;
                const modalEl = event.target;

                if (!trigger || !modalEl) {
                    return;
                }

                const config = {
                    type: trigger.getAttribute('data-info-type') || 'delivery',
                    title: trigger.getAttribute('data-info-title') || 'Information Preview',
                    sourceSelector: trigger.getAttribute('data-info-source'),
                    emptyLabel: trigger.getAttribute('data-empty-label') || 'No text available',
                };

                updateModalContent(modalEl, config);
            };

            const handleModalHidden = (event) => {
                const modalEl = event.target;
                const copyButton = modalEl.querySelector('#copyInfoModalButton');

                if (copyButton) {
                    copyButton._setCopyState?.('default');
                    copyButton.dataset.copyText = '';
                }
            };

            const bootInfoPreview = () => {
                initTooltips();

                const modalEl = document.getElementById('infoPreviewModal');
                if (!modalEl || modalEl.dataset.bound === 'true') {
                    return;
                }

                modalEl.dataset.bound = 'true';

                const copyButton = modalEl.querySelector('#copyInfoModalButton');
                copyHandlerFactory(copyButton);

                modalEl.addEventListener('show.bs.modal', handleModalShow);
                modalEl.addEventListener('hidden.bs.modal', handleModalHidden);
            };

            document.addEventListener('livewire:navigated', bootInfoPreview);
            onReady(bootInfoPreview);
        })();
    </script>
@endpush
