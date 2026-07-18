<div>
    <form wire:submit.prevent="save">
        <div class="row g-4">
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
                                    <option value="{{ $carOption->id }}">
                                        {{ $carOption->fullname() }} ({{ $carOption->ownershipLabel() }})
                                    </option>
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
                                <input type="text" class="form-control"
                                    value="{{ $car->fullname() }} ({{ $car->ownershipLabel() }})" disabled>
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
                                <input type="text" class="form-control" value="{{ $car->operationalStatusLabel() }}" disabled>
                            </div>
                            @if ($car->operationalStatus() === \App\Models\Car::STATUS_UNAVAILABLE && $car->unavailabilityReasonLabel())
                                <div class="form-text mt-1">{{ $car->unavailabilityReasonLabel() }}</div>
                            @endif
                            @if ($car->operationalStatusContextNote())
                                <div class="form-text text-warning">{{ $car->operationalStatusContextNote() }}</div>
                            @endif
                            <x-car-need-action-alert :car="$car" compact show-edit-link class="mt-2" />
                        @endif
                    </div>
                </div>
            </div>

            <!-- Insurance Information -->
            <div class="col-md-6">
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <div class="d-flex flex-column gap-1">
                            <h5 class="mb-0">Insurance Information</h5>
                            <p class="text-muted small mb-0">This date powers the dashboard monthly renewal and urgent expiry report.</p>
                        </div>
                    </div>
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
                                wire:model="validDays" min="0">
                            @error('validDays')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="input-group mt-2">
                            <span class="input-group-text">Status</span>
                            <select class="form-control @error('status') is-invalid @enderror" wire:model="status">
                                <option value="">Select Status</option>
                                <option value="done">Done</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <div class="d-flex flex-column gap-1">
                            <h5 class="mb-0">Passing Information</h5>
                            <p class="text-muted small mb-0">Passing report is calculated from the recorded date plus its validity days.</p>
                        </div>
                    </div>
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="input-group">
                            <span class="input-group-text">Passing Date</span>
                            <input type="date" class="form-control @error('passingDate') is-invalid @enderror"
                                wire:model="passingDate">
                            @error('passingDate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Valid Days</span>
                            <input type="number" class="form-control @error('passingValidDays') is-invalid @enderror"
                                wire:model="passingValidDays" min="0">
                            @error('passingValidDays')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="input-group mt-2">
                            <span class="input-group-text">Status</span>
                            <select class="form-control @error('passingStatus') is-invalid @enderror" wire:model="passingStatus">
                                <option value="">Select Status</option>
                                <option value="done">Done</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                            @error('passingStatus')
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
