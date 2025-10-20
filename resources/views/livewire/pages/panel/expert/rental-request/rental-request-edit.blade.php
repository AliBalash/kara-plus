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
                                                @if ($car['status'] !== 'available') class="text-warning" @endif>
                                                {{ $car['plate_number'] }} - {{ $car['manufacturing_year'] }} -
                                                {{ $car['color'] }} - [{{ ucfirst($car['status']) }}]
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
                                    @foreach (array_keys($this->locationCosts) as $location)
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
                                    @foreach (array_keys($this->locationCosts) as $location)
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
                                title="Add any contract notes">{{ $contract?->notes }}</textarea>
                        </div>

                        <!-- Services & Insurance -->
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
            }

            .btn-gradient-sunset {
                background: linear-gradient(135deg, #ff9f43, #ff6f61);
            }

            .btn-gradient-sunset:hover,
            .btn-gradient-ocean:hover {
                color: #fff;
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
