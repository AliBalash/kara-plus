<div>
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Information</h4>

    @if (session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <ul class="nav nav-pills flex-column flex-md-row mb-3">
        <li class="nav-item">
            <!-- اگر contract موجود نیست، فقط از مسیر پیش‌فرض استفاده می‌کنیم -->
            <a class="nav-link active"
                href="{{ isset($contract->id) ? route('rental-requests.form', $contract->id) : '#' }}">
                <i class="bx bxs-info-square me-1"></i> Rental Information
            </a>
        </li>

        @if (isset($contract->customer))
            <li class="nav-item">
                <a class="nav-link" href="{{ route('customer.documents', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-file me-1"></i> Customer Document
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"
                    href="{{ route('rental-requests.payment', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-money me-1"></i> Payment
                </a>
            </li>

            <!-- افزودن لینک تاریخچه درخواست -->
            <li class="nav-item">
                <a class="nav-link" href="{{ route('rental-requests.history', $contract->id) }}">
                    <i class="bx bx-history me-1"></i> History
                </a>
            </li>
        @endif


    </ul>


    <form wire:submit.prevent="submit">

        <div class="row">
            <!-- Contract Information -->
            <div class="col-md-6">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Contract Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">
                            <!-- Start Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-start-date">Start Date</span>
                                <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                    placeholder="Start Date" name="start_date" wire:model="start_date" disabled>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- End Date -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-end-date">End Date</span>
                                <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                    placeholder="End Date" name="end_date" wire:model="end_date" disabled>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Total Price -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-total-price">$</span>
                                <input type="number" class="form-control @error('total_price') is-invalid @enderror"
                                    placeholder="Total Price" name="total_price" wire:model="total_price" disabled>
                                @error('total_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <!-- Status -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-status">Status</span>
                                <select class="form-control @error('status') is-invalid @enderror" name="status"
                                    wire:model="status" disabled>
                                    <option value="">Select Status</option>
                                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending
                                    </option>
                                    <option value="assigned" {{ $status == 'assigned' ? 'selected' : '' }}>Assigned
                                    </option>
                                    <option value="under_review" {{ $status == 'under_review' ? 'selected' : '' }}>
                                        Under Review</option>
                                    <option value="reserved" {{ $status == 'reserved' ? 'selected' : '' }}>Reserved
                                    </option>
                                    <option value="delivery_in_progress"
                                        {{ $status == 'delivery_in_progress' ? 'selected' : '' }}>Delivery In Progress
                                    </option>
                                    <option value="agreement_inspection"
                                        {{ $status == 'agreement_inspection' ? 'selected' : '' }}>Agreement Inspection
                                    </option>
                                    <option value="awaiting_return"
                                        {{ $status == 'awaiting_return' ? 'selected' : '' }}>Awaiting Return</option>
                                    <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>Returned
                                    </option>
                                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled
                                    </option>
                                    <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected
                                    </option>
                                    <option value="complete" {{ $status == 'complete' ? 'selected' : '' }}>Complete
                                    </option>
                                </select>

                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>



                            <!-- Notes -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-notes">Note</span>
                                <textarea class="form-control " wire:model="note" placeholder="Contract Notes" name="notes">{{ $contract?->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Car Information -->
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


                            <!-- Display the selected car details (plate number and year) -->
                            @if ($selectedCarId)
                                @php
                                    $selectedCar = App\Models\Car::find($selectedCarId);
                                @endphp
                                <div class="input-group">
                                    <span class="input-group-text" id="plate-number-addon">Plate Number</span>
                                    <input type="text"
                                        class="form-control @error('plate_number') is-invalid @enderror"
                                        value="{{ $selectedCar->plate_number }}" disabled />

                                </div>

                                <div class="input-group">
                                    <span class="input-group-text" id="manufacturing-year-addon">Manufacturing
                                        Year</span>
                                    <input type="text"
                                        class="form-control @error('manufacturing_year') is-invalid @enderror"
                                        value="{{ $selectedCar->manufacturing_year }}" disabled />
                                </div>

                                <!-- Price Per Day -->
                                <div class="input-group">
                                    <span class="input-group-text" id="price-per-day-addon">Per Day $</span>
                                    <input type="number"
                                        class="form-control @error('price_per_day') is-invalid @enderror"
                                        id="price_per_day" name="price_per_day"
                                        value="{{ $selectedCar->price_per_day }}" placeholder="Price per day"
                                        aria-describedby="price-per-day-addon" disabled />
                                </div>

                                <!-- Service Due Date -->
                                <div class="input-group">
                                    <span class="input-group-text" id="service-due-date-addon">Service Due Date</span>
                                    <input type="date"
                                        class="form-control @error('service_due_date') is-invalid @enderror"
                                        id="service_due_date" name="service_due_date"
                                        value="{{ $selectedCar->service_due_date }}"
                                        aria-describedby="service-due-date-addon" disabled />
                                </div>
                            @endif




                        </div>
                    </div>

                </div>

            </div>

            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Customer Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <!-- First Name -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-first-name">First Name</span>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                placeholder="First Name" name="first_name" wire:model="first_name">
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-last-name">Last Name</span>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                placeholder="Last Name" name="last_name" wire:model="last_name">
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-email">Email</span>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                placeholder="Email" name="email" wire:model="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-phone">Phone</span>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                placeholder="Phone" name="phone" wire:model="phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-address">Address</span>
                            <input type="text" class="form-control @error('address') is-invalid @enderror"
                                placeholder="Address" name="address" wire:model="address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- National Code -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-national-code">National Code</span>
                            <input type="text" class="form-control @error('national_code') is-invalid @enderror"
                                placeholder="National Code" name="national_code" wire:model="national_code">
                            @error('national_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Passport Number -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-passport-number">Passport Number</span>
                            <input type="text" class="form-control @error('passport_number') is-invalid @enderror"
                                placeholder="Passport Number" name="passport_number" wire:model="passport_number">
                            @error('passport_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Passport Expiry Date -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-passport-expiry-date">Passport Expiry
                                Date</span>
                            <input type="date"
                                class="form-control @error('passport_expiry_date') is-invalid @enderror"
                                name="passport_expiry_date" wire:model="passport_expiry_date">
                            @error('passport_expiry_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                        <!-- Nationality -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-nationality">Nationality</span>
                            <input type="text" class="form-control @error('nationality') is-invalid @enderror"
                                placeholder="Nationality" name="nationality" wire:model="nationality">
                            @error('nationality')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- License Number -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-license-number">License Number</span>
                            <input type="text" class="form-control @error('license_number') is-invalid @enderror"
                                placeholder="License Number" name="license_number" wire:model="license_number">
                            @error('license_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary">Save Contract</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
