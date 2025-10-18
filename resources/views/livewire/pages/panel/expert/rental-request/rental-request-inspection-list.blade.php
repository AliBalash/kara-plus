<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Inspection Approvals</h4>

    <div class="row p-3 g-3">
        <div class="col-md-3">
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

        <div class="col-md-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="delivery">Awaiting Delivery</option>
                <option value="agreement_inspection">Agreement Inspection</option>
                <option value="awaiting_return">Awaiting Return</option>
                <option value="returned">Returned</option>
                <option value="complete">Complete</option>
                <option value="cancelled">Cancelled</option>
                <option value="all">All Statuses</option>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="userFilter">
                <option value="">All Contracts</option>
                <option value="assigned">Assigned</option>
                <option value="unassigned">Unassigned</option>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="tarsStatus">
                <option value="all">All TARS States</option>
                <option value="pending">Pending TARS</option>
                <option value="approved">Approved TARS</option>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" wire:model.live="kardoStatus">
                <option value="all">All KARDO States</option>
                <option value="pending">Pending KARDO</option>
                <option value="approved">Approved KARDO</option>
                <option value="not_required">KARDO Not Required</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup From" wire:model.live="pickupFrom">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Pickup To" wire:model.live="pickupTo">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return From" wire:model.live="returnFrom">
        </div>
        <div class="col-md-2">
            <input type="date" class="form-control" placeholder="Return To" wire:model.live="returnTo">
        </div>

        <div class="col-md-2">
            <button class="btn btn-secondary w-100" type="button" wire:click="clearFilters">Clear Filters</button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" wire:key="success-message">
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
                    <th wire:click="sortBy('id')" role="button" class="sortable">
                        #
                        <i
                            class="bx {{ $sortField === 'id' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Customer</th>
                    <th>Car Type</th>
                    <th wire:click="sortBy('pickup_date')" role="button" class="sortable">
                        Pickup Date
                        <i
                            class="bx {{ $sortField === 'pickup_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th wire:click="sortBy('return_date')" role="button" class="sortable">
                        Return Date
                        <i
                            class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Approvals</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @foreach ($contracts as $contract)
                    @php
                        $pickupDocument = $contract->pickupDocument;
                        $tarsDone = $pickupDocument && $pickupDocument->tars_approved_at;
                        $kardoDone = $contract->kardo_required
                            ? ($pickupDocument && $pickupDocument->kardo_approved_at)
                            : false;

                        $tarsBadge = $tarsDone ? 'bg-success' : 'bg-warning text-dark';
                        $kardoBadge = match (true) {
                            ! $contract->kardo_required => 'bg-secondary',
                            $kardoDone => 'bg-success',
                            default => 'bg-warning text-dark',
                        };
                    @endphp
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>{{ optional($contract->customer)->fullName() ?? '—' }}</td>
                        <td>{{ optional($contract->car)->fullName() ?? 'Vehicle N/A' }}</td>
                        <td>{{ $contract->pickup_date?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $contract->return_date?->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $tarsBadge }}">TARS: {{ $tarsDone ? 'Approved' : 'Pending' }}</span>
                            <span class="badge {{ $kardoBadge }} mt-1">KARDO:
                                {{ $contract->kardo_required ? ($kardoDone ? 'Approved' : 'Pending') : 'Not Required' }}</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Approval links">
                                <a href="{{ route('rental-requests.tars-approval', $contract->id) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-check-shield me-1"></i>TARS
                                </a>
                                <a href="{{ route('rental-requests.kardo-approval', $contract->id) }}"
                                    class="btn btn-outline-info btn-sm {{ $contract->kardo_required ? '' : 'disabled' }}"
                                    @if (! $contract->kardo_required) aria-disabled="true" tabindex="-1" @endif>
                                    <i class="bx bx-layer me-1"></i>KARDO
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $contracts->links() }}
    </div>
</div>

@once
    @push('styles')
        <style>
            th.sortable {
                cursor: pointer;
                user-select: none;
            }

            th.sortable i {
                margin-left: 0.35rem;
            }

            th.sortable:hover {
                color: #007bff;
            }
        </style>
    @endpush
@endonce
