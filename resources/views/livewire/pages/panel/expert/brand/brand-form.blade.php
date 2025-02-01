<div>
    @if (session()->has('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="row">
            <!-- Car Model Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Car Model Information</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <!-- Brand -->
                        <div class="input-group">
                            <span class="input-group-text" id="brand-addon">Brand</span>
                            <input type="text" class="form-control @error('brand') is-invalid @enderror"
                                placeholder="Enter Brand" name="brand" wire:model.live="brand">
                            @error('brand')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Model -->
                        <div class="input-group">
                            <span class="input-group-text" id="model-addon">Model</span>
                            <input type="text" class="form-control @error('model') is-invalid @enderror"
                                placeholder="Enter Model" name="model" wire:model.live="model">
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Engine Capacity -->
                        <div class="input-group">
                            <span class="input-group-text" id="engine-capacity-addon">Engine Capacity (L)</span>
                            <input type="number" step="0.1"
                                class="form-control @error('engineCapacity') is-invalid @enderror"
                                placeholder="Enter Engine Capacity" name="engine_capacity"
                                wire:model.live="engineCapacity">
                            @error('engineCapacity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Details -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <h5 class="card-header">Additional Details</h5>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <!-- Brand Icon -->
                        <div class="mb-3">
                            <label for="brandIcon" class="form-label">Brand Icon</label>
                            @if ($currentBrandIcon)
                                <img src="{{ asset('storage/' . $currentBrandIcon) }}" alt="Current Brand Icon"
                                    width="100">
                            @endif
                            <input type="file" class="form-control" id="brandIcon" wire:model="brandIcon">
                            @error('brandIcon')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="additionalImage" class="form-label">Additional Image</label>
                            <input type="file" class="form-control" id="additionalImage" wire:model="additionalImage">
                            <small class="form-text text-muted">Recommended size: 800x450 pixels, format: PNG</small>
                            @error('additionalImage')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        

                        @if ($additionalImages)
                            <img src="{{ asset('assets/car-pics/' . $additionalImages->file_name) }}"
                                alt="Additional Image" width="100">
                        @else
                            <img src="{{ asset('assets/car-pics/car test.webp') }}" alt="Default Image" width="100">
                        @endif

                        <!-- Fuel Type -->
                        <div class="input-group">
                            <span class="input-group-text" id="fuel-type-addon">Fuel Type</span>
                            <select class="form-control @error('fuelType') is-invalid @enderror" name="fuel_type"
                                wire:model="fuelType">
                                <option value="">Select Fuel Type</option>
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="electric">Electric</option>
                            </select>
                            @error('fuelType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Gearbox Type -->
                        <div class="input-group">
                            <span class="input-group-text" id="gearbox-type-addon">Gearbox Type</span>
                            <select class="form-control @error('gearboxType') is-invalid @enderror" name="gearbox_type"
                                wire:model="gearboxType">
                                <option value="">Select Gearbox Type</option>
                                <option value="manual">Manual</option>
                                <option value="automatic">Automatic</option>
                            </select>
                            @error('gearboxType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Seating Capacity -->
                        <div class="input-group">
                            <span class="input-group-text" id="seating-capacity-addon">Seating Capacity</span>
                            <input type="number" class="form-control @error('seatingCapacity') is-invalid @enderror"
                                placeholder="Enter Seating Capacity" name="seating_capacity"
                                wire:model.live="seatingCapacity">
                            @error('seatingCapacity')
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
                {{ $brandId ? 'Update Car Model' : 'Add Car Model' }}
            </button>
        </div>
    </form>

</div>
