<div>
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
                                <div class="mb-2">
                                    <img src="{{ asset('storage/' . ltrim($currentBrandIcon, '/')) }}" alt="Current Brand Icon"
                                        width="100" height="100" loading="lazy" decoding="async" fetchpriority="low">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger mb-2"
                                    wire:click="removeBrandIcon">Remove icon</button>
                            @endif
                            <input type="file" class="form-control" id="brandIcon"
                                wire:key="brand-icon-{{ $fileInputVersion }}" wire:model="brandIcon" accept="image/*">
                            @error('brandIcon')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="additionalImage" class="form-label">Additional Image</label>
                            <input type="file" class="form-control" id="additionalImage"
                                wire:key="brand-additional-image-{{ $fileInputVersion }}" wire:model="additionalImage"
                                accept="image/*">
                            <small class="form-text text-muted">Recommended size: 800x450 pixels, format: PNG</small>

                            @error('additionalImage')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                            <!-- Loading bar when image is uploading -->
                            <div wire:loading wire:target="additionalImage" class="progress mt-2" style="height: 40px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info fs-5 fw-bold text-center"
                                    style="width: 100%;">
                                    Uploading Image...
                                </div>
                            </div>
                        </div>

                            @if ($additionalImages)
                                <div class="mt-3">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('car_pics')->url($additionalImages->file_name) }}"
                                        alt="Additional Image" width="100" loading="lazy" decoding="async"
                                        fetchpriority="low">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                    wire:click="removeAdditionalImage">Remove image</button>
                            @else
                                <img src="{{ asset('assets/car-pics/car test.webp') }}" alt="Default Image" width="100"
                                    loading="lazy" decoding="async" fetchpriority="low">
                            @endif

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
