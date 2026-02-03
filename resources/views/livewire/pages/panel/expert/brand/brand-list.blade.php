<div class="card">
    <h5 class="card-header">Brands</h5>

    <div class="row" style="padding: 0.5rem 1.5rem">
        <div class="col-md-6">
            <form class="input-group" wire:submit.prevent="applySearch">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search..." aria-label="Search"
                    wire:model.defer="searchInput">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                    wire:target="applySearch">
                    <span wire:loading.remove wire:target="applySearch">Search</span>
                    <span wire:loading wire:target="applySearch">...</span>
                </button>
            </form>
        </div>
        
    </div>
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
                                    <img src="{{ asset('storage/' . ltrim($brand->brand_icon, '/')) }}" alt="Brand Icon"
                                        class="rounded-circle me-2" width="30" height="30" loading="lazy"
                                        decoding="async" fetchpriority="low">
                                @endif
                                {{ $brand->brand }}
                            </div>
                        </td>
                        <td>{{ $brand->model }}</td>
                        <td>{{ ucfirst($brand->gearbox_type ?? 'N/A') }}</td>
                        <td>{{ $brand->cars_count }}</td>
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
