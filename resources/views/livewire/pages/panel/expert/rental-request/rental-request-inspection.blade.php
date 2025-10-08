<div class="card">
    <h5 class="card-header">Inspect Documents for Contract #{{ $contractId }}</h5>
    <div class="card-body">
        <div class="row">
            <!-- TARS Section -->
            <div class="col-md-6 mb-3">
                <label class="form-label">TARS Contract</label>
                @if (!empty($existingFiles['tarsContract']))
                    <img src="{{ $existingFiles['tarsContract'] }}" class="img-thumbnail" width="150">
                    @if (!$pickupDocument->tars_approved_at)
                        <button wire:click="approveTars" class="btn btn-success mt-2">Approve TARS</button>
                    @else
                        <span class="badge bg-success mt-2">Approved on {{ $pickupDocument->tars_approved_at }}</span>
                    @endif
                @else
                    <p class="text-danger">TARS not uploaded</p>
                @endif
            </div>

            <!-- KARDO Section -->
            <div class="col-md-6 mb-3">
                <label class="form-label">CARDO Contract</label>
                @if ($contract->kardo_required)
                    @if (!empty($existingFiles['kardoContract']))
                        <img src="{{ $existingFiles['kardoContract'] }}" class="img-thumbnail" width="150">
                        @if (!$pickupDocument->kardo_approved_at)
                            <button wire:click="approveKardo" class="btn btn-success mt-2">Approve CARDO</button>
                        @else
                            <span class="badge bg-success mt-2">Approved on
                                {{ $pickupDocument->kardo_approved_at }}</span>
                        @endif
                    @else
                        <p class="text-danger">CARDO not uploaded</p>
                    @endif
                @else
                    <p class="text-info">CARDO not required</p>
                @endif
            </div>

            <!-- Display other driver documents like videos, fuel, mileage (read-only, no approval buttons) -->
            <!-- Example: -->
            <div class="col-md-6 mb-3">
                <label class="form-label">Interior Car Video</label>
                @if (!empty($existingFiles['carVideoInside']))
                    <video width="150" controls>
                        <source src="{{ $existingFiles['carVideoInside'] }}" type="video/mp4">
                    </video>
                @endif
            </div>
            <!-- Add other documents as needed -->
        </div>

        <!-- Final Button with Confirmation -->
        @if ($pickupDocument->tars_approved_at && (!$contract->kardo_required || $pickupDocument->kardo_approved_at))
            <button wire:click="completeInspection"
                wire:confirm="Are you sure you want to complete the inspection and set the status to awaiting_return?"
                class="btn btn-danger mt-3">Complete Inspection and Set to Return</button>
        @else
            <p class="text-warning mt-3">Please complete all required approvals first.</p>
        @endif

        @if (session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif
    </div>
</div>
