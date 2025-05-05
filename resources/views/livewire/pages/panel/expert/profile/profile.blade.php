<div>
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Account Settings /</span> Profile
        </h4>

        <div class="card mb-4">
            <h5 class="card-header">Profile Details</h5>

            <div class="card-body">
                <div class="d-flex align-items-start align-items-sm-center gap-4">
                    <img src="{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : asset('assets/panel/assets/img/avatars/unknow.jpg') }}"
                        alt="User Avatar" class="d-block rounded" height="100" width="100" />



                    <div class="button-wrapper">
                        <input type="file" wire:model="new_avatar" id="upload" class="d-none">
                        <label for="upload" class="btn btn-primary me-2 mb-4">
                            <span class="d-none d-sm-block">Upload new photo</span>
                        </label>

                        {{-- Loading Indicator --}}
                        <div wire:loading wire:target="new_avatar" class="mt-2">
                            <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                                <span class="visually-hidden">Uploading Image...</span>
                            </div>
                        </div>


                        @if ($new_avatar)
                            <small>Preview:</small>
                            <img src="{{ $new_avatar->temporaryUrl() }}" class="rounded mt-2" width="100">
                        @endif

                        @error('new_avatar')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <hr class="my-0" />

            <div class="card-body">
                <form wire:submit.prevent="save">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input class="form-control" type="text" wire:model.defer="first_name" required />
                            @error('first_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input class="form-control" type="text" wire:model.defer="last_name" required />
                            @error('last_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input class="form-control" type="email" wire:model.defer="email" required />
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input class="form-control" type="text" wire:model.defer="phone" />
                            @error('phone')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="national_code" class="form-label">National Code</label>
                            <input class="form-control" type="text" wire:model.defer="national_code" />
                            @error('national_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="address" class="form-label">Address</label>
                            <input class="form-control" type="text" wire:model.defer="address" />
                            @error('address')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="last_login" class="form-label">Last Login</label>
                            <input class="form-control" type="text" value="{{ $last_login }}" disabled />
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary me-2">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success mt-3">
            {{ session('message') }}
        </div>
    @endif
</div>
