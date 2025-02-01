<div class="card">
    <h5 class="card-header">Cars</h5>

    <div class="row" style="padding: 0.5rem 1.5rem">
        <div class="col-md-6">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..."
                    aria-label="Search..." wire:model.live.debounce.1000ms="search">
            </div>
        </div>
        <div class="col-md-6">
            <select class="form-select" wire:model.live="selectedBrand">
                <option value="">All Brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand }}">{{ $brand }}</option>
                @endforeach
            </select>
        </div>
    </div>



    <!-- نمایش پیام‌ها -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Plate Number</th>
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Color</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">

                @forelse ($cars as $car)
                    <tr>
                        <td>{{ $car->id }}</td>
                        <td>{{ $car->plate_number }}</td>
                        <td>{{ $car->carModel->brand }}</td>
                        <td>{{ $car->carModel->model }}</td>
                        <td>{{ $car->color ?? 'N/A' }}</td>
                        <td>
                            <span
                                class="badge 
                                @switch($car->status)
                                    @case('available') bg-label-success @break
                                    @case('reserved') bg-label-warning @break
                                    @case('under_maintenance') bg-label-danger @break
                                    @default bg-label-secondary
                                @endswitch">
                                {{ ucfirst($car->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">

                                    <!-- گزینه Details -->
                                    <a class="dropdown-item" href="{{ route('car.detail', $car->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>

                                    <!-- گزینه Edit -->
                                    <a class="dropdown-item" href="{{ route('car.form', $car->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>

                                    <!-- گزینه Delete -->
                                    @if ($car->user_id === auth()->id())
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deletecar({{ $car->id }})">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </a>
                                    @endif
                                </div>
                            </div>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $cars->links() }}
    </div>
</div>
