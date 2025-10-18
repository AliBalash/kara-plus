<div class="card">
    <h5 class="card-header">Cars</h5>

    <div class="row p-3">
        <!-- Search -->
        <div class="col-md-3 mb-2">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search..."
                    wire:model.defer="searchInput">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>

        <!-- Brand Filter -->
        <div class="col-md-3 mb-2">
            <select class="form-select" wire:model.live="selectedBrand">
                <option value="">All Brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>

        <!-- Status Filter -->
        <div class="col-md-3 mb-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="reserved">Booking</option>
                <option value="under_maintenance">Under Maintenance</option>
            </select>
        </div>

        <!-- Only Booking -->
        <div class="col-md-3 mb-2 d-flex align-items-center">
            <input type="checkbox" class="form-check-input me-1" id="onlyReserved" wire:model.live="onlyReserved">
            <label class="form-check-label" for="onlyReserved">Show only booking</label>
        </div>

        <!-- Date Filters -->
        <div class="col-md-3 mb-2">
            <input type="date" class="form-control" wire:model.live="pickupFrom" placeholder="Pickup From">
        </div>
        <div class="col-md-3 mb-2">
            <input type="date" class="form-control" wire:model.live="pickupTo" placeholder="Pickup To">
        </div>

        <!-- Clear All Filters -->
        <div class="col-md-3 mb-2">
            <button class="btn btn-secondary w-100" wire:click="clearFilters">Clear All Filters</button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th wire:click="sortBy('id')">#</th>
                    <th>Car Model</th>
                    <th wire:click="sortBy('color')">Color</th>
                    <th wire:click="sortBy('status')">Status</th>
                    <th wire:click="sortBy('pickup_date')">Pickup Date</th>
                    <th wire:click="sortBy('return_date')">Return Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cars as $car)
                    <tr>
                        <td>{{ $car->id }}</td>
                        <td>{{ $car->fullname() }}</td>
                        <td>{{ $car->color ?? 'N/A' }}</td>
                        <td>
                            <span
                                class="badge 
                                @switch($car->status)
                                    @case('available') bg-success @break
                                    @case('reserved') bg-warning @break
                                    @case('under_maintenance') bg-danger @break
                                    @default bg-secondary
                                @endswitch">
                                {{ ucfirst($car->status) }}
                            </span>
                        </td>
                        <td>{{ $car->currentContract ? $car->currentContract->pickup_date->format('d M Y') : '-' }}</td>
                        <td>{{ $car->currentContract ? $car->currentContract->return_date->format('d M Y') : '-' }}</td>
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
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No cars found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $cars->links() }}</div>
</div>
