<div class="card">
    <h4 class="card-header fw-bold py-3 mb-4"><span class="text-muted fw-light">Contract /</span> Reserve</h4>


    <!-- Filters -->
    <div class="row p-3 g-3">
        <div class="col-md-3">
            <div class="filter-field">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="filter-label" for="rentalListSearch">Search</label>
                    <span class="filter-hint">Name, plate or contract #</span>
                </div>
                <form class="input-group" wire:submit.prevent="applySearch">
                    <span class="input-group-text"><i class="bx bx-search"></i></span>
                    <input id="rentalListSearch" type="search" class="form-control" placeholder="Start typing…"
                        wire:model.defer="searchInput">
                    <button class="btn btn-primary" type="submit" wire:loading.attr="disabled"
                        wire:target="applySearch">
                        <span wire:loading.remove wire:target="applySearch">Apply</span>
                        <span wire:loading wire:target="applySearch">…</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListStatus">Status</label>
                <select id="rentalListStatus" class="form-select" wire:model.live="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="under_review">Under Review</option>
                    <option value="reserved">Booking</option>
                    <option value="delivery">Awaiting Delivery</option>
                    <option value="agreement_inspection">Agreement Inspection</option>
                    <option value="awaiting_return">Awaiting Return</option>
                    <option value="returned">Returned</option>
                    <option value="complete">Complete</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListAssignment">Assignment</label>
                <select id="rentalListAssignment" class="form-select" wire:model.live="userFilter">
                    <option value="">All Contracts</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListAgent">Sales Agent</label>
                <select id="rentalListAgent" class="form-select" wire:model.live="agentFilter">
                    <option value="">All Agents</option>
                    <option value="none">No Agent</option>
                    @foreach ($salesAgents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListKardo">KARDO</label>
                <select id="rentalListKardo" class="form-select" wire:model.live="kardoFilter">
                    <option value="">All Contracts</option>
                    <option value="required">Requires KARDO</option>
                    <option value="not_required">KARDO Not Required</option>
                </select>
            </div>
        </div>

        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListPickupFrom">Pickup From</label>
                <input id="rentalListPickupFrom" type="date" class="form-control" wire:model.live="pickupFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListPickupTo">Pickup To</label>
                <input id="rentalListPickupTo" type="date" class="form-control" wire:model.live="pickupTo">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListReturnFrom">Return From</label>
                <input id="rentalListReturnFrom" type="date" class="form-control" wire:model.live="returnFrom">
            </div>
        </div>
        <div class="col-md-2">
            <div class="filter-field">
                <label class="filter-label" for="rentalListReturnTo">Return To</label>
                <input id="rentalListReturnTo" type="date" class="form-control" wire:model.live="returnTo">
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
    <!-- Table -->
    <div class="table-responsive mt-3">
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
                    <th>Car</th>
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
                    <th>Actions</th>
                    <th>Status</th>
                    <th wire:click="sortBy('agent_name')" role="button" class="sortable">
                        Sales Agent
                        <i
                            class="bx {{ $sortField === 'agent_name' ? ($sortDirection === 'asc' ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt') : 'bx-sort-alt-2' }}">
                        </i>
                    </th>
                    <th>Submitted By</th>
                    <th>Assigned Expert</th>
                    <th>Documents</th>
                </tr>
            </thead>
            <tbody wire:poll.10s>
                @forelse($contracts as $contract)
                    <tr wire:key="contract-{{ $contract->id }}">
                        <td>{{ $contract->id }}</td>
                        <td>
                            <div>{{ $contract->customer->fullName() }}</div>
                            <div class="text-muted small">{{ $contract->customer->phone ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ $contract->car->fullName() }}</span>
                                <x-car-ownership-badge :car="$contract->car" />
                            </div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    @if (is_null($contract->user_id))
                                        <a wire:click.prevent="assignToMe({{ $contract->id }})" class="dropdown-item"
                                            href="javascript:void(0);">
                                            <i class="bx bx-user-check me-1"></i> Assign to Me
                                        </a>
                                    @endif
                                    {{-- <a class="dropdown-item"
                                        href="{{ route('rental-requests.details', $contract->id) }}">
                                        <i class="bx bx-info-circle me-1"></i> Details
                                    </a> --}}
                                    <a class="dropdown-item" href="{{ route('rental-requests.edit', $contract->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>
                                    @if ($contract->current_status !== 'cancelled')
                                        <a class="dropdown-item text-danger" href="javascript:void(0);"
                                            onclick="if(confirm('Are you sure you want to cancel this contract?')) { @this.cancelContract({{ $contract->id }}) }">
                                            <i class="bx bx-block me-1"></i> Cancel
                                        </a>
                                    @endif
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        wire:click.prevent="deleteContract({{ $contract->id }})">
                                        <i class="bx bx-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-status-badge :status="$contract->current_status" />
                        </td>
                        <td>
                            <span class="badge {{ $contract->agent?->name ? 'bg-label-primary text-primary' : 'bg-label-secondary text-muted' }}">
                                {{ $contract->agent?->name ?? '—' }}
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
                            @php
                                $pickupDocument = $contract->pickupDocument;
                                $agreementNumber = $pickupDocument?->agreement_number;
                                $kardoContract = $pickupDocument?->kardo_contract;
                            @endphp
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                @if ($contract->customerDocument()->exists())
                                    <span class="badge bg-warning doc-chip">📄 Customer</span>
                                @endif
                                @if ($contract->ReturnDocument()->exists())
                                    <span class="badge bg-success doc-chip">📄 Return</span>
                                @endif
                                @if ($contract->pickupDocument()->exists())
                                    <span class="badge bg-primary doc-chip">📄 Deliver</span>
                                @endif
                                @if ($contract->kardo_required && $kardoContract)
                                    <span class="badge bg-label-success text-success doc-chip">
                                        <i class="bx bx-layer"></i>
                                        KARDO uploaded
                                    </span>
                                @endif
                                @if ($agreementNumber)
                                    <span class="badge bg-label-info text-info doc-chip">
                                        <i class="bx bx-receipt"></i>
                                        Agreement #{{ \Illuminate\Support\Str::upper($agreementNumber) }}
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No contracts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
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

            .doc-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.6rem;
                border-radius: 999px;
                font-size: 0.78rem;
                font-weight: 600;
                letter-spacing: 0.01em;
                white-space: nowrap;
            }

            .doc-chip i {
                font-size: 0.9rem;
            }
        </style>
    @endpush
@endonce

</div>
