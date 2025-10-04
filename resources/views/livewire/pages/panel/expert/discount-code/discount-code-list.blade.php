<div class="card">
    <h5 class="card-header">Active Discount Codes</h5>

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

    <!-- Displaying success message after actions -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Code</th>
                    <th>Discount (%)</th>
                    <th>Registery Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0" wire:poll.10s>
                @forelse ($discountCodes as $discountCode)
                    <tr wire:key={{ $discountCode->id }}>
                        <td>{{ $discountCode->id }}</td>
                        <td>{{ $discountCode->name }}</td>
                        <td>{{ $discountCode->phone }}</td>
                        <td>{{ $discountCode->code }}</td>
                        <td>{{ $discountCode->discount_percentage }}%</td>
                        <td>{{ \Carbon\Carbon::parse($discountCode->registery_at)->format('Y-m-d H:i') }}</td>
                        <td>
                            @if ($discountCode->contacted)
                                <span class="badge bg-success">Contacted</span>
                            @else
                                <span class="badge bg-danger">Not Contacted</span>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    @if (!$discountCode->contacted)
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="markAsContacted({{ $discountCode->id }})">
                                            <i class="bx bx-check-circle me-1"></i> Mark as Contacted
                                        </a>
                                    @else
                                        <a class="dropdown-item" href="javascript:void(0);"
                                            wire:click.prevent="markAsNotContacted({{ $discountCode->id }})">
                                            <i class="bx bx-x-circle me-1"></i> Mark as Not Contacted
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No active discount codes found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $discountCodes->links() }}
    </div>
</div>
