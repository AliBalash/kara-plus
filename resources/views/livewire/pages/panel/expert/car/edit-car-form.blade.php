<div>
    <form wire:submit.prevent="submit">
        @php
            $baseStatusLabel = \App\Models\Car::manualStatusLabels()[$status] ?? ucfirst((string) $status);
            $isNeedActionPreview = $this->effectiveUnavailabilityReasonLabel === 'Need Action';
        @endphp

        <section class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #f9fafb 0%, #eef7ff 48%, #fff5ec 100%);">
                    <div class="d-flex flex-column flex-xl-row justify-content-between gap-3">
                        <div>
                            <div class="small text-uppercase text-muted fw-semibold mb-1">Status Control</div>
                            <h5 class="mb-1">Base Status Decision</h5>
                            <div class="text-muted">Set the manual decision here. Automatic booking states are shown as the final operational result.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-start">
                            <span class="badge bg-light text-dark border">Base: {{ $baseStatusLabel }}</span>
                            <span class="badge {{ $this->effectiveStatusBadgeClass }}">Final: {{ $this->effectiveStatusLabel }}</span>
                            @if ($this->effectiveUnavailabilityReasonLabel)
                                <span class="badge bg-danger-subtle text-danger">{{ $this->effectiveUnavailabilityReasonLabel }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <label class="form-label small text-muted mb-1">Base Status</label>
                            <select class="form-select form-select-lg border-warning @error('status') is-invalid @enderror"
                                name="status" wire:model.live="status" required>
                                <option value="available" {{ $status == 'available' ? 'selected' : '' }}>Available</option>
                                <option value="unavailable" {{ $status == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                                <option value="sold" {{ $status == 'sold' ? 'selected' : '' }}>Sold</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Manual unavailable and sold stay fixed until you change them.</div>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label small text-muted mb-1">Unavailable Reason</label>
                            <select class="form-select @error('hold_reason') is-invalid @enderror"
                                wire:model.live="hold_reason" @disabled($status !== \App\Models\Car::MANUAL_STATUS_UNAVAILABLE)>
                                <option value="">Select reason</option>
                                @foreach (\App\Models\Car::scheduledUnavailabilityReasonLabels() as $reasonValue => $reasonLabel)
                                    <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                @endforeach
                            </select>
                            @error('hold_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Required only when Base Status is Unavailable.</div>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label small text-muted mb-1">Status Note</label>
                            <textarea class="form-control @error('hold_note') is-invalid @enderror" rows="2"
                                placeholder="Example: Accident repair, workshop check, management decision."
                                wire:model.defer="hold_note" @disabled($status !== \App\Models\Car::MANUAL_STATUS_UNAVAILABLE)></textarea>
                            @error('hold_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-lg-8">
                            <div class="alert alert-secondary border-0 mb-0">
                                <div class="fw-semibold">Final Status: {{ $this->effectiveStatusLabel }}</div>
                                @if ($this->effectiveUnavailabilityReasonLabel)
                                    <div class="small text-dark mt-1">Reason: {{ $this->effectiveUnavailabilityReasonLabel }}</div>
                                @endif
                                <div class="small text-muted">
                                    {{ $this->effectiveStatusExplanation ?? 'Final status is synchronized automatically from base status and reservation timeline.' }}
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex flex-column flex-sm-row flex-lg-column gap-2">
                                <a href="{{ route('car.unavailable-desk', ['carFilter' => $car->id]) }}" class="btn btn-outline-dark">
                                    Open Unavailable Report
                                </a>
                                @if ($car->manual_status === \App\Models\Car::MANUAL_STATUS_UNAVAILABLE)
                                    <span class="badge bg-danger-subtle text-danger py-2">Manual unavailable active</span>
                                @else
                                    <span class="badge bg-success-subtle text-success py-2">No manual unavailable hold</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if ($isNeedActionPreview)
                        <div class="alert alert-danger border-0 mt-3 mb-0">
                            <div class="fw-semibold">Need Action requires review</div>
                            <div class="small">
                                {{ $car->needActionAlertMessage() }} Click the car, review the contract/status, then save the correct base status.
                            </div>
                        </div>
                    @endif

                    <x-car-need-action-alert :car="$car" class="mt-3 mb-0" />
                </div>
            </div>
        </section>

        <div class="row">
            <!-- Car Information -->
            <div class="col-12">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Car Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <!-- Car Brand Selection (Readonly) -->
                            <div class="input-group">
                                <span class="input-group-text" id="car-brand-addon">Car Brand</span>
                                <select class="form-control" disabled>
                                    <option>{{ $car->fullName() }} ({{ $car->ownershipLabel() }})</option>
                                </select>
                            </div>

                            <!-- Plate Number -->
                            <div class="input-group">
                                <span class="input-group-text" id="plate-number-addon">Plate Number</span>
                                <input type="text" class="form-control @error('plate_number') is-invalid @enderror"
                                    placeholder="Plate Number" name="plate_number" wire:model="plate_number" required>
                                @error('plate_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                                <!-- Ownership -->
                                <div class="input-group">
                                    <span class="input-group-text" id="ownership-addon">Ownership</span>
                                    <select class="form-select @error('ownership_type') is-invalid @enderror"
                                        wire:model="ownership_type" required>
                                        <option value="company">Our Fleet</option>
                                        <option value="golden_key">Golden Key</option>
                                        <option value="liverpool">Liverpool</option>
                                        <option value="safe_drive">Safe Drive</option>
                                        <option value="other">Other Fleet</option>
                                    </select>
                                    @error('ownership_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                            <!-- Mileage -->
                            <div class="input-group">
                                <span class="input-group-text" id="mileage-addon">Mileage</span>
                                <input type="number" class="form-control @error('mileage') is-invalid @enderror"
                                    placeholder="Mileage" name="mileage" wire:model="mileage" min="0" required>
                                @error('mileage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Price for 1-6 Days -->
                            <div class="input-group">
                                <span class="input-group-text">Price (1-6 days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('price_per_day_short') is-invalid @enderror"
                                    wire:model="price_per_day_short" placeholder="Price for short-term (AED)"
                                    min="0" required>
                                @error('price_per_day_short')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Price for 7-28 Days -->
                            <div class="input-group">
                                <span class="input-group-text">Price (7-28 days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('price_per_day_mid') is-invalid @enderror"
                                    wire:model="price_per_day_mid" placeholder="Price for mid-term (AED)"
                                    min="0" required>
                                @error('price_per_day_mid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Price for 28+ Days -->
                            <div class="input-group">
                                <span class="input-group-text">Price (28+ days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('price_per_day_long') is-invalid @enderror"
                                    wire:model="price_per_day_long" placeholder="Price for long-term (AED)"
                                    min="0" required>
                                @error('price_per_day_long')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LDW Insurance Short -->
                            <div class="input-group">
                                <span class="input-group-text">LDW Insurance (1-6 days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('ldw_price_short') is-invalid @enderror"
                                    wire:model="ldw_price_short" placeholder="LDW short-term daily price" min="0"
                                    required>
                                @error('ldw_price_short')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LDW Insurance Mid -->
                            <div class="input-group">
                                <span class="input-group-text">LDW Insurance (7-28 days)</span>
                                <input type="number" step="0.01" class="form-control @error('ldw_price_mid') is-invalid @enderror"
                                    wire:model="ldw_price_mid" placeholder="LDW mid-term daily price" min="0"
                                    required>
                                @error('ldw_price_mid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LDW Insurance Long -->
                            <div class="input-group">
                                <span class="input-group-text">LDW Insurance (28+ days)</span>
                                <input type="number" step="0.01" class="form-control @error('ldw_price_long') is-invalid @enderror"
                                    wire:model="ldw_price_long" placeholder="LDW long-term daily price" min="0"
                                    required>
                                @error('ldw_price_long')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- SCDW Insurance Short -->
                            <div class="input-group">
                                <span class="input-group-text">SCDW Insurance (1-6 days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('scdw_price_short') is-invalid @enderror"
                                    wire:model="scdw_price_short" placeholder="SCDW short-term daily price"
                                    min="0" required>
                                @error('scdw_price_short')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- SCDW Insurance Mid -->
                            <div class="input-group">
                                <span class="input-group-text">SCDW Insurance (7-28 days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('scdw_price_mid') is-invalid @enderror"
                                    wire:model="scdw_price_mid" placeholder="SCDW mid-term daily price"
                                    min="0" required>
                                @error('scdw_price_mid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- SCDW Insurance Long -->
                            <div class="input-group">
                                <span class="input-group-text">SCDW Insurance (28+ days)</span>
                                <input type="number" step="0.01"
                                    class="form-control @error('scdw_price_long') is-invalid @enderror"
                                    wire:model="scdw_price_long" placeholder="SCDW long-term daily price"
                                    min="0" required>
                                @error('scdw_price_long')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Service Due Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="service-due-date-addon">Service Due Date</span>
                                <input type="date"
                                    class="form-control @error('service_due_date') is-invalid @enderror"
                                    name="service_due_date" wire:model="service_due_date">
                                @error('service_due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Damage Report -->
                            <div class="input-group">
                                <span class="input-group-text" id="damage-report-addon">Damage Report</span>
                                <textarea class="form-control @error('damage_report') is-invalid @enderror" placeholder="Damage Report"
                                    name="damage_report" wire:model="damage_report"></textarea>
                                @error('damage_report')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Manufacturing Year -->
                            <div class="input-group">
                                <span class="input-group-text" id="manufacturing-year-addon">Manufacturing Year</span>
                                <input type="number"
                                    class="form-control @error('manufacturing_year') is-invalid @enderror"
                                    placeholder="Manufacturing Year" name="manufacturing_year"
                                    wire:model="manufacturing_year" min="1900" max="2155" required>
                                @error('manufacturing_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Color -->
                            <div class="input-group">
                                <span class="input-group-text" id="color-addon">Color</span>
                                <input type="text" class="form-control @error('color') is-invalid @enderror"
                                    placeholder="Color" name="color" wire:model="color" required>
                                @error('color')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Chassis Number -->
                            <div class="input-group">
                                <span class="input-group-text" id="chassis-number-addon">Chassis Number</span>
                                <input type="text"
                                    class="form-control @error('chassis_number') is-invalid @enderror"
                                    placeholder="Chassis Number" name="chassis_number" wire:model="chassis_number"
                                    required>
                                @error('chassis_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- GPS -->
                            <div class="input-group">
                                <span class="input-group-text" id="gps-addon">GPS</span>
                                <select class="form-select @error('gps') is-invalid @enderror" name="gps"
                                    wire:model="gps">
                                    <option value="">Select</option>
                                    <option value="1" {{ $gps ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ !$gps ? 'selected' : '' }}>No</option>
                                </select>
                                @error('gps')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Existing Image -->
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                @if ($existingImageUrl)
                                    <img src="{{ $existingImageUrl }}" width="400" height="200"
                                        style="object-fit: cover; border-radius: 8px;" class="mb-3" loading="lazy"
                                        decoding="async" fetchpriority="low">
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            wire:click="removeImage">Remove current image</button>
                                    </div>
                                @else
                                    <p class="text-muted">No image uploaded yet.</p>
                                @endif
                            </div>

                            <!-- Upload New Image -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">Update Image</span>
                                <input type="file" class="form-control @error('newImage') is-invalid @enderror"
                                    wire:key="edit-car-image-{{ $fileInputVersion }}" wire:model="newImage"
                                    accept="image/*">
                                @error('newImage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div wire:loading wire:target="newImage" class="progress mt-2" style="height: 40px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info fs-5 fw-bold text-center"
                                    style="width: 100%;">
                                    Uploading Image...
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Is Featured (Brand Highlight) -->
                <div class="card mb-4">
                    <h5 class="card-header">
                        Featured
                    </h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="form-check form-switch d-flex align-items-center gap-3 fs-5">
                            <input class="form-check-input" type="checkbox" id="isFeatured" wire:model="is_featured"
                                style="width: 3rem; height: 1.5rem;">
                            <label class="form-check-label fw-bold d-flex align-items-center gap-2" for="isFeatured">
                                <i class="bx bx-star bx-sm text-warning"></i>
                                Special Brand
                            </label>
                        </div>
                    </div>
                </div>



            </div>

            <!-- Additional Fields -->
            {{-- <div class="col-md-6">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Registration & Service Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <!-- Issue Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="issue-date-addon">Issue Date</span>
                                <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                    name="issue_date" wire:model="issue_date">
                                @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Expiry Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="expiry-date-addon">Expiry Date</span>
                                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror"
                                    name="expiry_date" wire:model="expiry_date">
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Passing Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="passing-date-addon">Passing Date</span>
                                <input type="date"
                                    class="form-control @error('passing_date') is-invalid @enderror"
                                    name="passing_date" wire:model="passing_date">
                                @error('passing_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Passing Validity -->
                            <div class="input-group">
                                <span class="input-group-text" id="passing-validity-addon">Passing Valid For
                                    Days</span>
                                <input type="number"
                                    class="form-control @error('passing_valid_for_days') is-invalid @enderror"
                                    name="passing_valid_for_days" wire:model="passing_valid_for_days" min="0">
                                @error('passing_valid_for_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Passing Status -->
                            <div class="input-group">
                                <label class="input-group-text" for="passing-status">Passing Status</label>
                                <select class="form-select @error('passing_status') is-invalid @enderror"
                                    id="passing-status" name="passing_status" wire:model="passing_status">
                                    <option value="done" {{ $passing_status == 'done' ? 'selected' : '' }}>
                                        Done
                                    </option>
                                    <option value="pending" {{ $passing_status == 'pending' ? 'selected' : '' }}>
                                        Pending</option>
                                    <option value="failed" {{ $passing_status == 'failed' ? 'selected' : '' }}>Failed
                                    </option>
                                </select>
                                @error('passing_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Registration Validity -->
                            <div class="input-group">
                                <span class="input-group-text" id="registration-validity-addon">Registration
                                    Valid For
                                    Days</span>
                                <input type="number"
                                    class="form-control @error('registration_valid_for_days') is-invalid @enderror"
                                    name="registration_valid_for_days" wire:model="registration_valid_for_days"
                                    min="0">
                                @error('registration_valid_for_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Registration Status -->
                            <div class="input-group">
                                <label class="input-group-text" for="registration-status">Registration
                                    Status</label>
                                <select class="form-select @error('registration_status') is-invalid @enderror"
                                    id="registration-status" name="registration_status"
                                    wire:model="registration_status">
                                    <option value="done" {{ $registration_status == 'done' ? 'selected' : '' }}>Done
                                    </option>
                                    <option value="pending" {{ $registration_status == 'pending' ? 'selected' : '' }}>
                                        Pending</option>
                                    <option value="failed" {{ $registration_status == 'failed' ? 'selected' : '' }}>
                                        Failed</option>
                                </select>
                                @error('registration_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="input-group">
                                <span class="input-group-text" id="notes-addon">Notes</span>
                                <textarea class="form-control @error('notes') is-invalid @enderror" placeholder="Additional Notes" name="notes"
                                    wire:model="notes"></textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card mb-4">
                        <h5 class="card-header">Car Options</h5>
                        <div class="card-body">
                            <!-- Gear Type -->
                            <div class="mb-3">
                                <label class="form-label">Gear Type</label>
                                <select class="form-select @error('car_options.gear') is-invalid @enderror"
                                    wire:model="car_options.gear">
                                    <option value="">Select</option>
                                    <option value="automatic"
                                        {{ isset($car_options['gear']) && $car_options['gear'] == 'automatic' ? 'selected' : '' }}>
                                        Automatic</option>
                                    <option value="manual"
                                        {{ isset($car_options['gear']) && $car_options['gear'] == 'manual' ? 'selected' : '' }}>
                                        Manual</option>
                                </select>
                                @error('car_options.gear')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Number of Seats -->
                            <div class="mb-3">
                                <label class="form-label">Number of Seats</label>
                                <input type="number"
                                    class="form-control @error('car_options.seats') is-invalid @enderror"
                                    wire:model="car_options.seats" min="1" placeholder="e.g., 5"
                                    value="{{ $car_options['seats'] ?? '' }}">
                                @error('car_options.seats')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Number of Doors -->
                            <div class="mb-3">
                                <label class="form-label">Number of Doors</label>
                                <input type="number"
                                    class="form-control @error('car_options.doors') is-invalid @enderror"
                                    wire:model="car_options.doors" min="1" placeholder="e.g., 4"
                                    value="{{ $car_options['doors'] ?? '' }}">
                                @error('car_options.doors')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Number of Luggage -->
                            <div class="mb-3">
                                <label class="form-label">Number of Luggage</label>
                                <input type="number"
                                    class="form-control @error('car_options.luggage') is-invalid @enderror"
                                    wire:model="car_options.luggage" min="0" placeholder="e.g., 3"
                                    value="{{ $car_options['luggage'] ?? '' }}">
                                @error('car_options.luggage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Minimum Rental Days -->
                            <div class="mb-3">
                                <label class="form-label">Minimum Rental Days</label>
                                <input type="number"
                                    class="form-control @error('car_options.min_days') is-invalid @enderror"
                                    wire:model="car_options.min_days" min="1" placeholder="e.g., 2"
                                    value="{{ $car_options['min_days'] ?? '' }}">
                                @error('car_options.min_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Fuel Type -->
                            <div class="mb-3">
                                <label class="form-label">Fuel Type</label>
                                <select class="form-select @error('car_options.fuel_type') is-invalid @enderror"
                                    wire:model="car_options.fuel_type">
                                    <option value="">Select</option>
                                    <option value="petrol"
                                        {{ isset($car_options['fuel_type']) && $car_options['fuel_type'] == 'petrol' ? 'selected' : '' }}>
                                        Petrol</option>
                                    <option value="diesel"
                                        {{ isset($car_options['fuel_type']) && $car_options['fuel_type'] == 'diesel' ? 'selected' : '' }}>
                                        Diesel</option>
                                    <option value="hybrid"
                                        {{ isset($car_options['fuel_type']) && $car_options['fuel_type'] == 'hybrid' ? 'selected' : '' }}>
                                        Hybrid</option>
                                    <option value="electric"
                                        {{ isset($car_options['fuel_type']) && $car_options['fuel_type'] == 'electric' ? 'selected' : '' }}>
                                        Electric</option>
                                </select>
                                @error('car_options.fuel_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Unlimited Kilometers -->
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="unlimited_km"
                                    wire:model="car_options.unlimited_km"
                                    {{ isset($car_options['unlimited_km']) && $car_options['unlimited_km'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="unlimited_km">Unlimited
                                    Kilometers</label>
                                @error('car_options.unlimited_km')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Base Insurance -->
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="base_insurance"
                                    wire:model="car_options.base_insurance"
                                    {{ isset($car_options['base_insurance']) && $car_options['base_insurance'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="base_insurance">Base Insurance</label>
                                @error('car_options.base_insurance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                        </div>
                    </div>
                </div>
            </div> --}}
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Update Car</button>
            <span wire:loading wire:target="submit" class="spinner-border spinner-border-sm ms-2" role="status"
                aria-hidden="true"></span>
        </div>
    </form>

    <section class="card border-0 shadow-sm mt-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="p-4 border-bottom" style="background: linear-gradient(135deg, #f8fafc 0%, #eef6f2 52%, #fff7ed 100%);">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                    <div>
                        <div class="small text-uppercase text-muted fw-semibold mb-1">Vehicle Status Audit</div>
                        <h5 class="mb-1">Status Timeline</h5>
                        <div class="text-muted">
                            Every manual or automatic status change is kept here. The previous status closes automatically when the next one starts.
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <span class="badge bg-light text-dark border">{{ $this->statusTimeline->count() }} records shown</span>
                        <span class="badge {{ $this->effectiveStatusBadgeClass }}">{{ $this->effectiveStatusLabel }}</span>
                        @if ($this->effectiveUnavailabilityReasonLabel)
                            <span class="badge bg-danger-subtle text-danger">{{ $this->effectiveUnavailabilityReasonLabel }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @if (! \App\Models\CarStatusPeriod::tableExists())
                <div class="alert alert-warning m-4">
                    <div class="fw-semibold">Database update required</div>
                    <div class="small">Create `car_status_periods` to enable full status timeline.</div>
                </div>
            @else
                <div class="row g-0">
                    <div class="col-lg-7 border-end">
                        <div class="p-4">
                            @forelse ($this->statusTimeline as $period)
                                <div class="d-flex gap-3 pb-4 {{ ! $loop->last ? 'border-bottom mb-4' : '' }}">
                                    <div class="flex-shrink-0 text-center">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center {{ \App\Models\Car::operationalStatusSubtleBadgeClassFor($period->status, (bool) $period->availability, $period->reason) }}"
                                            style="width: 44px; height: 44px;">
                                            <i class="{{ \App\Models\Car::operationalStatusIconFor($period->status, (bool) $period->availability) }}"></i>
                                        </div>
                                        @if (! $period->ended_at)
                                            <span class="badge bg-success-subtle text-success mt-2">Open</span>
                                        @endif
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                            <div>
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                    <span class="badge {{ \App\Models\Car::operationalStatusSubtleBadgeClassFor($period->status, (bool) $period->availability, $period->reason) }}">
                                                        {{ $period->statusLabel() }}
                                                    </span>
                                                    @if ($period->reasonLabel())
                                                        <span class="badge bg-danger-subtle text-danger">{{ $period->reasonLabel() }}</span>
                                                    @endif
                                                    <span class="badge {{ $period->sourceBadgeClass() }}">{{ $period->sourceLabel() }}</span>
                                                </div>
                                                <div class="fw-semibold">
                                                    {{ $period->started_at?->format('Y-m-d H:i') ?? '—' }}
                                                    @if ($period->ended_at)
                                                        <span class="text-muted fw-normal">to {{ $period->ended_at->format('Y-m-d H:i') }}</span>
                                                    @else
                                                        <span class="text-success fw-normal">to now</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted">{{ $period->durationLabel() }}</div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2 text-md-end">
                                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center small fw-semibold"
                                                    style="width: 34px; height: 34px;">
                                                    {{ $period->actorInitials() }}
                                                </div>
                                                <div>
                                                    <div class="small fw-semibold">{{ $period->actorName() }}</div>
                                                    <div class="small text-muted">{{ $period->trigger_type ?: 'status_sync' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($period->note)
                                            <div class="bg-light rounded-3 px-3 py-2 mt-3 small text-muted">
                                                {{ $period->note }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-5">
                                    <div class="fw-semibold mb-1">No timeline yet</div>
                                    <div class="small">It will start after the SQL is run and the car is synced.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="p-4">
                            <div class="fw-semibold mb-3">Status History Table</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Start</th>
                                            <th>End</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($this->statusTimeline as $period)
                                            <tr>
                                                <td>
                                                    <span class="badge {{ \App\Models\Car::operationalStatusSubtleBadgeClassFor($period->status, (bool) $period->availability, $period->reason) }}">
                                                        {{ $period->statusLabel() }}
                                                    </span>
                                                    @if ($period->reasonLabel())
                                                        <div class="small text-muted mt-1">{{ $period->reasonLabel() }}</div>
                                                    @endif
                                                </td>
                                                <td class="small">{{ $period->started_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                                <td class="small">
                                                    {{ $period->ended_at?->format('Y-m-d H:i') ?? 'Open' }}
                                                </td>
                                                <td class="small">{{ $period->actorName() }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">No records</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
