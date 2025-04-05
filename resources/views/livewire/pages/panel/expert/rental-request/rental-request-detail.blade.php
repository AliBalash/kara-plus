<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rental Request /</span> Detail</h4>


    <ul class="nav nav-pills flex-column flex-md-row mb-3">
        <li class="nav-item">
            <a class="nav-link active"
                href="{{ isset($contract->id) ? route('rental-requests.form', $contract->id) : '#' }}">
                <i class="bx bxs-info-square me-1"></i> Rental Information
            </a>
        </li>

        @if (isset($contract->customer))
            {{-- Customer Document --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('customer.documents', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-file me-1"></i> Customer Document
                    @if ($customerDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Pickup Document --}}
            <li class="nav-item">
                <a class="nav-link"
                    href="{{ route('rental-requests.pickup-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-upload me-1"></i> Pickup Document
                    @if ($pickupDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Return Document --}}
            <li class="nav-item">
                <a class="nav-link"
                    href="{{ route('rental-requests.return-document', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-download me-1"></i> Return Document
                    @if ($returnDocumentsCompleted ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Payment --}}
            <li class="nav-item">
                <a class="nav-link"
                    href="{{ route('rental-requests.payment', [$contract->id, $contract->customer->id]) }}">
                    <i class="bx bx-money me-1"></i> Payment
                    @if ($paymentsExist ?? false)
                        ✔
                    @endif
                </a>
            </li>

            {{-- Status / History --}}
            <li class="nav-item">
                <a class="nav-link" href="{{ route('rental-requests.history', $contract->id) }}">
                    <i class="bx bx-history me-1"></i> Status & History
                </a>
            </li>
        @endif
    </ul>




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
                            <!-- Delivery Date -->
                            <div class="input-group">
                                <span class="input-group-text">Delivery Date</span>
                                <span class="form-control">{{ $contract->pickup_date }}</span>
                            </div>
                            <!-- Pickup Location -->
                            <div class="input-group">
                                <span class="input-group-text">Pickup Location</span>
                                <span class="form-control">{{ $contract->pickup_location }}</span>
                            </div>

                            <!-- Return Date -->
                            <div class="input-group">
                                <span class="input-group-text">Return Date</span>
                                <span class="form-control">{{ $contract->return_date }}</span>
                            </div>

                            <!-- Return Location -->
                            <div class="input-group">
                                <span class="input-group-text">Return Location</span>
                                <span class="form-control">{{ $contract->return_location }}</span>
                            </div>

                            <!-- Total Price -->
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <span class="form-control">{{ $contract->total_price }}</span>
                            </div>

                            <!-- Status -->
                            <div class="input-group">
                                <span class="input-group-text">Status</span>
                                <span class="form-control">{{ $contract->statusLabel() }}</span>
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
                        <div class="input-group">
                            <span class="input-group-text">Agent</span>
                            <span class="form-control">{{ $contract->user->name }}</span>
                        </div>

                        @if ($contract->customerDocument)
                            <div class="input-group">
                                <span class="input-group-text">Document Uploaded</span>
                                <span class="form-control text-success">Yes</span>
                            </div>
                        @else
                            <div class="input-group">
                                <span class="input-group-text">Document Uploaded</span>
                                <span class="form-control text-danger">No</span>
                            </div>
                        @endif


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
