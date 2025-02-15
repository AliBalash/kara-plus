<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Contract /</span> Pickup Document
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


    <div class="card">
        <h5 class="card-header">Upload Pickup Documents</h5>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocuments">
                <div class="row">
                    <!-- Tars Contract -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tars Contract</label>
                        @if (!empty($existingFiles['tarsContract']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['tarsContract'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2" onclick="confirmDeletion('tars_contract')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="tarsContract">
                        <div wire:loading wire:target="tarsContract" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%;">Uploading...</div>
                        </div>
                        @error('tarsContract')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
            
                    <!-- Kardo Contract -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kardo Contract</label>
                        @if (!empty($existingFiles['kardoContract']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['kardoContract'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2" onclick="confirmDeletion('kardo_contract')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="kardoContract">
                        <div wire:loading wire:target="kardoContract" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%;">Uploading...</div>
                        </div>
                        @error('kardoContract')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
            
                    <!-- Factor Contract -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Factor Contract</label>
                        @if (!empty($existingFiles['factorContract']))
                            <div class="mb-2">
                                <img src="{{ $existingFiles['factorContract'] }}" class="img-thumbnail" width="150">
                                <button type="button" class="btn btn-warning mt-2" onclick="confirmDeletion('factor_contract')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="factorContract">
                        <div wire:loading wire:target="factorContract" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%;">Uploading...</div>
                        </div>
                        @error('factorContract')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
            
                    <!-- Car Video -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Car Video</label>
                        @if (!empty($existingFiles['carVideo']))
                            <div class="mb-2">
                                <video width="150" controls>
                                    <source src="{{ $existingFiles['carVideo'] }}" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                                <button type="button" class="btn btn-warning mt-2" onclick="confirmDeletion('car_video')">Remove</button>
                            </div>
                        @endif
                        <input type="file" class="form-control" wire:model="carVideo">
                        <div wire:loading wire:target="carVideo" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" style="width: 100%;">Uploading...</div>
                        </div>
                        @error('carVideo')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            
                <button type="submit" class="btn btn-primary mt-3">Upload Documents</button>
            </form>
            

        </div>
    </div>

    @push('scripts')
        <script>
            function confirmDeletion(fileType) {
                if (confirm('Are you sure you want to delete this file?')) {
                    @this.call('removeFile', fileType);
                }
            }
        </script>
    @endpush
</div>
