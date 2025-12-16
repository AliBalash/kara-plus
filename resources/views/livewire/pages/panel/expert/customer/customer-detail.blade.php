<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Customer /</span> Detail</h4>
    <div>
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.detail') ? 'active' : '' }}"
                            href="{{ route('customer.detail', $customer['id']) }}">
                            <i class="bx bx-file me-1"></i> Contract Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.history') ? 'active' : '' }}"
                            href="{{ route('customer.history', $customer['id']) }}">
                            <i class="bx bx-history me-1"></i> History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.debt') ? 'active' : '' }}"
                            href="{{ route('customer.debt', $customer['id']) }}">
                            <i class="bx bx-trending-down me-1"></i> Debt</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bx bx-paperclip me-1"></i> Attachments</a>
                    </li>
                </ul>
                <div class="card mb-4">
                    <h5 class="card-header">Customer Information</h5>
                    <div class="card-body">
                        <form wire:submit.prevent="updateCustomer">
                            <div class="row">
                                <!-- First Name -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">First Name</label>
                                    <input type="text" class="form-control" wire:model.defer="customer.first_name">
                                    @error('customer.first_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Last Name -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" wire:model.defer="customer.last_name">
                                    @error('customer.last_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- National Code -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">National Code</label>
                                    <input type="text" class="form-control"
                                        wire:model.defer="customer.national_code">
                                    @error('customer.national_code')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                

                                <!-- Email -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" wire:model.defer="customer.email">
                                    @error('customer.email')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Phone -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" wire:model.defer="customer.phone">
                                    @error('customer.phone')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Address -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Address</label>
                                    <input type="text" class="form-control" wire:model.defer="customer.address">
                                    @error('customer.address')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Passport Number -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Passport Number</label>
                                    <input type="text" class="form-control"
                                        wire:model.defer="customer.passport_number">
                                    @error('customer.passport_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Passport Expiry Date -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Passport Expiry Date</label>
                                    <input type="date" class="form-control"
                                        wire:model.defer="customer.passport_expiry_date">
                                    @error('customer.passport_expiry_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Nationality -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" class="form-control" wire:model.defer="customer.nationality">
                                    @error('customer.nationality')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Birth Date -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" wire:model.defer="customer.birth_date"
                                        max="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                    @error('customer.birth_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- License Number -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">License Number</label>
                                    <input type="text" class="form-control"
                                        wire:model.defer="customer.license_number">
                                    @error('customer.license_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-control" wire:model.defer="customer.status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('customer.status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                

                                <!-- Registration Date -->
                                <div class="mb-3 col-md-6">
                                    <label class="form-label">Registration Date</label>
                                    <input type="date" class="form-control"
                                        wire:model.defer="customer.registration_date">
                                    @error('customer.registration_date')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
