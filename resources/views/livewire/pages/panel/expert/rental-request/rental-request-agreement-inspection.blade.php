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
    <form wire:submit.prevent="save">
        <div class="row">
            <!-- اطلاعات ماشین -->
            

            <!-- اطلاعات بیمه -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Insurance Information</h5>
                    <div class="card-body">
                        <div class="input-group">
                            <span class="input-group-text">Expiry Date</span>
                            <input type="date" class="form-control @error('expiryDate') is-invalid @enderror"
                                wire:model="expiryDate">
                            @error('expiryDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Valid Days</span>
                            <input type="number" class="form-control @error('validDays') is-invalid @enderror"
                                wire:model="validDays">
                            @error('validDays')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Status</span>
                            <select class="form-control @error('status') is-invalid @enderror" wire:model="status">
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="done">Done</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- اطلاعات اضافی -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Additional Information</h5>
                    <div class="card-body">
                        <div class="input-group">
                            <span class="input-group-text">Mileage (km)</span>
                            <input type="number" class="form-control @error('mileage') is-invalid @enderror"
                                wire:model="mileage">
                            @error('mileage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Pickup Date</span>
                            <input type="date" class="form-control @error('deliveryDate') is-invalid @enderror"
                                wire:model="deliveryDate">
                            @error('deliveryDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Delivery Time</span>
                            <input type="time" class="form-control @error('deliveryTime') is-invalid @enderror"
                                wire:model="deliveryTime">
                            @error('deliveryTime')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- دکمه ثبت -->
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                Add Insurance
            </button>
        </div>
    </form>
</div>