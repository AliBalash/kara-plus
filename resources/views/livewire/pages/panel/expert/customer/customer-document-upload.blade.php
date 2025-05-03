<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Customer /</span> Document
    </h4>

    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if (session()->has('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <x-detail-rental-request-tabs :contract-id="$contractId" />



    <div class="card">
        <h5 class="card-header">Upload Customer Documents</h5>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocument" enctype="multipart/form-data">
                <div class="row">
                    <!-- Visa -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Visa</label>
                        @if (!empty($existingFiles['visa']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['visa'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2"
                                    onclick="confirmDeletion('visa')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="visa">
                        <div wire:loading wire:target="visa" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('visa')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Passport -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Passport</label>
                        @if (!empty($existingFiles['passport']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['passport'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2"
                                    onclick="confirmDeletion('passport')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="passport">
                        <div wire:loading wire:target="passport" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('passport')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Driving License -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Driving License</label>
                        @if (!empty($existingFiles['license']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['license'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2"
                                    onclick="confirmDeletion('license')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="license">
                        <div wire:loading wire:target="license" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('license')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Flight Ticket -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Flight Ticket</label>
                        @if (!empty($existingFiles['ticket']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['ticket'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2"
                                    onclick="confirmDeletion('ticket')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="ticket">
                        <div wire:loading wire:target="ticket" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('ticket')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3">Upload Documents</button>
            </form>

        </div>
    </div>
</div>
@push('scripts')
    <script>
        function confirmDeletion(fileType) {
            if (confirm('Are you sure you want to delete this file?')) {
                // If confirmed, call the Livewire removeFile function
                @this.call('removeFile', fileType);
            }
        }
    </script>
@endpush
