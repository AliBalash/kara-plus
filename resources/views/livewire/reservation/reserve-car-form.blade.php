<!-- main container start -->
<div class="center-box d-flex justify-content-center align-items-center">
    <div class="wrapper p-4">
        <label for="darkmode">
            <input type="checkbox" id="darkmode" />
            <div class="theme-mode d-flex">
                <img src="{{ asset('assets/reserve/assets/images/dark.png') }}" alt="darkMode icon from flat icons" />
            </div>
        </label>
        <div class="row gx-1 ">
            <!-- Start Sidebar -->
            <aside class="col-md-4 d-none">
                <div class="sidebar p-5">
                    <div class="steps d-flex justify-content-center align-items-center mb-4">
                        <div
                            class="icon d-flex justify-content-center align-items-center border border-2 rounded-circle me-2 checked">
                            1</div>
                        <div class="text flex-grow-1 d-none d-md-block">
                            <span class="d-block">Step 1</span>
                            <span class="text-uppercase text-white fw-bold">Your Info</span>
                        </div>
                    </div>
                    <div class="steps d-flex justify-content-center align-items-center mb-4">
                        <div
                            class="icon d-flex justify-content-center align-items-center border border-2 rounded-circle me-2">
                            2</div>
                        <div class="text flex-grow-1 d-none d-md-block">
                            <span class="d-block">Step 2</span>
                            <span class="text-uppercase text-white fw-bold">Select car</span>
                        </div>
                    </div>
                    <div class="steps d-flex justify-content-center align-items-center mb-4">
                        <div
                            class="icon d-flex justify-content-center align-items-center border border-2 rounded-circle me-2">
                            3</div>
                        <div class="text flex-grow-1 d-none d-md-block">
                            <span class="d-block">Step 3</span>
                            <span class="text-uppercase text-white fw-bold">Add-ons</span>
                        </div>
                    </div>

                    <div class="steps d-flex justify-content-center align-items-center mb-4">
                        <div
                            class="icon d-flex justify-content-center align-items-center border border-2 rounded-circle me-2">
                            4</div>
                        <div class="text flex-grow-1 d-none d-md-block">
                            <span class="d-block">Step 4</span>
                            <span class="text-uppercase text-white fw-bold">Summary</span>
                        </div>
                    </div>
                </div>
            </aside>
            <!-- End Sidebar -->
            <form class="col-md-12 p-1 needs-validation" id="checkoutForm" wire:submit.prevent="submitForm" novalidate>


                <!-- Start profile step -->
                <div class="step step-1 row profile-step d-none">
                    <header class="col-12">
                        <h1>Profile Info</h1>
                        <p class="lead">Please provide your name, email address, and phone number.</p>
                    </header>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" class="form-control" wire:model="first_name"
                            placeholder="First Name" />
                        <div class="invalid-feedback">First Name is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" class="form-control" wire:model="last_name"
                            placeholder="Last Name" />
                        <div class="invalid-feedback">Last Name is required!</div>
                    </div>


                    <div class="mb-3 col-12 col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" required id="email" wire:model="email"
                            placeholder="name@example.com" />
                        <div class="invalid-feedback">Valid Email is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" required id="phone" wire:model="phone"
                            placeholder="eg 09123456789" />
                        <div class="invalid-feedback">Phone is required!</div>
                    </div>


                    <div class="mb-3 col-12 col-md-6">
                        <label for="pickup_date" class="form-label">Pickup Date</label>
                        <input type="date" id="pickup_date" class="form-control" wire:model="pickup_date"
                            placeholder="Pickup Date" />
                        <div class="invalid-feedback">Pickup Date is required!</div>
                    </div>

                    <div class="mb-3 col-12 col-md-6">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" id="return_date" class="form-control" wire:model="return_date"
                            placeholder="Return Date" />
                        <div class="invalid-feedback">Return Date is required!</div>
                    </div>


                    <div class="mb-3 col-12 col-md-6">
                        <label for="messenger_phone" class="form-label">Telegram/WhatsApp Number</label>
                        <input type="tel" class="form-control" required id="messenger_phone"
                            wire:model="messenger_phone" placeholder="eg 09123456789" />
                        <div class="invalid-feedback">Messenger number is required!</div>
                    </div>

                    <div id="date-error" class="bad-feedback d-none">Return Date must be after Pickup Date.</div>

                </div>
                <!-- End profile-step -->

                <div class="step row plan-step d-none" wire:ignore.self>
                    <header class="col-12">
                        <h1>Select your plan</h1>
                        {{-- <p class="lead">You have the option of monthly or yearly billing.</p> --}}
                    </header>

                    <!-- Select Box برای فیلتر برندها -->
                    <div class="mb-1 col-12 col-md-6">
                        <label for="brand" class="form-label">Filter by Brand</label>
                        <select id="brand" class="form-select" wire:model.live="selectedBrand">
                            <option value="">All Brands</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand }}">{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="form-check plans row" id="month-plan">
                        @foreach ($cars as $car)
                            <label class="col-lg-12 plan car-box {{ $selectedCar === $car->id ? 'checked' : '' }}"
                                wire:click="selectCar({{ $car->id }})">
                                <!-- Radio input -->
                                <input type="radio" name="plan" id="car-{{ $car->id }}"
                                    class="form-check-input plan-type d-none" value="{{ $car->id }}" />
                    
                                <!-- Box layout -->
                                <div class="car-box-container d-flex flex-column flex-md-row align-items-center shadow-sm p-3 rounded">
                                    <!-- Car Information -->
                                    <div class="car-info flex-grow-1 text-left">
                                        <div class="overlay-content">
                                            <!-- Display car name and details -->
                                            <div class="text-overlay">
                                                <h5>
                                                    <i class="fa fa-car text-primary mr-2"></i>
                                                    {{ $car->carModel->brand }} {{ $car->carModel->model }}
                                                </h5>
                                                <h6>
                                                    <i class="fa fa-calendar-alt text-secondary mr-2"></i>
                                                    Year: {{ $car->manufacturing_year }}
                                                </h6>
                                                <h6 class="mt-2 text-success">
                                                    <i class="fa fa-tag mr-2"></i>
                                                    5500 AED/day
                                                </h6>
                                                <h6 class="mt-2 text-info">
                                                    <i class="fa fa-calendar-week mr-2"></i>
                                                    35000 AED/week
                                                </h6>
                                                <h6 class="mt-2 text-danger">
                                                    <i class="fa fa-exclamation-triangle mr-2"></i>
                                                    Deposit: 500 AED
                                                </h6>
                                            </div>
                                        </div>
                                        <!-- Features with icons -->
                                        <div class="features mt-3 d-flex flex-wrap align-items-center">
                                            <!-- Automatic/Manual -->
                                            <div class="feature-item d-flex align-items-center mr-3">
                                                <span class="badge badge-primary">Primary</span>
                                                {{ $car->transmission === 'automatic' ? 'Automatic' : 'Manual' }}
                                            </div>
                                            <!-- Capacity -->
                                            <div class="feature-item d-flex align-items-center mr-3">
                                                <i class="fa fa-users text-secondary mr-2"></i>
                                                Capacity: {{ $car->capacity ?? 5 }}
                                            </div>
                                            <!-- Fuel Type -->
                                            <div class="feature-item d-flex align-items-center mr-3">
                                                <i class="fa fa-gas-pump text-success mr-2"></i>
                                                Fuel: {{ $car->fuel_type ?? 'Gasoline' }}
                                            </div>
                                            <!-- Number of Doors -->
                                            <div class="feature-item d-flex align-items-center">
                                                <i class="fa fa-door-closed text-warning mr-2"></i>
                                                Doors: {{ $car->doors ?? 4 }}
                                            </div>
                                        </div>
                                            
                                    </div>
                                    <!-- Car Image -->
                                    <div class="car-image ml-md-3">
                                        <img src="{{ $car->carModel->images ? asset('assets/car-pics/' . $car->carModel->images->file_name) : asset('assets/car-pics/cartest.webp') }}"
                                            class="img-fluid rounded" alt="{{ $car->carModel->brand }} Thumbnail" />
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    
                    
                    





                    <!-- Display Cars -->
                    {{-- <div class="form-check plans row" id="month-plan">
                        @foreach ($cars as $car)
                            <div class="col-12">
                                <label
                                    class="plan shadow-sm p-3 m-1 rounded d-flex d-md-block position-relative col-12 col-sm-6 col-md-6 col-lg-4 {{ $selectedCar === $car->id ? 'checked' : '' }}"
                                    wire:click="selectCar({{ $car->id }})">
                                    <input type="radio" name="plan" id="car-{{ $car->id }}"
                                        class="form-check-input plan-type d-none" value="{{ $car->id }}" />

                                    <!-- Display car image -->
                                    <img src="{{ $car->carModel->images ? asset('assets/car-pics/' . $car->carModel->images->file_name) : asset('assets/car-pics/cartest.webp') }}"
                                        class="thumbnail-img rounded " alt="{{ $car->carModel->brand }} Thumbnail" />

                                    <div class="overlay-content">
                                        <!-- Display car name and details -->
                                        <div class="text-overlay">
                                            <h5>{{ $car->carModel->brand }} {{ $car->carModel->model }}</h5>
                                            <h6>{{ $car->manufacturing_year }}</h6>
                                            <h6 class="mt-2">5500 AED/day</h6>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div> --}}


                    

                    <div class="bad-feedback-plan bad-feedback d-none">Please choose a car.</div>


                </div>







                <!-- Start addons step -->
                <div class="step addons-step p-4" wire:ignore>
                    <header>
                        <h1>Pick add-on</h1>
                        <p class="lead">Add-ons help enhance your gaming experience.</p>
                    </header>
                    <div class="addons d-flex gap-2 flex-column" id="monthly-addons">
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="month-onlineService"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Online service</p>
                                <p>Access to multiplayer games</p>
                            </div>
                            <div class="ms-auto">+$1/mo</div>
                        </div>
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="month-largeStorage"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Larger storage</p>
                                <p>Extra 1TB of cloud save</p>
                            </div>
                            <div class="ms-auto">+$2/mo</div>
                        </div>
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="month-customProfile"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Customizable Profile</p>
                                <p>Custom theme on your profile</p>
                            </div>
                            <div class="ms-auto">+$1/mo</div>
                        </div>
                    </div>
                    <div class="addons d-flex gap-2 flex-column d-none" id="yearly-addons">
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="year-onlineService"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Online service</p>
                                <p>Access to multiplayer games</p>
                            </div>
                            <div class="ms-auto">+$10/yr</div>
                        </div>
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="year-largeStorage"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Larger storage</p>
                                <p>Extra 1TB of cloud save</p>
                            </div>
                            <div class="ms-auto">+$20/yr</div>
                        </div>
                        <div class="addon shadow-sm p-3 mb-2 mb-md-3 rounded d-flex align-items-center">
                            <div class="me-4">
                                <input class="form-check-input mt-0" type="checkbox" value="year-customProfile"
                                    name="addon" aria-label="Checkbox for following text input" />
                            </div>
                            <div>
                                <p>Customizable Profile</p>
                                <p>Custom theme on your profile</p>
                            </div>
                            <div class="ms-auto">+$20/yr</div>
                        </div>
                    </div>
                </div>
                <!-- End addon-step -->
                <!-- Start summary step -->
                <div class="step summary-step d-none">
                    <header>
                        <h1>Finishing up</h1>
                        <p class="lead">Double-check everything looks OK before confirming.</p>
                    </header>
                    <div class="summary d-flex gap-2 flex-column">
                        <div class="plan p-3 mb-2 mb-md-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex flex-column">
                                <span class="fw-bold plan-name"></span>
                                <a href="#changePlan" id="changePlan">Change</a>
                            </div>
                            <div class="plan-price"></div>
                        </div>
                        <hr />
                    </div>
                </div>
                <!-- End summary-step -->
                <div class="next-step mt-1 d-flex align-items-center">
                    <button type="button" id="back" class="fw-bold btn">Go Back</button>

                    <button type="button" id="next" class="btn btn-primary ms-auto">Next Step</button>
                </div>
            </form>
            <!-- Start thanks step -->
            <div class="thanks-step step d-flex align-items-center col-md-8 d-none">
                <header class="d-flex flex-column align-items-center">
                    <img class="w-25 mb-4" src="{{ asset('assets/reserve/assets/images/icon-thank-you.svg') }}"
                        alt="" />

                    <h1>Thank You</h1>
                    <p class="text-center lead">
                        Thanks for confirming your subscription! We hope you have fun using our platform. If you
                        ever need support, please feel free to email us at support@loremgaming.com.
                    </p>
                </header>
            </div>
            <!-- End thanks-step -->
        </div>
    </div>
</div>
