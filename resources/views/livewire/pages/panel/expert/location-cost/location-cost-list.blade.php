<div class="container-xl py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Settings /</span> Location Costs</h4>
            <p class="text-muted mb-0">Manage delivery and return fees by location.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search locations" wire:model.live.debounce.400ms="searchInput"
                    autocomplete="off" enterkeyhint="search">
            </div>
            <button class="btn btn-secondary" type="button" wire:click="resetForm">
                <i class="bx bx-reset me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">{{ $editingId ? 'Edit Location Cost' : 'Add Location Cost' }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="location">Location</label>
                        <input type="text" id="location" class="form-control" placeholder="Country / City / Area" wire:model.defer="location">
                        <x-panel.form-error-highlighter field="location" />
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="under_3_fee">Under 3 Days Fee</label>
                            <input type="number" step="0.01" id="under_3_fee" class="form-control" wire:model.defer="under_3_fee">
                            <x-panel.form-error-highlighter field="under_3_fee" />
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="over_3_fee">3 Days &amp; Above Fee</label>
                            <input type="number" step="0.01" id="over_3_fee" class="form-control" wire:model.defer="over_3_fee">
                            <x-panel.form-error-highlighter field="over_3_fee" />
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model.defer="is_active">
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" type="button" wire:click="resetForm">
                        <i class="bx bx-x me-1"></i> Cancel
                    </button>
                    <button class="btn btn-primary" type="button" wire:click="save">
                        <i class="bx bx-save me-1"></i> {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Locations</h5>
                    <span class="badge bg-label-info">{{ $locationCosts->total() }} total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Location</th>
                                <th>Under 3 Days</th>
                                <th>3+ Days</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locationCosts as $cost)
                                <tr>
                                    <td>{{ $cost->id }}</td>
                                    <td class="fw-semibold">{{ $cost->location }}</td>
                                    <td>AED {{ number_format($cost->under_3_fee, 2) }}</td>
                                    <td>AED {{ number_format($cost->over_3_fee, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $cost->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ $cost->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" type="button" wire:click="edit({{ $cost->id }})">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" type="button" wire:click="toggleActive({{ $cost->id }})">
                                                <i class="bx {{ $cost->is_active ? 'bx-low-vision' : 'bx-show' }}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" wire:click="delete({{ $cost->id }})">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No locations found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0">
                    {{ $locationCosts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
