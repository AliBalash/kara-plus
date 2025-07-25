<div class="card">
    <h5 class="card-header">Brands</h5>

    <div class="row" style="padding: 0.5rem 1.5rem">
        <div class="col-md-6">
            <div class="nav-item d-flex align-items-center">
                <i class="bx bx-search fs-4 lh-0"></i>
                <input type="text" class="form-control border-0 shadow-none" placeholder="Search..."
                    aria-label="Search..." wire:model.live.debounce.1000ms="search">
            </div>
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
                    <th>Brand</th>
                    <th>Model</th>
                    <th>Gearbox Type</th>
                    <th>Number of Cars</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($brands as $brand)
                    <tr>
                        <td>{{ $brand->id }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if ($brand->brand_icon)
                                    <img src="{{ asset('storage/' . $brand->brand_icon) }}" alt="Brand Icon"
                                        class="rounded-circle me-2" width="30" height="30">
                                @endif
                                {{ $brand->brand }}
                            </div>
                        </td>
                        <td>{{ $brand->model }}</td>
                        <td>{{ ucfirst($brand->gearbox_type ?? 'N/A') }}</td>
                        <td>{{ $brand->cars()->count() }}</td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <!-- گزینه Details -->
                                    <a class="dropdown-item" href="{{ route('brand.detail', $brand->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a>

                                    <!-- گزینه Edit -->
                                    <a class="dropdown-item" href="{{ route('brand.form', $brand->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>

                                    <!-- گزینه Delete -->
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="deleteBrand({{ $brand->id }})">
                                            <i class="bx bx-trash me-1"></i> Delete
                                        </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">No car models found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $brands->links() }}
    </div>
</div>
