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


                            <!-- Car Brand Selection -->
                            <div class="input-group">
                                <span class="input-group-text" id="car-brand-addon">Car Brand</span>
                                <select class="form-control @error('selectedBrand') is-invalid @enderror"
                                    id="car_brand_id" name="car_brand_id" wire:model.live="selectedBrand"
                                    aria-describedby="car-brand-addon">
                                    <option value="">Select Brand</option>
                                    @foreach ($carModels as $model)
                                        <option value="{{ $model->id }}"
                                            @if ($model->id == $selectedBrand) selected @endif>
                                            {{ $model->fullname() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('selectedBrand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!-- Car Model Selection (Filtered by Brand) -->
                            <div class="input-group">
                                <span class="input-group-text" id="car-model-id-addon">Car Model</span>
                                <select class="form-control @error('selectedCarId') is-invalid @enderror"
                                    id="car_model_id" name="car_model_id" wire:model.live="selectedCarId"
                                    aria-describedby="car-model-id-addon">
                                    <option value="">Select Model</option>
                                    @if ($selectedBrand)
                                        @foreach ($cars as $car)
                                            <option value="{{ $car->id }}"
                                                @if ($car->id == $selectedCarId) selected @endif>
                                                {{ $car->carModel->fullname() }} -
                                                {{ $car->manufacturing_year }} -
                                                {{ $car->color ?? 'No Color' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('selectedCarId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($selectedCarId)
                                <div class="input-group">
                                    <span class="input-group-text" id="plate-number-addon">Plate Number</span>
                                    <input type="text"
                                        class="form-control @error('plate_number') is-invalid @enderror"
                                        placeholder="Plate Number" name="plate_number" wire:model.live="plate_number">
                                    @error('plate_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="input-group">
                                    <span class="input-group-text" id="status-addon">Status</span>
                                    <select class="form-control @error('status') is-invalid @enderror" name="status"
                                        wire:model.live="status">
                                        <option value="available" {{ $status == 'available' ? 'selected' : '' }}>
                                            Available
                                        </option>
                                        <option value="reserved" {{ $status == 'reserved' ? 'selected' : '' }}>Reserved
                                        </option>
                                        <option value="under_maintenance"
                                            {{ $status == 'under_maintenance' ? 'selected' : '' }}>
                                            Under Maintenance
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Availability -->
                                <div class="input-group">
                                    <span class="input-group-text" id="availability-addon">Availability</span>
                                    <input type="text"
                                        class="form-control @error('availability') is-invalid @enderror"
                                        placeholder="Availability" name="availability" wire:model.live="availability">
                                    @error('availability')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Mileage -->
                                <div class="input-group">
                                    <span class="input-group-text" id="mileage-addon">Mileage</span>
                                    <input type="number" class="form-control @error('mileage') is-invalid @enderror"
                                        placeholder="Mileage" name="mileage" wire:model.live="mileage">
                                    @error('mileage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Price Per Day -->
                                <div class="input-group">
                                    <span class="input-group-text" id="price-per-day-addon">Price per Day $</span>
                                    <input type="number"
                                        class="form-control @error('price_per_day') is-invalid @enderror"
                                        placeholder="Price per Day" name="price_per_day" wire:model.live="price_per_day">
                                    @error('price_per_day')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <!-- Service Due Date -->
                                <div class="input-group">
                                    <span class="input-group-text" id="service-due-date-addon">Service Due Date</span>
                                    <input type="date"
                                        class="form-control @error('service_due_date') is-invalid @enderror"
                                        name="service_due_date" wire:model.live="service_due_date">
                                    @error('service_due_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Damage Report -->
                                <div class="input-group">
                                    <span class="input-group-text" id="damage-report-addon">Damage Report</span>
                                    <textarea class="form-control @error('damage_report') is-invalid @enderror" placeholder="Damage Report"
                                        name="damage_report" wire:model.live="damage_report"></textarea>
                                    @error('damage_report')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Manufacturing Year -->
                                <div class="input-group">
                                    <span class="input-group-text" id="manufacturing-year-addon">Manufacturing
                                        Year</span>
                                    <input type="number"
                                        class="form-control @error('manufacturing_year') is-invalid @enderror"
                                        placeholder="Manufacturing Year" name="manufacturing_year"
                                        wire:model.live="manufacturing_year">
                                    @error('manufacturing_year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Color -->
                                <div class="input-group">
                                    <span class="input-group-text" id="color-addon">Color</span>
                                    <input type="text" class="form-control @error('color') is-invalid @enderror"
                                        placeholder="Color" name="color" wire:model.live="color">
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Chassis Number -->
                                <div class="input-group">
                                    <span class="input-group-text" id="chassis-number-addon">Chassis Number</span>
                                    <input type="text"
                                        class="form-control @error('chassis_number') is-invalid @enderror"
                                        placeholder="Chassis Number" name="chassis_number"
                                        wire:model.live="chassis_number">
                                    @error('chassis_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- GPS -->
                                <div class="input-group">
                                    <span class="input-group-text" id="gps-addon">GPS</span>
                                    <input type="text" class="form-control @error('gps') is-invalid @enderror"
                                        placeholder="GPS" name="gps" wire:model.live="gps">
                                    @error('gps')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Fields (Registration, Service Dates, etc.) -->
            <div class="col-md-6">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Registration & Service Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <!-- Issue Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="issue-date-addon">Issue Date</span>
                                <input type="date" class="form-control @error('issue_date') is-invalid @enderror"
                                    name="issue_date" wire:model.live="issue_date">
                                @error('issue_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Expiry Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="expiry-date-addon">Expiry Date</span>
                                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror"
                                    name="expiry_date" wire:model.live="expiry_date">
                                @error('expiry_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Passing Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="passing-date-addon">Passing Date</span>
                                <input type="date"
                                    class="form-control @error('passing_date') is-invalid @enderror"
                                    name="passing_date" wire:model.live="passing_date">
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
                                    name="passing_valid_for_days" wire:model.live="passing_valid_for_days">
                                @error('passing_valid_for_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Registration Validity -->
                            <div class="input-group">
                                <span class="input-group-text" id="registration-validity-addon">Registration Valid For
                                    Days</span>
                                <input type="number"
                                    class="form-control @error('registration_valid_for_days') is-invalid @enderror"
                                    name="registration_valid_for_days" wire:model.live="registration_valid_for_days">
                                @error('registration_valid_for_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="input-group">
                                <span class="input-group-text" id="notes-addon">Notes</span>
                                <textarea class="form-control @error('notes') is-invalid @enderror" placeholder="Additional Notes" name="notes"
                                    wire:model.live="notes"></textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>

    </form>
</div>
