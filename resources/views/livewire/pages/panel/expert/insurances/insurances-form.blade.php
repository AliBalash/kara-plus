<div>
    <form wire:submit.prevent="save">
        <div class="row">
            <!-- Car Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Car Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <!-- Select Car -->
                        <div class="input-group">
                            <span class="input-group-text">Select Car</span>
                            <select class="form-control @error('carId') is-invalid @enderror" wire:model.live="carId">
                                <option value="">Choose a car</option>
                                @foreach ($cars as $carOption)
                                    <option value="{{ $carOption->id }}">{{ $carOption->fullname() }}</option>
                                @endforeach
                            </select>
                            @error('carId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if ($car)
                            <!-- Car Details -->
                            <div class="input-group mt-2">
                                <span class="input-group-text">Car Name</span>
                                <input type="text" class="form-control" value="{{ $car->fullname() }}" disabled>
                            </div>
                            <div class="input-group mt-2">
                                <span class="input-group-text">Manufacturing Year</span>
                                <input type="number" class="form-control" value="{{ $car->manufacturing_year }}"
                                    disabled>
                            </div>
                            <div class="input-group mt-2">
                                <span class="input-group-text">Car Color</span>
                                <input type="text" class="form-control" value="{{ $car->color }}" disabled>
                            </div>
                            <div class="input-group mt-2">
                                <span class="input-group-text">Car Status</span>
                                <input type="text" class="form-control" value="{{ $car->status }}" disabled>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Insurance Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Insurance Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <!-- Expiry Date -->
                        <div class="input-group">
                            <span class="input-group-text">Expiry Date</span>
                            <input type="date" class="form-control @error('expiryDate') is-invalid @enderror"
                                wire:model="expiryDate">
                            @error('expiryDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Valid Days -->
                        <div class="input-group mt-2">
                            <span class="input-group-text">Valid Days</span>
                            <input type="number" class="form-control @error('validDays') is-invalid @enderror"
                                wire:model="validDays">
                            @error('validDays')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
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

                        <!-- Insurance Company -->
                        <div class="input-group mt-2">
                            <span class="input-group-text">Insurance Company</span>
                            <input type="text" class="form-control @error('insuranceCompany') is-invalid @enderror"
                                wire:model="insuranceCompany">
                            @error('insuranceCompany')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                {{ $insuranceId ? 'Update Insurance' : 'Add Insurance' }}
            </button>
        </div>
    </form>

</div>
