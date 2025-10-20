<div>
    <div class="row">
        <div class="col-lg-12 text-start">
            <h4 class="fw-bold py-3 mb-4">
                <span class="text-muted fw-light">Rental Request /</span> Create Request
            </h4>
        </div>
    </div>

    @isset($contract)
        @if (is_null($contract->user_id))
            <a class="btn btn-info fw-bold m-2 transition-all duration-300 hover:bg-info-dark" href="javascript:void(0);"
                onclick="if(confirm('Are you sure you want to assign this contract to self?')) { @this.assignToMe({{ $contract->id }}) }">
                <i class="bx bx-user-plus me-2"></i> Assign to Me
            </a>
        @else
            <div class="col-lg-6 text-end">
                <a class="btn btn-danger fw-bold transition-all duration-300 hover:bg-danger-dark" href="javascript:void(0);"
                    onclick="if(confirm('Are you sure you want to set this contract to Booking?')) { @this.changeStatusToReserve({{ $contract->id }}) }">
                    <i class="bx bxs-log-in-circle me-2"></i> Set to Booking
                </a>
            </div>
        @endif
    @endisset

    @if (session()->has('success'))
        <div class="alert alert-success animate__animated animate__fadeIn">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('message'))
        <div class="alert alert-info animate__animated animate__fadeIn">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger animate__animated animate__fadeIn">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="submit" novalidate>
        <div class="row">
            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-4 shadow-sm border-0">
                    <h5 class="card-header bg-primary text-white mb-3 rounded-top">Customer Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="mb-3 mt-3" data-validation-field="first_name">
                            <label class="form-label fw-semibold mb-1" for="firstNameInput">
                                First Name <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-user"></i></span>
                                <input id="firstNameInput" type="text"
                                    class="form-control @error('first_name') is-invalid @enderror"
                                    placeholder="First Name" wire:model="first_name" data-bs-toggle="tooltip"
                                    title="Enter customer's first name">
                            </div>
                            @error('first_name')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="last_name">
                            <label class="form-label fw-semibold mb-1" for="lastNameInput">
                                Last Name <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-user"></i></span>
                                <input id="lastNameInput" type="text"
                                    class="form-control @error('last_name') is-invalid @enderror"
                                    placeholder="Last Name" wire:model="last_name" data-bs-toggle="tooltip"
                                    title="Enter customer's last name">
                            </div>
                            @error('last_name')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="email">
                            <label class="form-label fw-semibold mb-1" for="emailInput">
                                Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-envelope"></i></span>
                                <input id="emailInput" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="Email" wire:model="email" data-bs-toggle="tooltip"
                                    title="Enter customer's email address">
                            </div>
                            @error('email')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="phone">
                            <label class="form-label fw-semibold mb-1" for="phoneInput">
                                Phone <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-phone"></i></span>
                                <input id="phoneInput" type="tel"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    placeholder="Phone" wire:model="phone" data-bs-toggle="tooltip"
                                    title="Enter customer's phone number">
                            </div>
                            @error('phone')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="messenger_phone">
                            <label class="form-label fw-semibold mb-1" for="messengerPhoneInput">
                                Messenger Phone <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-chat"></i></span>
                                <input id="messengerPhoneInput" type="tel"
                                    class="form-control @error('messenger_phone') is-invalid @enderror"
                                    placeholder="Messenger Phone" wire:model="messenger_phone" data-bs-toggle="tooltip"
                                    title="Enter customer's messenger phone number">
                            </div>
                            @error('messenger_phone')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="address">
                            <label class="form-label fw-semibold mb-1" for="addressInput">Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-home"></i></span>
                                <input id="addressInput" type="text" class="form-control @error('address') is-invalid @enderror"
                                    placeholder="Address" wire:model="address" data-bs-toggle="tooltip"
                                    title="Enter customer's address">
                            </div>
                            @error('address')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="national_code">
                            <label class="form-label fw-semibold mb-1" for="nationalCodeInput">
                                National Code <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-id-card"></i></span>
                                <input id="nationalCodeInput" type="text"
                                    class="form-control @error('national_code') is-invalid @enderror"
                                    placeholder="National Code" wire:model="national_code" data-bs-toggle="tooltip"
                                    title="Enter customer's national code">
                            </div>
                            @error('national_code')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="passport_number">
                            <label class="form-label fw-semibold mb-1" for="passportNumberInput">Passport Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bxs-plane-take-off"></i></span>
                                <input id="passportNumberInput" type="text"
                                    class="form-control @error('passport_number') is-invalid @enderror"
                                    placeholder="Passport Number" wire:model="passport_number" data-bs-toggle="tooltip"
                                    title="Enter customer's passport number">
                            </div>
                            @error('passport_number')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="passport_expiry_date">
                            <label class="form-label fw-semibold mb-1" for="passportExpiryInput">Passport Expiry Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="passportExpiryInput" type="date"
                                    class="form-control @error('passport_expiry_date') is-invalid @enderror"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" wire:model="passport_expiry_date"
                                    data-bs-toggle="tooltip" title="Enter passport expiry date">
                            </div>
                            @error('passport_expiry_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="nationality">
                            <label class="form-label fw-semibold mb-1" for="nationalityInput">
                                Nationality <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-flag"></i></span>
                                <input id="nationalityInput" type="text"
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
                            <label class="form-label fw-semibold mb-1" for="selectedBrandInput">
                                Car Brand <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-car"></i></span>
                                <select id="selectedBrandInput"
                                    class="form-control text-uppercase @error('selectedBrand') is-invalid @enderror"
                                    wire:model.live="selectedBrand" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select car brand">
                                    <option value="">Select Brand</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand }}">{{ $brand }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('selectedBrand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($selectedBrand)
                            <div class="mb-3" data-validation-field="selectedModelId">
                                <label class="form-label fw-semibold mb-1" for="selectedModelInput">
                                    Car Model <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-car"></i></span>
                                    <select id="selectedModelInput"
                                        class="form-control text-uppercase @error('selectedModelId') is-invalid @enderror"
                                        wire:model.live="selectedModelId" aria-required="true" data-bs-toggle="tooltip"
                                        title="Select car model">
                                        <option value="">Select Model</option>
                                        @foreach ($models as $model)
                                            <option value="{{ $model->id }}">
                                                {{ $model->model }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('selectedModelId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($selectedModelId)
                            <div class="mb-3" data-validation-field="selectedCarId">
                                <label class="form-label fw-semibold mb-1" for="selectedCarInput">
                                    Car <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-car"></i></span>
                                    <select id="selectedCarInput"
                                        class="form-control @error('selectedCarId') is-invalid @enderror"
                                        wire:model.live="selectedCarId" aria-required="true" data-bs-toggle="tooltip"
                                        title="Select available car">
                                        <option value="">Select Car</option>
                                        @foreach ($carsForModel as $car)
                                            <option value="{{ $car['id'] }}"
                                                @if ($car['status'] !== 'available') class="text-warning" @endif>
                                                {{ $car['plate_number'] }} - {{ $car['manufacturing_year'] }} -
                                                {{ $car['color'] }} - [{{ ucfirst($car['status']) }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('selectedCarId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        @if ($selectedCarId)
                            @php
                                $selectedCar = App\Models\Car::find($selectedCarId);
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
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-12">
                                        <strong>Price Tiers:</strong>
                                        <div class="d-flex flex-wrap">
                                            <span class="badge bg-secondary m-1">1-6 days:
                                                {{ $selectedCar->price_per_day_short }} AED</span>
                                            <span class="badge bg-secondary m-1">7-28 days:
                                                {{ $selectedCar->price_per_day_mid }} AED</span>
                                            <span class="badge bg-secondary m-1">28+ days:
                                                {{ $selectedCar->price_per_day_long }} AED</span>
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
                                            <span class="input-group-text"><i class="bx bxs-discount"></i></span>
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
                                                                    @if ($isCurrent)
                                                                        <span class="badge bg-danger">Currently
                                                                            Booking</span>
                                                                    @else
                                                                        <span class="badge bg-warning">Future
                                                                            Reservation</span>
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
                        <h6 class="text-primary mb-3">Location & Dates</h6>
                        <div class="mb-3" data-validation-field="pickup_location">
                            <label class="form-label fw-semibold mb-1" for="pickupLocationInput">
                                Pickup Location <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-map"></i></span>
                                <select id="pickupLocationInput"
                                    class="form-control @error('pickup_location') is-invalid @enderror"
                                    wire:model.live="pickup_location" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select pickup location">
                                    <option value="">Pickup Location</option>
                                    @foreach (array_keys($this->locationCosts) as $location)
                                        <option value="{{ $location }}">{{ $location }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('pickup_location')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="return_location">
                            <label class="form-label fw-semibold mb-1" for="returnLocationInput">
                                Return Location <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-map"></i></span>
                                <select id="returnLocationInput"
                                    class="form-control @error('return_location') is-invalid @enderror"
                                    wire:model.live="return_location" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select return location">
                                    <option value="">Return Location</option>
                                    @foreach (array_keys($this->locationCosts) as $location)
                                        <option value="{{ $location }}">{{ $location }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('return_location')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="pickup_date">
                            <label class="form-label fw-semibold mb-1" for="pickupDateInput">
                                Pickup Date & Time <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="pickupDateInput" type="datetime-local"
                                    class="form-control @error('pickup_date') is-invalid @enderror"
                                    wire:model.live="pickup_date" aria-required="true" data-bs-toggle="tooltip"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}"
                                    title="Select pickup date and time">
                            </div>
                            @error('pickup_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3" data-validation-field="return_date">
                            <label class="form-label fw-semibold mb-1" for="returnDateInput">
                                Return Date & Time <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                <input id="returnDateInput" type="datetime-local"
                                    class="form-control @error('return_date') is-invalid @enderror"
                                    wire:model.live="return_date" aria-required="true" data-bs-toggle="tooltip"
                                    title="Select return date and time">
                            </div>
                            @error('return_date')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-money"></i></span>
                            <input type="text" class="form-control" value="{{ number_format($final_total) }} AED"
                                disabled data-bs-toggle="tooltip" title="Total contract amount">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-user"></i></span>
                            <select class="form-control @error('agent_sale') is-invalid @enderror"
                                wire:model="agent_sale" data-bs-toggle="tooltip" title="Select agent or Website">
                                <option value="Website">Website</option>
                                <option value="Alireza bakhshi">Alireza bakhshi</option>
                                <option value="Mohammadreza bakhshi">Mohammadreza bakhshi</option>
                                <option value="TACI">TACI</option>
                                <option value="Foad sharifian">Foad sharifian</option>
                                <option value="Shahrokh gasht">Shahrokh gasht</option>
                                <option value="Zaman parvaz">Zaman parvaz</option>
                                <option value="Hotel review global">Hotel review global</option>
                                <option value="Dubai discount">Dubai discount</option>
                                <option value="Mrs Saei">Mrs Saei</option>
                                <option value="Dubai offer">Dubai offer</option>
                                <option value="Mr Navid">Mr Navid</option>
                                <option value="Mrs khorrami">Mrs khorrami</option>
                                <option value="Mr soleimani">Mr soleimani</option>
                                <option value="Mrs shams">Mrs shams</option>
                                <option value="Mrs hashempour">Mrs hashempour</option>
                                <option value="Sepris">Sepris</option>
                                <option value="Javed">Javed</option>
                                <option value="Arkarsh">Arkarsh</option>
                            </select>
                            @error('agent_sale')
                                <div class="invalid-feedback animate__animated animate__fadeIn">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="bx bx-note"></i></span>
                            <textarea class="form-control" wire:model="notes" placeholder="Contract Notes" data-bs-toggle="tooltip"
                                title="Add any contract notes"></textarea>
                        </div>

                        <h6 class="text-primary mb-3 mt-4">Services & Insurance</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-2">Additional Services</h6>
                                @foreach ($services as $key => $service)
                                    @if (!in_array($key, ['ldw_insurance', 'scdw_insurance']))
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
                                                    {{ number_format($service['amount']) }} AED
                                                    @if ($service['per_day'])
                                                        /day
                                                    @endif
                                                @else
                                                    Free
                                                @endif
                                            </label>
                                        </div>
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
                                            {{ number_format($ldw_daily_rate) }} AED/day
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
                                            {{ number_format($scdw_daily_rate) }} AED/day
                                        @else
                                            --
                                        @endif
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Breakdown -->
        <div class="my-4">
            <h5 class="text-primary mb-3">Cost Breakdown</h5>
            <div class="table-responsive">
                <table class="table table-bordered shadow-sm">
                    <tr>
                        <th>Daily Rate (after discount if applied)</th>
                        <td>{{ number_format($dailyRate) }} AED</td>
                    </tr>
                    <tr>
                        <th>Base Rental Cost
                            @if ($rental_days)
                                ({{ $rental_days }} days)
                            @endif
                        </th>
                        <td>{{ number_format($base_price) }} AED</td>
                    </tr>
                    <tr>
                        <th>Pickup Transfer Cost</th>
                        <td>{{ number_format($transfer_costs['pickup']) }} AED</td>
                    </tr>
                    <tr>
                        <th>Return Transfer Cost</th>
                        <td>{{ number_format($transfer_costs['return']) }} AED</td>
                    </tr>
                    <tr>
                        <th>Additional Services</th>
                        <td>{{ number_format($services_total) }} AED</td>
                    </tr>
                    <tr>
                        <th>Insurance</th>
                        <td>{{ number_format($insurance_total) }} AED</td>
                    </tr>
                    <tr class="table-secondary">
                        <th>Subtotal</th>
                        <td>{{ number_format($subtotal) }} AED</td>
                    </tr>
                    <tr>
                        <th>Tax (5%)</th>
                        <td>{{ number_format($tax_amount) }} AED</td>
                    </tr>
                    <tr class="table-primary">
                        <th>Total Amount</th>
                        <td>{{ number_format($final_total) }} AED</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Sticky Save Button -->
        <div class="fixed-bottom bg-white p-3 shadow-lg d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-lg transition-all duration-300 hover:bg-primary-dark">
                <i class="bx bx-save me-2"></i> Create Contract
            </button>
        </div>
    </form>
</div>

@include('components.panel.form-error-highlighter')

@section('styles')
    <style>
        .transition-all {
            transition: all 0.3s ease;
        }

        .hover\:bg-primary-dark:hover {
            background-color: #0052cc;
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

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection
