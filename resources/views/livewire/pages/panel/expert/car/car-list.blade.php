<div class="card">
    <h5 class="card-header">Cars</h5>

    <div class="p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small text-muted mb-1">Quick search</label>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input type="search" class="form-control" placeholder="Plate, brand, or model..."
                        wire:model.defer="searchInput">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                        wire:target="applySearch">
                        <span wire:loading.remove wire:target="applySearch">Search</span>
                        <span wire:loading wire:target="applySearch">...</span>
                    </button>
                </form>
            </div>

            <div class="col-lg-7">
                <div class="d-flex flex-column flex-md-row justify-content-md-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <form class="p-3 bg-light border-bottom" wire:submit.prevent="applyFilters">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
            <div>
                <h6 class="mb-1">Filter cars</h6>
                <p class="text-muted small mb-0">Choose your filters, then press Apply Filters.</p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="applyFilters">
                    <span wire:loading.remove wire:target="applyFilters">Apply Filters</span>
                    <span wire:loading wire:target="applyFilters">Applying...</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                    Reset
                </button>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-1">Brand</label>
                <select class="form-select" wire:model.defer="selectedBrand">
                    <option value="">All Brands</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand }}">{{ $brand }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 col-lg-3">
                <label class="form-label small text-muted mb-1">Status</label>
                <select class="form-select" wire:model.defer="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="pre_reserved">Upcoming Booking</option>
                    <option value="reserved">Active Booking</option>
                    <option value="unavailable">Unavailable</option>
                    <option value="sold">Sold</option>
                </select>
            </div>

            <div class="col-md-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Pickup From</label>
                <input type="date" class="form-control" wire:model.defer="pickupFrom" placeholder="Pickup From">
            </div>

            <div class="col-md-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Pickup To</label>
                <input type="date" class="form-control" wire:model.defer="pickupTo" placeholder="Pickup To">
            </div>

            <div class="col-md-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Daily Price From</label>
                <input type="number" min="0" step="0.01" class="form-control" wire:model.defer="dailyPriceMin"
                    placeholder="Min price">
            </div>

            <div class="col-md-6 col-lg-2">
                <label class="form-label small text-muted mb-1">Daily Price To</label>
                <input type="number" min="0" step="0.01" class="form-control" wire:model.defer="dailyPriceMax"
                    placeholder="Max price">
            </div>

            <div class="col-md-12 col-lg-2">
                <label class="form-label small text-muted mb-1">Booking Scope</label>
                <div class="border rounded px-3 py-2 bg-white d-flex align-items-center">
                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                        <input type="checkbox" class="form-check-input mt-0 float-none me-0" id="onlyReserved"
                            wire:model.defer="onlyReserved">
                        <label class="form-check-label mb-0" for="onlyReserved">Show only bookings</label>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th role="button" wire:click="sortBy('id')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>#</span>
                            <i class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                    <th>Car Model</th>
                    <th role="button" wire:click="sortBy('color')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>Color</span>
                            <i class="bx {{ $sortField === 'color' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                    <th role="button" wire:click="sortBy('price_per_day_short')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>Daily Price</span>
                            <i class="bx {{ $sortField === 'price_per_day_short' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                    <th>Actions</th>
                    <th role="button" wire:click="sortBy('status')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>Status</span>
                            <i class="bx {{ $sortField === 'status' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                    <th role="button" wire:click="sortBy('pickup_date')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>Pickup Date</span>
                            <i class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                    <th role="button" wire:click="sortBy('return_date')">
                        <span class="d-inline-flex align-items-center gap-1">
                            <span>Return Date</span>
                            <i class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down') : 'bx-sort-alt-2' }}"></i>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($cars as $car)
                    <tr>
                        <td>{{ $car->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ $car->fullname() }}</span>
                                <x-car-ownership-badge :car="$car" />
                            </div>
                        </td>
                        <td>{{ $car->color ?? 'N/A' }}</td>
                        <td>{{ number_format((float) $car->price_per_day_short, 2) }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn p-0 dropdown-toggle" data-bs-toggle="dropdown"><i
                                        class="bx bx-dots-vertical-rounded"></i></button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('car.edit', $car->id) }}"><i
                                            class="bx bx-edit-alt"></i> Edit</a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        onclick="if(confirm('Delete?')){@this.call('deletecar', {{ $car->id }})}"><i
                                            class="bx bx-trash"></i> Delete</a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $car->operationalStatusBadgeClass() }}">
                                {{ $car->operationalStatusLabel() }}
                            </span>
                            @if ($car->operationalStatus() === \App\Models\Car::STATUS_UNAVAILABLE && $car->unavailabilityReasonLabel())
                                <div class="small text-muted mt-1">
                                    {{ $car->unavailabilityReasonLabel() }}
                                </div>
                            @endif
                        </td>
                        <td>{{ optional(optional($car->currentContract)->pickup_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ optional(optional($car->currentContract)->return_date)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No cars found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $cars->links() }}</div>
</div>
