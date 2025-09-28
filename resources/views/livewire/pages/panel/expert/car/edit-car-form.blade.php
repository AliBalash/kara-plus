<div>
    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="submit">
        <div class="row">
            <!-- Car Information -->
            <div class="col-md-6">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Car Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <!-- Car Brand Selection (Readonly) -->
                            <div class="input-group">
                                <span class="input-group-text" id="car-brand-addon">Car Brand</span>
                                <select class="form-control" disabled>
                                    <option>{{ $car->carModel->fullname() }}</option>
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

                            <!-- Status -->
                            <div class="input-group">
                                <span class="input-group-text" id="status-addon">Status</span>
                                <select class="form-control border border-warning @error('status') is-invalid @enderror"
                                    name="status" wire:model="status" required>
                                    <option value="available" {{ $status == 'available' ? 'selected' : '' }}>Available
                                    </option>
                                    <option value="reserved" {{ $status == 'reserved' ? 'selected' : '' }}>Reserved
                                    </option>
                                    <option value="under_maintenance"
                                        {{ $status == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Availability -->
                            <div class="input-group">
                                <span class="input-group-text" id="availability-addon">Availability</span>
                                <select class="form-select @error('availability') is-invalid @enderror"
                                    wire:model="availability" required>
                                    <option value="true" {{ $availability == true ? 'selected' : '' }}>Available
                                    </option>
                                    <option value="false" {{ $availability == false ? 'selected' : '' }}>Not Available
                                    </option>
                                </select>
                                @error('availability')
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
                                <input type="number"
                                    class="form-control @error('price_per_day_short') is-invalid @enderror"
                                    wire:model="price_per_day_short" placeholder="Price for short-term (درهم)"
                                    min="0" required>
                                @error('price_per_day_short')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Price for 7-28 Days -->
                            <div class="input-group">
                                <span class="input-group-text">Price (7-28 days)</span>
                                <input type="number"
                                    class="form-control @error('price_per_day_mid') is-invalid @enderror"
                                    wire:model="price_per_day_mid" placeholder="Price for mid-term (درهم)"
                                    min="0" required>
                                @error('price_per_day_mid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Price for 28+ Days -->
                            <div class="input-group">
                                <span class="input-group-text">Price (28+ days)</span>
                                <input type="number"
                                    class="form-control @error('price_per_day_long') is-invalid @enderror"
                                    wire:model="price_per_day_long" placeholder="Price for long-term (درهم)"
                                    min="0" required>
                                @error('price_per_day_long')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LDW Insurance Short -->
                            <div class="input-group">
                                <span class="input-group-text">LDW Insurance (1-6 days)</span>
                                <input type="number"
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
                                <input type="number" class="form-control @error('ldw_price_mid') is-invalid @enderror"
                                    wire:model="ldw_price_mid" placeholder="LDW mid-term daily price" min="0"
                                    required>
                                @error('ldw_price_mid')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- LDW Insurance Long -->
                            <div class="input-group">
                                <span class="input-group-text">LDW Insurance (28+ days)</span>
                                <input type="number" class="form-control @error('ldw_price_long') is-invalid @enderror"
                                    wire:model="ldw_price_long" placeholder="LDW long-term daily price" min="0"
                                    required>
                                @error('ldw_price_long')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- SCDW Insurance Short -->
                            <div class="input-group">
                                <span class="input-group-text">SCDW Insurance (1-6 days)</span>
                                <input type="number"
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
                                <input type="number"
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
                                <input type="number"
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
                                        style="object-fit: cover; border-radius: 8px;" class="mb-3">
                                @else
                                    <p class="text-muted">No image uploaded yet.</p>
                                @endif
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
</div>
