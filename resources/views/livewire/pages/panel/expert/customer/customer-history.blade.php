<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Customer /</span> History</h4>

    <div>
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills flex-column flex-md-row mb-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.detail') ? 'active' : '' }}"
                            href="{{ route('customer.detail', $customerId) }}">
                            <i class="bx bx-file me-1"></i> Contract Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.history') ? 'active' : '' }}"
                            href="{{ route('customer.history', $customerId) }}"><i class="bx bx-history me-1"></i> History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('customer.debt') ? 'active' : '' }}"
                            href="{{ route('customer.debt', $customerId) }}">
                            <i class="bx bx-trending-down me-1"></i> Debt
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bx bx-paperclip me-1"></i> Attachments</a>
                    </li>
                </ul>
                <div class="card mb-4">
                    <h5 class="card-header">Customer History</h5>
                    <div class="card-body">


                        <div class="table-responsive text-nowrap">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th> <!-- افزودن ستون ID قرارداد -->
                                        <th>Customer</th>
                                        <th>Car</th>
                                        <th>Pickup Date</th>
                                        <th>Return Date</th>
                                        <th>Agent Sale</th>
                                        <th>Expert</th>
                                        <th>Actions</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                    @foreach ($contracts as $contract)
                                        <tr>
                                            <td>{{ $contract->id }}</td> <!-- نمایش ID قرارداد -->
                                            <td>{{ $contract->customer->fullName() }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span>{{ $contract->car->fullName() }}</span>
                                                    <x-car-ownership-badge :car="$contract->car" />
                                                </div>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($contract->pickup_date)->format('d M Y') }}
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($contract->return_date)->format('d M Y') }}
                                            </td>
                                            <td>{{ $contract->agent_sale }}</td>

                                            <td>
                                                @php
                                                    $showAgent = $contract->user && !in_array($contract->current_status, ['pending', 'assigned']);
                                                    $badgeClass = $showAgent ? 'badge bg-primary' : 'badge bg-warning text-dark';
                                                    $badgeTitle = $showAgent ? 'Assigned agent' : 'Submitted by';
                                                    $badgeLabel = $showAgent ? $contract->user->shortName() : ($contract->submitted_by_name ?? 'Website');
                                                @endphp
                                                <span class="{{ $badgeClass }}" title="{{ $badgeTitle }}">{{ $badgeLabel }}</span>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                                        data-bs-toggle="dropdown">
                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        @if (is_null($contract->user_id))
                                                            <a href="#" class="dropdown-item text-danger">
                                                                <i class="bx bx-error-alt me-1 text-danger"></i> Without
                                                                Expert
                                                            </a>
                                                        @endif
                                                        <a class="dropdown-item"
                                                            href="{{ route('rental-requests.details', $contract->id) }}">
                                                            <i class="bx bx-info-circle me-1"></i> Details
                                                        </a>
                                                        <a class="dropdown-item"
                                                            href="{{ route('rental-requests.edit', $contract->id) }}">
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
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
