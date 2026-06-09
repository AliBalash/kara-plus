<div class="card">
    <h5 class="card-header">Insurances</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vehicle</th>
                    <th>Insurance Expiry</th>
                    <th>Insurance Status</th>
                    <th>Passing Due</th>
                    <th>Passing Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody class="table-border-bottom-0">
                @forelse ($insurancelist as $insurance)
                    @php
                        $passingDueDate = $insurance->car?->passing_date
                            ? \Carbon\Carbon::parse($insurance->car->passing_date)->addDays((int) ($insurance->car->passing_valid_for_days ?? 0))
                            : null;
                    @endphp
                    <tr>
                        <td>{{ $insurance->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span>{{ $insurance->car->fullname() }}</span>
                                <x-car-ownership-badge :car="$insurance->car" />
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $insurance->expiry_date }}</div>
                            <div class="text-muted small">{{ $insurance->valid_days ?? 0 }} day(s)</div>
                        </td>
                        <td>
                            <span
                                class="badge 
                                @switch($insurance->status)
                                    @case('done') bg-label-success @break
                                    @case('pending') bg-label-warning @break
                                    @case('failed') bg-label-danger @break
                                    @default bg-label-secondary
                                @endswitch">
                                {{ ucfirst($insurance->status) }}
                            </span>
                        </td>
                        <td>
                            @if ($passingDueDate)
                                <div class="fw-semibold">{{ $passingDueDate->format('Y-m-d') }}</div>
                                <div class="text-muted small">{{ $insurance->car->passing_valid_for_days ?? 0 }} day(s)</div>
                            @else
                                <span class="text-muted">Not set</span>
                            @endif
                        </td>
                        <td>
                            <span
                                class="badge
                                @switch($insurance->car->passing_status)
                                    @case('done') bg-label-success @break
                                    @case('pending') bg-label-warning @break
                                    @case('failed') bg-label-danger @break
                                    @default bg-label-secondary
                                @endswitch">
                                {{ ucfirst($insurance->car->passing_status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="{{ route('insurance.form', $insurance->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);"
                                        wire:click.prevent="deleteInsurance({{ $insurance->id }})">
                                        <i class="bx bx-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No insurance records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $insurancelist->links() }}
    </div>
</div>
