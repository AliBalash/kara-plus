<div class="container">
    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Customer /</span> Document
    </h4>
    <x-detail-rental-request-tabs :contract-id="$contractId" />



    <div class="card">
        <h5 class="card-header">Upload Customer Documents</h5>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocument" enctype="multipart/form-data">
                <div class="row">


                    {{-- Modal Zoom Picture --}}
                    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="imageModalLabel">View Image</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <img id="modalImage" src="" class="img-fluid" alt="Preview">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Modal Zoom Picture --}}

                    <!-- Visa -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Visa (Front / Back / Additional)</label>
                        @if (!empty($existingFiles['visa']))
                            @foreach ($existingFiles['visa'] as $file)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $file['label'] }}</span>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="confirmDeletion('visa','{{ $file['raw_label'] }}')">Remove</button>
                                    </div>
                                    @if ($file['url'])
                                        @if ($file['is_pdf'])
                                            <iframe src="{{ $file['url'] }}" width="100%" height="320px"></iframe>
                                        @else
                                            <img src="{{ $file['url'] }}" class="img-thumbnail" width="150"
                                                onclick="openModal('{{ $file['url'] }}')">
                                        @endif
                                    @else
                                        <span class="text-muted">File missing from storage.</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        <input type="file" class="form-control" wire:model="visa" multiple>
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div wire:loading wire:target="visa" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('visa')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        @if ($errors->has('visa.*'))
                            @foreach ($errors->get('visa.*') as $messages)
                                @foreach ($messages as $message)
                                    <span class="text-danger d-block">{{ $message }}</span>
                                @endforeach
                            @endforeach
                        @endif
                    </div>

                    <!-- Passport -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Passport / ID Card (Front / Back / Additional)</label>
                        @if (!empty($existingFiles['passport']))
                            @foreach ($existingFiles['passport'] as $file)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $file['label'] }}</span>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="confirmDeletion('passport','{{ $file['raw_label'] }}')">Remove</button>
                                    </div>
                                    @if ($file['url'])
                                        @if ($file['is_pdf'])
                                            <iframe src="{{ $file['url'] }}" width="100%" height="320px"></iframe>
                                        @else
                                            <img src="{{ $file['url'] }}" class="img-thumbnail" width="150"
                                                onclick="openModal('{{ $file['url'] }}')">
                                        @endif
                                    @else
                                        <span class="text-muted">File missing from storage.</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        <input type="file" class="form-control" wire:model="passport" multiple>
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div wire:loading wire:target="passport" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('passport')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        @if ($errors->has('passport.*'))
                            @foreach ($errors->get('passport.*') as $messages)
                                @foreach ($messages as $message)
                                    <span class="text-danger d-block">{{ $message }}</span>
                                @endforeach
                            @endforeach
                        @endif
                    </div>

                    <!-- Driving License -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Driving License (Front / Back / Additional)</label>
                        @if (!empty($existingFiles['license']))
                            @foreach ($existingFiles['license'] as $file)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $file['label'] }}</span>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="confirmDeletion('license','{{ $file['raw_label'] }}')">Remove</button>
                                    </div>
                                    @if ($file['url'])
                                        @if ($file['is_pdf'])
                                            <iframe src="{{ $file['url'] }}" width="100%" height="320px"></iframe>
                                        @else
                                            <img src="{{ $file['url'] }}" class="img-thumbnail" width="150"
                                                onclick="openModal('{{ $file['url'] }}')">
                                        @endif
                                    @else
                                        <span class="text-muted">File missing from storage.</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                        <input type="file" class="form-control" wire:model="license" multiple>
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div wire:loading wire:target="license" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('license')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        @if ($errors->has('license.*'))
                            @foreach ($errors->get('license.*') as $messages)
                                @foreach ($messages as $message)
                                    <span class="text-danger d-block">{{ $message }}</span>
                                @endforeach
                            @endforeach
                        @endif
                    </div>

                    <!-- Flight Ticket -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Flight Ticket (Front / Back / Additional)</label>
                        @if (!empty($existingFiles['ticket']))
                            @foreach ($existingFiles['ticket'] as $file)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-semibold">{{ $file['label'] }}</span>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            onclick="confirmDeletion('ticket','{{ $file['raw_label'] }}')">Remove</button>
                                    </div>
                                    @if ($file['url'])
                                        @if ($file['is_pdf'])
                                            <iframe src="{{ $file['url'] }}" width="100%" height="320px"></iframe>
                                        @else
                                            <img src="{{ $file['url'] }}" class="img-thumbnail" width="150"
                                                onclick="openModal('{{ $file['url'] }}')">
                                        @endif
                                    @else
                                        <span class="text-muted">File missing from storage.</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        <input type="file" class="form-control" wire:model="ticket" multiple>
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div wire:loading wire:target="ticket" class="progress mt-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                style="width: 100%;">Uploading...</div>
                        </div>
                        @error('ticket')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        @if ($errors->has('ticket.*'))
                            @foreach ($errors->get('ticket.*') as $messages)
                                @foreach ($messages as $message)
                                    <span class="text-danger d-block">{{ $message }}</span>
                                @endforeach
                            @endforeach
                        @endif
                    </div>


                    <!-- Hotel Name -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hotel Name</label>
                        <input type="text" class="form-control" wire:model="hotel_name"
                            placeholder="Enter hotel name">
                        @error('hotel_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Hotel Address -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hotel Address</label>
                        <input type="text" class="form-control" wire:model="hotel_address"
                            placeholder="Enter hotel address">
                        @error('hotel_address')
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
        function confirmDeletion(fileType, label) {
            if (confirm('Are you sure you want to delete this file?')) {
                @this.call('removeFile', fileType, label);
            }
        }
    </script>

    <script>
        function openModal(imageUrl) {
            document.getElementById('modalImage').src = imageUrl;
            var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        }
    </script>
@endpush
