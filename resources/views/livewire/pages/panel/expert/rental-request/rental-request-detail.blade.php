<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Detail</h4>

    <div>
        @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
        @endif

        <div class="row">
            <!-- Contract Information -->
            <div class="col-md-6">
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Contract Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">
                            <!-- Start Date -->
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <span class="form-control">{{ $contract->start_date }}</span>
                            </div>

                            <!-- End Date -->
                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <span class="form-control">{{ $contract->end_date }}</span>
                            </div>

                            <!-- Total Price -->
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <span class="form-control">{{ $contract->total_price }}</span>
                            </div>

                            <!-- Status -->
                            <div class="input-group">
                                <span class="input-group-text">Status</span>
                                <span class="form-control">{{ $contract->status }}</span>
                            </div>

                            <!-- Notes -->
                            <div class="input-group">
                                <span class="input-group-text">Note</span>
                                <span class="form-control">{{ $contract->notes }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Car Information -->
                <div class="col-md-12">
                    <div class="card mb-4">
                        <h5 class="card-header">Car Information</h5>
                        <div class="card-body demo-vertical-spacing demo-only-element">

                            <!-- Car Brand -->
                            <div class="input-group">
                                <span class="input-group-text">Car Brand</span>
                                <span class="form-control">{{ $contract->car->carModel->brand }}</span>
                            </div>

                            <!-- Car Model -->
                            <div class="input-group">
                                <span class="input-group-text">Car Model</span>
                                <span class="form-control">{{ $contract->car->carModel->model }}</span>
                            </div>

                            <!-- Plate Number -->
                            <div class="input-group">
                                <span class="input-group-text">Plate Number</span>
                                <span class="form-control">{{ $contract->car->plate_number }}</span>
                            </div>

                            <!-- Manufacturing Year -->
                            <div class="input-group">
                                <span class="input-group-text">Manufacturing Year</span>
                                <span class="form-control">{{ $contract->car->manufacturing_year }}</span>
                            </div>

                            <!-- Price Per Day -->
                            <div class="input-group">
                                <span class="input-group-text">Per Day $</span>
                                <span class="form-control">{{ $contract->car->price_per_day }}</span>
                            </div>

                            <!-- Service Due Date -->
                            <div class="input-group">
                                <span class="input-group-text">Service Due Date</span>
                                <span class="form-control">{{ $contract->car->service_due_date }}</span>
                            </div>
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
                            <span class="input-group-text">First Name</span>
                            <span class="form-control">{{ $contract->customer->first_name }}</span>
                        </div>

                        <!-- Last Name -->
                        <div class="input-group">
                            <span class="input-group-text">Last Name</span>
                            <span class="form-control">{{ $contract->customer->last_name }}</span>
                        </div>

                        <!-- Email -->
                        <div class="input-group">
                            <span class="input-group-text">Email</span>
                            <span class="form-control">{{ $contract->customer->email }}</span>
                        </div>

                        <!-- Phone -->
                        <div class="input-group">
                            <span class="input-group-text">Phone</span>
                            <span class="form-control">{{ $contract->customer->phone }}</span>
                        </div>

                        <!-- Address -->
                        <div class="input-group">
                            <span class="input-group-text">Address</span>
                            <span class="form-control">{{ $contract->customer->address }}</span>
                        </div>

                        <!-- National Code -->
                        <div class="input-group">
                            <span class="input-group-text">National Code</span>
                            <span class="form-control">{{ $contract->customer->national_code }}</span>
                        </div>

                        <!-- Passport Number -->
                        <div class="input-group">
                            <span class="input-group-text">Passport Number</span>
                            <span class="form-control">{{ $contract->customer->passport_number }}</span>
                        </div>

                        <!-- Passport Expiry Date -->
                        <div class="input-group">
                            <span class="input-group-text">Passport Expiry Date</span>
                            <span class="form-control">{{ $contract->customer->passport_expiry_date }}</span>
                        </div>

                        <!-- Nationality -->
                        <div class="input-group">
                            <span class="input-group-text">Nationality</span>
                            <span class="form-control">{{ $contract->customer->nationality }}</span>
                        </div>

                        <!-- License Number -->
                        <div class="input-group">
                            <span class="input-group-text">License Number</span>
                            <span class="form-control">{{ $contract->customer->license_number }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
