<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 fw-bold py-3 mb-4">
        <h4 class="mb-0">
            <span class="text-muted fw-light">Contract /</span>
            {{ $isTarsList ? 'TARS Inspection Approvals' : 'KARDO Inspection Approvals' }}
        </h4>

        <div class="btn-group" role="group" aria-label="Approval type">
            <a href="{{ route('rental-requests.tars-inspection-list') }}"
                class="btn btn-sm {{ $isTarsList ? 'btn-primary' : 'btn-outline-primary' }}">TARS</a>
            @if ($hasPendingKardoContracts || ! $isTarsList)
                <a href="{{ route('rental-requests.kardo-inspection-list') }}"
                    class="btn btn-sm {{ $isTarsList ? 'btn-outline-primary' : 'btn-primary' }}">KARDO</a>
            @endif
        </div>
    </div>

    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="inspectionSearch">Search</label>
                    <span class="filter-hint">Customer, plate, ID</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="inspectionSearch" type="search" class="form-control" placeholder="Start typing…"
                        wire:model.defer="searchInput">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"
                        wire:target="applySearch">
                        <span wire:loading.remove wire:target="applySearch">Apply</span>
                        <span wire:loading wire:target="applySearch">…</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionStatus">Status</label>
                <select id="inspectionStatus" class="form-select" wire:model.live="statusFilter">
                    <option value="delivery">Awaiting Delivery</option>
                    <option value="inspection">Inspection</option>
                    <option value="agreement_inspection">Agreement Inspection</option>
                    <option value="all">All Statuses</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionAssignment">Assignment</label>
                <select id="inspectionAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        @if ($isTarsList)
            <div class="col-md-2">
                <div class="filter-field">
                    <label class="filter-label" for="inspectionTars">TARS State</label>
                    <select id="inspectionTars" class="form-select" wire:model.live="tarsStatus">
                        <option value="pending">Pending TARS</option>
                        <option value="approved">Approved TARS</option>
                        <option value="all">All TARS States</option>
                    </select>
                </div>
            </div>
        @else
            <div class="col-md-2">
                <div class="filter-field">
                    <label class="filter-label" for="inspectionKardo">KARDO State</label>
                    <select id="inspectionKardo" class="form-select" wire:model.live="kardoStatus">
                        <option value="pending">Pending KARDO</option>
                        <option value="approved">Approved KARDO</option>
                        <option value="all">All KARDO States</option>
                    </select>
                </div>
            </div>
        @endif

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionPickupFrom">Pickup From</label>
                <input id="inspectionPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionPickupTo">Pickup To</label>
                <input id="inspectionPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionReturnFrom">Return From</label>
                <input id="inspectionReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="inspectionReturnTo">Return To</label>
                <input id="inspectionReturnTo" type="date" class="form-control" wire:model.live="returnTo">
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field h-100">
                <label class="filter-label">Reset</label>
                <button class="btn btn-outline-secondary" type="button" wire:click="clearFilters">Clear Filters</button>
            </div>
        </div>
    </div>

    @include('livewire.pages.panel.expert.rental-request.partials.filter-styles')

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

    @if (! $isTarsList && ! $hasPendingKardoContracts)
        <div class="alert alert-info mx-3" role="alert">
            All KARDO approvals are complete for the current inspection stage.
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
                        <i class="bx {{ $sortField === 'return_date' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Actions</th>
                    <th>Status</th>
                    <th>Sales Agent</th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                    <th>{{ $isTarsList ? 'TARS Status' : 'KARDO Status' }}</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($contracts as $contract)
                    @php
                        $pickupDocument = $contract->pickupDocument;
                        $tarsDone = $pickupDocument && $pickupDocument->tars_approved_at;
                        $kardoDone = $contract->kardo_required
                            ? ($pickupDocument && $pickupDocument->kardo_approved_at)
                            : false;

                        $tarsBadge = $tarsDone ? 'bg-success' : 'bg-warning text-dark';
                        $kardoBadge = $kardoDone ? 'bg-success' : 'bg-warning text-dark';
                    @endphp
                    <tr>
                        <td>{{ $contract->id }}</td>
                        <td>
                            <div>{{ optional($contract->customer)->fullName() ?? '—' }}</div>
                            <div class="text-muted small">{{ optional($contract->customer)->phone ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ optional($contract->car)->fullName() ?? 'Vehicle N/A' }}</span>
                                <x-car-ownership-badge :car="$contract->car" />
                            </div>
                        </td>
                        <td>{{ $contract->pickup_date?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $contract->return_date?->format('d M Y H:i') ?? '—' }}</td>
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
                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>
                        <td>
                            <span class="badge {{ $contract->agent_sale ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $contract->agent_sale ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $contract->submitted_by_name ?? 'Website' }}</span>
                        </td>
                        <td>
                            @if ($contract->user)
                                <span class="badge bg-success">{{ $contract->user->shortName() }}</span>
                            @else
                                <span class="badge bg-label-secondary text-muted">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            @if ($isTarsList)
                                <span class="badge {{ $tarsBadge }}">TARS: {{ $tarsDone ? 'Approved' : 'Pending' }}</span>
                                @if ($contract->kardo_required)
                                    <span class="badge {{ $kardoBadge }} mt-1">KARDO:
                                        {{ $kardoDone ? 'Approved' : 'Pending' }}</span>
                                @endif
                            @else
                                <span class="badge {{ $kardoBadge }}">KARDO: {{ $kardoDone ? 'Approved' : 'Pending' }}</span>
                                <span class="badge {{ $tarsBadge }} mt-1">TARS: {{ $tarsDone ? 'Approved' : 'Pending' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No contracts found.</td>
                    </tr>
                @endforelse
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
