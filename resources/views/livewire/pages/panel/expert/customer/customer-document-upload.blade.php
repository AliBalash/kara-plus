<div class="container">
    @include('components.panel.form-error-highlighter')

    <h4 class="fw-bold py-3 mb-4">
        <span class="text-muted fw-light">Customer /</span> Document
    </h4>
    <x-detail-rental-request-tabs :contract-id="$contractId" />



    <div class="card">
        <h5 class="card-header">Upload Customer Documents</h5>
        <div class="card-body">
            <form wire:submit.prevent="uploadDocument" enctype="multipart/form-data">
                <div class="alert alert-info d-none d-flex align-items-center" data-upload-guard role="status">
                    <i class="bi bi-cloud-arrow-up me-2"></i>
                    <span>Please wait while the current files finish uploading before selecting another document.</span>
                </div>
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
                        <input type="file" class="form-control" wire:model="visa" multiple
                            wire:loading.attr="disabled" wire:target="visa,passport,license,ticket,uploadDocument"
                            data-upload-field="visa">
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div class="mt-2 d-none" data-progress-container="visa" wire:loading.class.remove="d-none"
                            wire:target="visa">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">
                                    0%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span data-progress-status="visa">Ready for upload</span>
                                <span data-progress-percent="visa">0%</span>
                            </div>
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
                        <input type="file" class="form-control" wire:model="passport" multiple
                            wire:loading.attr="disabled" wire:target="visa,passport,license,ticket,uploadDocument"
                            data-upload-field="passport">
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div class="mt-2 d-none" data-progress-container="passport" wire:loading.class.remove="d-none"
                            wire:target="passport">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">
                                    0%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span data-progress-status="passport">Ready for upload</span>
                                <span data-progress-percent="passport">0%</span>
                            </div>
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
                        <input type="file" class="form-control" wire:model="license" multiple
                            wire:loading.attr="disabled" wire:target="visa,passport,license,ticket,uploadDocument"
                            data-upload-field="license">
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div class="mt-2 d-none" data-progress-container="license" wire:loading.class.remove="d-none"
                            wire:target="license">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">
                                    0%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span data-progress-status="license">Ready for upload</span>
                                <span data-progress-percent="license">0%</span>
                            </div>
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

                        <input type="file" class="form-control" wire:model="ticket" multiple
                            wire:loading.attr="disabled" wire:target="visa,passport,license,ticket,uploadDocument"
                            data-upload-field="ticket">
                        <small class="text-muted">You can upload up to 3 files total for this document.</small>
                        <div class="mt-2 d-none" data-progress-container="ticket" wire:loading.class.remove="d-none"
                            wire:target="ticket">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0%;">
                                    0%
                                </div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mt-1">
                                <span data-progress-status="ticket">Ready for upload</span>
                                <span data-progress-percent="ticket">0%</span>
                            </div>
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
                    <div class="col-md-6 mb-3" data-validation-field="hotel_name">
                        <label class="form-label fw-semibold" for="hotelNameInput">
                            Hotel Name <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                        </label>
                        <input id="hotelNameInput" type="text" class="form-control" wire:model="hotel_name"
                            placeholder="Enter hotel name" aria-required="true">
                        @error('hotel_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Hotel Address -->
                    <div class="col-md-6 mb-3" data-validation-field="hotel_address">
                        <label class="form-label fw-semibold" for="hotelAddressInput">
                            Hotel Address <span class="badge bg-danger-subtle text-danger ms-2">Required</span>
                        </label>
                        <input id="hotelAddressInput" type="text" class="form-control" wire:model="hotel_address"
                            placeholder="Enter hotel address" aria-required="true">
                        @error('hotel_address')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <button type="submit" class="btn btn-primary mt-3" data-upload-submit
                    wire:loading.attr="disabled" wire:target="visa,passport,license,ticket,uploadDocument">Upload Documents</button>
            </form>

        </div>
    </div>
</div>
@push('scripts')
    <script>
        (() => {
            const resetTimers = {};
            let activeUploads = 0;

            const getUploadInputs = () => Array.from(document.querySelectorAll('[data-upload-field]'));

            const storeInitialDisabledState = (element) => {
                if (!element || element.dataset.uploadInitiallyDisabled !== undefined) {
                    return;
                }

                element.dataset.uploadInitiallyDisabled = element.disabled ? 'true' : 'false';
            };

            const setDisabledState = (element, shouldDisable) => {
                if (!element) {
                    return;
                }

                storeInitialDisabledState(element);

                if (shouldDisable) {
                    element.setAttribute('disabled', 'disabled');
                    return;
                }

                if ((element.dataset.uploadInitiallyDisabled ?? 'false') === 'true') {
                    element.setAttribute('disabled', 'disabled');
                } else {
                    element.removeAttribute('disabled');
                }
            };

            const toggleDisabledState = (shouldDisable) => {
                getUploadInputs().forEach((input) => {
                    setDisabledState(input, shouldDisable);
                });

                const guardAlert = document.querySelector('[data-upload-guard]');

                if (guardAlert) {
                    guardAlert.classList.toggle('d-none', !shouldDisable);
                }

                const submitButton = document.querySelector('[data-upload-submit]');
                setDisabledState(submitButton, shouldDisable);
            };

            const scheduleReset = (field, delay) => {
                if (resetTimers[field]) {
                    window.clearTimeout(resetTimers[field]);
                }

                resetTimers[field] = window.setTimeout(() => {
                    const container = document.querySelector(`[data-progress-container="${field}"]`);
                    const bar = container ? container.querySelector('.progress-bar') : null;
                    const status = document.querySelector(`[data-progress-status="${field}"]`);
                    const percent = document.querySelector(`[data-progress-percent="${field}"]`);

                    if (!container) {
                        delete resetTimers[field];
                        return;
                    }

                    container.classList.add('d-none');

                    if (status) {
                        status.textContent = 'Ready for upload';
                    }

                    if (percent) {
                        percent.textContent = '0%';
                    }

                    if (bar) {
                        bar.style.width = '0%';
                        bar.setAttribute('aria-valuenow', '0');
                        bar.textContent = '0%';
                    }

                    delete resetTimers[field];
                }, delay);
            };

            const bindUploadHandlers = () => {
                getUploadInputs().forEach((input) => {
                    if (input.dataset.uploadHandlerBound === 'true') {
                        return;
                    }

                    input.dataset.uploadHandlerBound = 'true';
                    storeInitialDisabledState(input);

                    const field = input.dataset.uploadField;

                    const findContainer = () => document.querySelector(`[data-progress-container="${field}"]`);
                    const findBar = () => {
                        const container = findContainer();
                        return container ? container.querySelector('.progress-bar') : null;
                    };
                    const findStatus = () => document.querySelector(`[data-progress-status="${field}"]`);
                    const findPercent = () => document.querySelector(`[data-progress-percent="${field}"]`);

                    input.addEventListener('livewire-upload-start', () => {
                        activeUploads += 1;

                        if (activeUploads === 1) {
                            toggleDisabledState(true);
                        }

                        if (resetTimers[field]) {
                            window.clearTimeout(resetTimers[field]);
                            delete resetTimers[field];
                        }

                        const container = findContainer();
                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (container) {
                            container.classList.remove('d-none');
                        }

                        if (status) {
                            status.textContent = 'Uploading...';
                        }

                        if (percent) {
                            percent.textContent = '0%';
                        }

                        if (bar) {
                            bar.style.width = '0%';
                            bar.setAttribute('aria-valuenow', '0');
                            bar.textContent = '0%';
                        }
                    });

                    input.addEventListener('livewire-upload-progress', (event) => {
                        const progress = event.detail.progress ?? 0;

                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (bar) {
                            bar.style.width = `${progress}%`;
                            bar.setAttribute('aria-valuenow', String(progress));
                            bar.textContent = `${progress}%`;
                        }

                        if (percent) {
                            percent.textContent = `${progress}%`;
                        }

                        if (status) {
                            status.textContent = progress >= 100 ? 'Finishing...' : 'Uploading...';
                        }
                    });

                    const finalizeUpload = (isError = false) => {
                        const bar = findBar();
                        const status = findStatus();
                        const percent = findPercent();

                        if (status) {
                            status.textContent = isError ? 'Upload failed' : 'Upload complete';
                        }

                        if (percent) {
                            percent.textContent = isError ? '0%' : '100%';
                        }

                        if (bar) {
                            const value = isError ? 0 : 100;
                            bar.style.width = `${value}%`;
                            bar.setAttribute('aria-valuenow', String(value));
                            bar.textContent = `${value}%`;
                        }

                        activeUploads = Math.max(activeUploads - 1, 0);

                        if (activeUploads === 0) {
                            toggleDisabledState(false);
                        }

                        scheduleReset(field, isError ? 2000 : 800);
                    };

                    input.addEventListener('livewire-upload-finish', () => finalizeUpload(false));
                    input.addEventListener('livewire-upload-error', () => finalizeUpload(true));
                    input.addEventListener('livewire-upload-cancel', () => finalizeUpload(true));
                });
            };

            document.addEventListener('DOMContentLoaded', bindUploadHandlers);
            document.addEventListener('livewire:load', bindUploadHandlers);
            document.addEventListener('livewire:update', bindUploadHandlers);
            document.addEventListener('livewire:navigated', bindUploadHandlers);

            if (document.readyState !== 'loading') {
                bindUploadHandlers();
            }

            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('message.processed', bindUploadHandlers);
            }

            const submitButton = document.querySelector('[data-upload-submit]');
            storeInitialDisabledState(submitButton);
        })();
    </script>
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
