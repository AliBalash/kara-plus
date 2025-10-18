<div class="card">
    <h5 class="card-header">Insurances</h5>
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Car ID</th>
                    <th>Insurance Company</th>
                    <th>Expiry Date</th>
                    <th>Valid Days</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody class="table-border-bottom-0">
                @forelse ($insurancelist as $insurance)
                    <tr>
                        <td>{{ $insurance->id }}</td>
                        <td>{{ $insurance->car->fullname() }}</td>
                        <td>{{ $insurance->insurance_company }}</td>
                        <td>{{ $insurance->expiry_date }}</td>
                        <td>{{ $insurance->valid_days }}</td>
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
                            <div class="dropdown">
                                <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                    data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">


                                    <!-- گزینه Edit -->
                                    <a class="dropdown-item" href="{{ route('insurance.form', $insurance->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i> Edit
                                    </a>

                                    <!-- گزینه Delete -->
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
