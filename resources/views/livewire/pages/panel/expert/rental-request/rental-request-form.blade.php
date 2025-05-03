<div>
    <div class="row">

        <div class="col-lg-6 text-start">
            <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Information</h4>
        </div>

        @if (!empty($contract->id))
            <div class="col-lg-6 text-end">
                <a class="btn btn-danger fw-bold" href="javascript:void(0);"
                    onclick="if(confirm('Are you sure you want to set this contract to Reserved?')) { @this.changeStatusToReserve({{ $contract->id }}) }">
                    Set to Reserved
                    <i class="bx bxs-log-in-circle"></i>
                </a>

            </div>
        @endif

    </div>



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
    @if (!empty($contract->id))
        <x-detail-rental-request-tabs :contract-id="$contract->id" />
    @endif

    <form wire:submit.prevent="submit">

        <div class="row">

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

                        <!-- Messenger Phone -->
                        <div class="input-group">
                            <span class="input-group-text" id="basic-addon-messenger-phone">Messenger Phone</span>
                            <input type="tel" class="form-control @error('messenger_phone') is-invalid @enderror"
                                placeholder="Messenger Phone" name="messenger_phone" wire:model="messenger_phone">
                            @error('messenger_phone')
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
                                min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" name="passport_expiry_date"
                                wire:model="passport_expiry_date" onfocus="this.value=''" />

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


            <!-- Contract Information -->
            <div class="col-md-6">

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

                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Contract Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-pickup-location">Pickup Location</span>
                                <select class="form-control @error('pickup_location') is-invalid @enderror"
                                    name="pickup_location" wire:model="pickup_location">
                                    <option value="">Location</option>
                                    <option value="UAE/Dubai/Clock Tower/Main Branch">UAE/Dubai/Clock Tower/Main Branch
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 1">UAE/Dubai/Dubai Airport/Terminal
                                        1
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 2">UAE/Dubai/Dubai Airport/Terminal
                                        2
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 3">UAE/Dubai/Dubai Airport/Terminal
                                        3
                                    </option>
                                    <option value="UAE/Dubai/Downtown">UAE/Dubai/Downtown</option>
                                    <option value="UAE/Dubai/Jumeirah 1, 2, 3">UAE/Dubai/Jumeirah 1, 2, 3</option>
                                    <option value="UAE/Dubai/Palm">UAE/Dubai/Palm</option>
                                    <option value="UAE/Dubai/Damac Hills">UAE/Dubai/Damac Hills</option>
                                    <option value="UAE/Dubai/JVC">UAE/Dubai/JVC</option>
                                    <option value="UAE/Dubai/JLT">UAE/Dubai/JLT</option>
                                    <option value="UAE/Dubai/Marina">UAE/Dubai/Marina</option>
                                    <option value="UAE/Dubai/JBR">UAE/Dubai/JBR</option>
                                    <option value="UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more">UAE/Dubai/Jebel
                                        Ali – Ibn
                                        Battuta – Hatta & more</option>
                                    <option value="UAE/Sharjah Airport">UAE/Sharjah Airport</option>
                                    <option value="UAE/Abu Dhabi Airport">UAE/Abu Dhabi Airport</option>
                                </select>
                                @error('pickup_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-pickup-location">Return Location</span>
                                <select class="form-control @error('return_location') is-invalid @enderror"
                                    name="return_location" wire:model="return_location">
                                    <option value="">Location</option>
                                    <option value="UAE/Dubai/Clock Tower/Main Branch">UAE/Dubai/Clock Tower/Main Branch
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 1">UAE/Dubai/Dubai Airport/Terminal
                                        1
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 2">UAE/Dubai/Dubai Airport/Terminal
                                        2
                                    </option>
                                    <option value="UAE/Dubai/Dubai Airport/Terminal 3">UAE/Dubai/Dubai Airport/Terminal
                                        3
                                    </option>
                                    <option value="UAE/Dubai/Downtown">UAE/Dubai/Downtown</option>
                                    <option value="UAE/Dubai/Jumeirah 1, 2, 3">UAE/Dubai/Jumeirah 1, 2, 3</option>
                                    <option value="UAE/Dubai/Palm">UAE/Dubai/Palm</option>
                                    <option value="UAE/Dubai/Damac Hills">UAE/Dubai/Damac Hills</option>
                                    <option value="UAE/Dubai/JVC">UAE/Dubai/JVC</option>
                                    <option value="UAE/Dubai/JLT">UAE/Dubai/JLT</option>
                                    <option value="UAE/Dubai/Marina">UAE/Dubai/Marina</option>
                                    <option value="UAE/Dubai/JBR">UAE/Dubai/JBR</option>
                                    <option value="UAE/Dubai/Jebel Ali – Ibn Battuta – Hatta & more">UAE/Dubai/Jebel
                                        Ali – Ibn
                                        Battuta – Hatta & more</option>
                                    <option value="UAE/Sharjah Airport">UAE/Sharjah Airport</option>
                                    <option value="UAE/Abu Dhabi Airport">UAE/Abu Dhabi Airport</option>
                                </select>
                                @error('return_location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Pickup Date & Time -->
                            <div class="input-group">
                                <span class="input-group-text" id="basic-addon-pickup-datetime">Pickup Date &
                                    Time</span>
                                <input type="datetime-local"
                                    class="form-control @error('pickup_date') is-invalid @enderror"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" name="pickup_date"
                                    wire:model="pickup_date">
                                @error('pickup_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>





                            <!-- Return Date & Time -->
                            <div class="input-group">
                                <span class="input-group-text">Return Date &
                                    Time</span>
                                <input type="datetime-local"
                                    class="form-control @error('return_date') is-invalid @enderror"
                                    min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" name="return_date"
                                    wire:model.live="return_date">
                                @error('return_date')
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

                            <div class="input-group">
                                <span class="input-group-text">Agent Sale</span>
                                <input type="text" class="form-control @error('agent_sale') is-invalid @enderror"
                                    placeholder="Agent Sale" name="agent_sale" wire:model="agent_sale">
                                @error('agent_sale')
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
