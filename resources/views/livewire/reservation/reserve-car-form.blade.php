<!-- main container start -->
<div class="center-box d-flex justify-content-center align-items-center">
    <div class="wrapper p-4">

        <div class="gx-1">
            <!-- End Sidebar -->
            <form class="col-md-12 p-1 needs-validation" id="checkoutForm" action="{{ route('reserve.car') }}"
                method="post" novalidate>
                @csrf

                <!-- Start profile step -->
                <div class="step step-1 row profile-step d-none">
                    <header class="col-12">
                        <h1>Profile Info</h1>
                        <p class="lead">Please provide your name, email address, and phone number.</p>
                    </header>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" class="form-control" name="first_name"
                            wire:model="first_name" placeholder="First Name" />
                        <div class="invalid-feedback">First Name is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" class="form-control" name="last_name"
                            wire:model="last_name" placeholder="Last Name" />
                        <div class="invalid-feedback">Last Name is required!</div>
                    </div>


                    <div class="mb-3 col-12 col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" required id="email" name="email"
                            wire:model="email" placeholder="name@example.com" />
                        <div class="invalid-feedback">Valid Email is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" required id="phone" name="phone"
                            wire:model="phone" placeholder="eg 09123456789" />
                        <div class="invalid-feedback">Phone is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="pickup_location" class="form-label">Pickup Location</label>
                        <select id="pickup_location" class="form-select" name="pickup_location"
                            wire:model="pickup_location">
                            <option value="">Location</option>
                            <option value="امارات/دبی/میدان ساعت/شعبه مرکزی">امارات/دبی/میدان ساعت/شعبه مرکزی</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 1">امارات/دبی/فرودگاه دبی/ترمینال 1</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 2">امارات/دبی/فرودگاه دبی/ترمینال 2</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 3">امارات/دبی/فرودگاه دبی/ترمینال 3</option>
                            <option value="امارات/دبی/مرکز شهر">امارات/دبی/مرکز شهر</option>
                            <option value="امارات/دبی/جمیرا ۱.۲.۳">امارات/دبی/جمیرا ۱.۲.۳</option>
                            <option value="امارات/دبی/پالم">امارات/دبی/پالم</option>
                            <option value="امارات/دبی/Damac Hills">امارات/دبی/Damac Hills</option>
                            <option value="امارات/دبی/JVC">امارات/دبی/JVC</option>
                            <option value="امارات/دبی/JLT">امارات/دبی/JLT</option>
                            <option value="امارات/دبی/مارینا">امارات/دبی/مارینا</option>
                            <option value="امارات/دبی/JBR">امارات/دبی/JBR</option>
                            <option value="امارات/امارت دبی/جبل علی – ابن بطوطه – حتا و…">امارات/امارت دبی/جبل علی – ابن
                                بطوطه – حتا و…</option>
                            <option value="امارات / فرودگاه شارجه">امارات / فرودگاه شارجه</option>
                            <option value="امارات / فرودگاه ابوظبی">امارات / فرودگاه ابوظبی</option>

                        </select>
                        <div class="invalid-feedback">Please select a valid Pickup Location.</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="return_location" class="form-label">Return Location</label>
                        <select id="return_location" class="form-select" name="return_location"
                            wire:model="return_location">
                            <option value="">Location</option>
                            <option value="امارات/دبی/میدان ساعت/شعبه مرکزی">امارات/دبی/میدان ساعت/شعبه مرکزی</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 1">امارات/دبی/فرودگاه دبی/ترمینال 1</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 2">امارات/دبی/فرودگاه دبی/ترمینال 2</option>
                            <option value="امارات/دبی/فرودگاه دبی/ترمینال 3">امارات/دبی/فرودگاه دبی/ترمینال 3</option>
                            <option value="امارات/دبی/مرکز شهر">امارات/دبی/مرکز شهر</option>
                            <option value="امارات/دبی/جمیرا ۱.۲.۳">امارات/دبی/جمیرا ۱.۲.۳</option>
                            <option value="امارات/دبی/پالم">امارات/دبی/پالم</option>
                            <option value="امارات/دبی/Damac Hills">امارات/دبی/Damac Hills</option>
                            <option value="امارات/دبی/JVC">امارات/دبی/JVC</option>
                            <option value="امارات/دبی/JLT">امارات/دبی/JLT</option>
                            <option value="امارات/دبی/مارینا">امارات/دبی/مارینا</option>
                            <option value="امارات/دبی/JBR">امارات/دبی/JBR</option>
                            <option value="امارات/امارت دبی/جبل علی – ابن بطوطه – حتا و…">امارات/امارت دبی/جبل علی – ابن
                                بطوطه – حتا و…</option>
                            <option value="امارات / فرودگاه شارجه">امارات / فرودگاه شارجه</option>
                            <option value="امارات / فرودگاه ابوظبی">امارات / فرودگاه ابوظبی</option>
                        </select>
                        <div class="invalid-feedback">Please select a valid Return Location.</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="pickup_date" class="form-label">Pickup Date & Time</label>
                        <input type="datetime-local" id="pickup_date" class="form-control" name="pickup_date"
                            wire:model="pickup_date" placeholder="Pickup Date & Time"
                            min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" />
                        <div class="invalid-feedback">Pickup Date is required!</div>
                    </div>
                    
                    <div class="mb-3 col-12 col-md-6">
                        <label for="return_date" class="form-label">Return Date & Time</label>
                        <input type="datetime-local" id="return_date" class="form-control" name="return_date"
                            wire:model="return_date" placeholder="Return Date & Time"
                            min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i') }}" />
                        <div class="invalid-feedback">Return Date is required!</div>
                    </div>



                    <div class="mb-3 col-12 col-md-6">
                        <label for="messenger_phone" class="form-label">Telegram/WhatsApp Number</label>
                        <input type="tel" class="form-control" required id="messenger_phone"
                            name="messenger_phone" wire:model="messenger_phone" placeholder="eg 09123456789" />
                        <div class="invalid-feedback">Messenger number is required!</div>
                    </div>

                    <div id="date-error" class="bad-feedback d-none">Return Date must be after Pickup Date.</div>

                </div>
                <!-- End profile-step -->

                <!-- Start car step -->
                <div class="step row car-step d-none" wire:ignore.self>
                    <header class="col-12">
                        <h1>Select your car</h1>
                        {{-- <p class="lead">You have the option of monthly or yearly billing.</p> --}}
                    </header>

                    <!-- Select Box برای فیلتر برندها -->
                    <div class="form-check mb-1 col-12 col-md-6">
                        <label for="brand" class="form-label">Filter by Brand</label>
                        <select id="brand" class="form-select" wire:model.live="selectedBrand">
                            <option value="">All Brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check cars col-12" id="month-car">
                        @foreach ($cars as $car)
                            <label class="col-lg-12 car car-box {{ $selectedCar?->id === $car->id ? 'checked' : '' }}"
                                wire:click="selectCar({{ $car }})">
                                <!-- Radio input -->
                                <input type="radio" name="carId" id="car-{{ $car->id }}"
                                    class="form-check-input car-type d-none" value="{{ $car->id }}" />
                                <input type="hidden" name="carId" value="{{ $selectedCar?->id }}">
                                <!-- Box layout -->
                                <div
                                    class="car-box-container d-flex flex-column flex-md-row-reverse shadow-sm p-3 rounded row">
                                    <!-- Car Image -->
                                    <div class="car-image mb-3 mb-md-0 text-md-right col-12 col-md-6 col-lg-5">
                                        <img src="{{ $car->carModel->images ? asset('assets/car-pics/' . $car->carModel->images->file_name) : asset('assets/car-pics/car test.webp') }}"
                                            class="img-fluid rounded" alt="{{ $car->carModel->brand }} Thumbnail" />
                                    </div>

                                    <!-- Car Information -->
                                    <div class="car-info flex-grow-1 text-md-left col-12 col-md-6 col-lg-7">

                                        <h4 class="car-name">
                                            {{ $car->carModel->brand }} {{ $car->carModel->model }}
                                        </h4>

                                        <!-- Manufacturing Year -->
                                        <h5 class="car-year text-secondary">
                                            {{ $car->manufacturing_year }}
                                        </h5>

                                        <div class="row g-3">
                                            <!-- Pricing Table -->
                                            <div class="col-12 col-sm-12 col-md-4 col-lg-6">
                                                <table class="table table-sm mb-0 styled-table " wire:ignore.self>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">Daily</th>
                                                            <td>137 AED</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">2 to 7 Days</th>
                                                            <td>134 AED</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">7 to 20 Days</th>
                                                            <td>133 AED</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">More than 20 Days</th>
                                                            <td>130 AED</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">Deposit</th>
                                                            <td>{{ rand(100, 999) }} AED</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <!-- New Column (Additional Info/Badges) -->
                                            <div class="col-12 col-sm-12 col-md-4 col-lg-2">
                                                <div class="d-flex flex-column badge-container">
                                                    <div>
                                                        <span class="badge badge-neutral">
                                                            <img src="{{ asset('assets/reserve/assets/images/gearbox.png') }}"
                                                                alt="Gearbox Icon" class="icon mr-2"> A
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-neutral">
                                                            <img src="{{ asset('assets/reserve/assets/images/car-seat.png') }}"
                                                                alt="Seat Icon" class="icon mr-2"> 5
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-neutral">
                                                            <img src="{{ asset('assets/reserve/assets/images/car-door.png') }}"
                                                                alt="Door Icon" class="icon mr-2"> 4
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-neutral">
                                                            <img src="{{ asset('assets/reserve/assets/images/suitcases.png') }}"
                                                                alt="Suitcase Icon" class="icon mr-2">
                                                            {{ rand(1, 3) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-neutral">
                                                            <img src="{{ asset('assets/reserve/assets/images/air.png') }}"
                                                                alt="AC Icon" class="icon mr-2"> A/C
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Badges (Existing Column) -->
                                            <div class="col-12 col-sm-12 col-md-4 col-lg-2">
                                                <div class="d-flex flex-column badge-container">
                                                    <div>
                                                        <span class="badge badge-success">
                                                            <img src="{{ asset('assets/reserve/assets/images/car-insurance.png') }}"
                                                                alt="Gearbox Icon" class="icon mr-2"> Insurance
                                                            included
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-success">
                                                            <img src="{{ asset('assets/reserve/assets/images/car-seat.png') }}"
                                                                alt="Seat Icon" class="icon mr-2"> Minimum 2 Days
                                                            Rental

                                                        </span>
                                                    </div>

                                                    <div>
                                                        <span class="badge badge-success">
                                                            <img src="{{ asset('assets/reserve/assets/images/speed.png') }}"
                                                                alt="AC Icon" class="icon mr-2"> Unlimited Mileage
                                                        </span>
                                                    </div>

                                                    <div>
                                                        <span class="badge badge-info">
                                                            <img src="{{ asset('assets/reserve/assets/images/check-mark.png') }}"
                                                                alt="Door Icon" class="icon mr-2"> Free cancellation
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="badge badge-danger">
                                                            <img src="{{ asset('assets/reserve/assets/images/check-mark.png') }}"
                                                                alt="Suitcase Icon" class="icon mr-2"> No Deposit
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </label>
                        @endforeach
                    </div>
                    <div class="bad-feedback-car bad-feedback d-none">Please choose a car.</div>
                </div>
                <!-- End car-step -->

                <!-- Start review step -->
                <div class="step step-3 row review-step d-none">
                    <header class="col-12">
                        <h1>Review and Confirm</h1>
                        <p class="lead">Please review your information and confirm your booking.</p>
                    </header>

                    <!-- Display Profile Info -->
                    <div class="col-12 mb-3">
                        <h4>Profile Information</h4>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>First Name:</strong> {{ $first_name }}</li>
                            <li class="list-group-item"><strong>Last Name:</strong> {{ $last_name }}</li>
                            <li class="list-group-item"><strong>Email:</strong> {{ $email }}</li>
                            <li class="list-group-item"><strong>Phone:</strong> {{ $phone }}</li>
                        </ul>
                    </div>

                    <!-- Display Pickup & Return Info -->
                    <div class="col-12 mb-3">
                        <h4>Pickup and Return Details</h4>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Pickup Location:</strong> {{ $pickup_location }}</li>
                            <li class="list-group-item"><strong>Return Location:</strong> {{ $return_location }}</li>
                            <li class="list-group-item"><strong>Pickup Date:</strong> {{ $pickup_date }}</li>
                            <li class="list-group-item"><strong>Return Date:</strong> {{ $return_date }}</li>
                        </ul>
                    </div>

                    <!-- Display Selected Car -->
                    <div class="col-12 mb-3">
                        <h4>Selected Car</h4>
                        @if ($selectedCar)
                            <div class="card">
                                <img src="{{ $selectedCar->carModel->images ? asset('assets/car-pics/' . $selectedCar->carModel->images->file_name) : asset('assets/car-pics/car test.webp') }}"
                                    class="card-img-top" alt="Car Image">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $selectedCar->carModel->brand }}
                                        {{ $selectedCar->carModel->model }}
                                    </h5>
                                    <p class="card-text">Year: {{ $selectedCar->manufacturing_year }}</p>
                                </div>
                            </div>
                        @else
                            <p>No car selected.</p>
                        @endif
                    </div>

                </div>
                <!-- End review step -->


                <!-- Start addons step -->
                <div class="step step-4 row review-step d-none">
                    <header class="col-12">
                        <h1>Review and Confirm</h1>
                        <p class="lead">Please review your payment and terms, and confirm your booking.</p>
                    </header>

                    <!-- Display Deposit Information -->
                    <div class="col-12 mb-3">
                        <h4>Deposit and Payment Details</h4>
                        <ul class="list-group">
                            <li class="list-group-item"><strong>Deposit Amount:</strong> 50,000,000 تومان</li>
                            <li class="list-group-item"><strong>Damage Deposit (Check):</strong> 800,000,000 تومان</li>
                            <li class="list-group-item"><strong>Total Deposit:</strong> 150,000,000 تومان (based on
                                provided documents)</li>
                            <li class="list-group-item"><strong>Reservation Fee:</strong> 5,150,000 تومان</li>
                            <li class="list-group-item"><strong>Total Payment:</strong> {{ rand(100000, 999999) }}
                                تومان</li>
                            <input type="hidden" name="total_price" value="{{ rand(100000, 999999) }}">
                        </ul>
                    </div>

                    <!-- Display Terms and Conditions -->
                    <div class="col-12 mb-3">
                        <h4>Terms and Conditions</h4>
                        <p>Please review the terms and conditions of the contract. By confirming your booking, you
                            accept the following conditions:</p>
                        <ul class="list-group">
                            <li class="list-group-item">Condition 1: The deposit is refundable upon return of the car
                                in good condition.</li>
                            <li class="list-group-item">Condition 2: Any damage to the car will result in a deduction
                                from the damage deposit.</li>
                            <li class="list-group-item">Condition 3: Late returns will incur additional charges as per
                                the rental agreement.</li>
                        </ul>
                    </div>

                    <!-- Display Confirmation Section -->
                    <div class="col-12">
                        <h4>Confirm Your Booking</h4>
                        <p>Please confirm that you have reviewed all the details and agree to the terms and conditions.
                        </p>

                        <!-- Checkboxes for Confirmation -->
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="confirm_deposit" />
                            <label class="form-check-label" for="confirm_deposit">I confirm the deposit and payment
                                details.</label>
                            <div class="invalid-feedback">Confirmation of the deposit is required!</div>

                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="accept_terms" />
                            <label class="form-check-label" for="accept_terms">I accept the terms and
                                conditions.</label>
                            <div class="invalid-feedback">Acceptance of terms and conditions is required!</div>
                        </div>

                    </div>
                </div>
                <!-- End addons step -->

                <div class="next-step mt-5 d-flex align-items-center">
                    <button type="button" id="back" class="fw-bold btn">Go Back</button>

                    <button type="button" id="next" class="btn btn-red ms-auto">Next Step</button>
                </div>

            </form>
            <!-- Start thanks step -->
            <div class="thanks-step step d-flex align-items-center col-md-8 d-none">
                <header class="d-flex flex-column align-items-center text-center">
                    <!-- Thank You Icon -->
                    <img class="w-50 mb-4" src="{{ asset('assets/reserve/assets/images/icon-thank-you.svg') }}"
                        alt="Thank You Icon" />

                    <!-- Title -->
                    <h1 class="display-4">Thank You for Your Booking!</h1>

                    <!-- Message -->
                    <p class="text-center lead mb-4">
                        Your rental request has been successfully submitted to our system. Our experts will contact you
                        soon
                        for confirmation and further details.
                    </p>

                    <!-- Additional Instructions -->
                    <div class="alert alert-secondary w-100 mb-4" role="alert">
                        <strong>Note:</strong> Please keep an eye on your email for booking updates and confirmation
                        from our team.
                    </div>

                    <!-- Support Information -->
                    <p>If you need any assistance or have questions, don't hesitate to reach out to us:</p>
                    <p><strong>Email:</strong> <a href="mailto:support@loremgaming.com">support@loremgaming.com</a></p>

                    <!-- Call to Action Button -->
                    <a href="https://karaplusrental.com/" class="btn btn-primary mt-4">Back to Homepage</a>
                </header>
            </div>

            <!-- End thanks-step -->
        </div>
    </div>
</div>
