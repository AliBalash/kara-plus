<div class="container-xl py-4 leads-page">
    @php
        $statusClasses = [
            'new' => 'bg-label-primary',
            'follow_up' => 'bg-label-warning',
            'interested' => 'bg-label-success',
            'not_interested' => 'bg-label-secondary',
            'unreachable' => 'bg-label-danger',
            'converted' => 'bg-label-info',
        ];

        $priorityClasses = [
            'low' => 'bg-label-secondary',
            'normal' => 'bg-label-primary',
            'high' => 'bg-label-warning',
            'urgent' => 'bg-label-danger',
        ];
    @endphp

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Operations /</span> Leads</h4>
            <div class="d-flex flex-wrap gap-2">
                <span class="badge bg-label-secondary">{{ $summary['total'] }} total</span>
                <span class="badge bg-label-primary">{{ $summary['open'] }} open</span>
                <span class="badge bg-label-warning">{{ $summary['due'] }} due</span>
                <span class="badge bg-label-success">{{ $summary['converted'] }} converted</span>
            </div>
        </div>

        <div class="lead-toolbar d-flex flex-column flex-md-row gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search leads"
                    wire:model.live.debounce.400ms="searchInput" autocomplete="off" enterkeyhint="search">
            </div>
            <select class="form-select" wire:model.live="statusFilter" aria-label="Status filter">
                <option value="">All status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select" wire:model.live="priorityFilter" aria-label="Priority filter">
                <option value="">All priority</option>
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm lead-form-card">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            @if ($convertingId)
                                Convert Lead
                            @elseif ($editingId)
                                Edit Lead
                            @else
                                New Lead
                            @endif
                        </h5>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" type="button" wire:click="resetForm">
                        <i class="bx bx-reset"></i>
                    </button>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="lead_first_name">First name</label>
                            <input type="text" id="lead_first_name" class="form-control" wire:model.defer="first_name"
                                autocomplete="given-name">
                            <x-panel.form-error-highlighter field="first_name" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_last_name">Last name</label>
                            <input type="text" id="lead_last_name" class="form-control" wire:model.defer="last_name"
                                autocomplete="family-name">
                            <x-panel.form-error-highlighter field="last_name" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead_phone">Phone <span class="text-danger">*</span></label>
                            <input type="tel" id="lead_phone" class="form-control" wire:model.defer="phone"
                                autocomplete="tel">
                            <x-panel.form-error-highlighter field="phone" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_messenger_phone">Messenger phone</label>
                            <input type="tel" id="lead_messenger_phone" class="form-control"
                                wire:model.defer="messenger_phone" autocomplete="tel">
                            <x-panel.form-error-highlighter field="messenger_phone" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lead_email">Email</label>
                            <input type="email" id="lead_email" class="form-control" wire:model.defer="email"
                                autocomplete="email">
                            <x-panel.form-error-highlighter field="email" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead_source">Contact channel</label>
                            <input type="text" id="lead_source" class="form-control" wire:model.defer="source"
                                placeholder="Call, WhatsApp, Instagram">
                            <x-panel.form-error-highlighter field="source" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_discovery_source">How found us</label>
                            <input type="text" id="lead_discovery_source" class="form-control"
                                wire:model.defer="discovery_source" placeholder="Google, referral, social media">
                            <x-panel.form-error-highlighter field="discovery_source" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lead_requested_vehicle">Requested vehicle</label>
                            <input type="text" id="lead_requested_vehicle" class="form-control"
                                wire:model.defer="requested_vehicle">
                            <x-panel.form-error-highlighter field="requested_vehicle" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead_pickup_date">Pickup date</label>
                            <input type="date" id="lead_pickup_date" class="form-control" wire:model.defer="pickup_date">
                            <x-panel.form-error-highlighter field="pickup_date" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_return_date">Return date</label>
                            <input type="date" id="lead_return_date" class="form-control" wire:model.defer="return_date">
                            <x-panel.form-error-highlighter field="return_date" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead_priority">Priority <span class="text-danger">*</span></label>
                            <select id="lead_priority" class="form-select" wire:model.defer="priority">
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-panel.form-error-highlighter field="priority" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_status">Status <span class="text-danger">*</span></label>
                            <select id="lead_status" class="form-select" wire:model.defer="status">
                                @foreach ($statuses as $value => $label)
                                    @if ($value === 'converted' && $status !== 'converted')
                                        @continue
                                    @endif
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-panel.form-error-highlighter field="status" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lead_assigned_to">Owner</label>
                            <select id="lead_assigned_to" class="form-select" wire:model.defer="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->fullName() }}</option>
                                @endforeach
                            </select>
                            <x-panel.form-error-highlighter field="assigned_to" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead_next_follow_up_at">Next follow-up</label>
                            <input type="datetime-local" id="lead_next_follow_up_at" class="form-control"
                                wire:model.defer="next_follow_up_at">
                            <x-panel.form-error-highlighter field="next_follow_up_at" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lead_last_contacted_at">Last contact</label>
                            <input type="datetime-local" id="lead_last_contacted_at" class="form-control"
                                wire:model.defer="last_contacted_at">
                            <x-panel.form-error-highlighter field="last_contacted_at" />
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lead_notes">Notes</label>
                            <textarea id="lead_notes" class="form-control" rows="4" wire:model.defer="notes"></textarea>
                            <x-panel.form-error-highlighter field="notes" />
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-0 d-flex flex-column gap-2">
                    @if ($convertingId)
                        <button class="btn btn-success w-100" type="button" wire:click="convertToCustomer"
                            wire:loading.attr="disabled" wire:target="convertToCustomer">
                            <i class="bx bx-user-check me-1"></i>
                            <span wire:loading.remove wire:target="convertToCustomer">Create Customer</span>
                            <span wire:loading wire:target="convertToCustomer">Creating...</span>
                        </button>
                        <button class="btn btn-outline-secondary w-100" type="button" wire:click="edit({{ $convertingId }})">
                            <i class="bx bx-edit me-1"></i> Back to Edit
                        </button>
                    @else
                        <button class="btn btn-primary w-100" type="button" wire:click="save" wire:loading.attr="disabled"
                            wire:target="save">
                            <i class="bx bx-save me-1"></i>
                            <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Lead' : 'Save Lead' }}</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Lead Pipeline</h5>
                    <span class="badge bg-label-info">{{ $leads->total() }} result</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Lead</th>
                                <th>Request</th>
                                <th>Source</th>
                                <th>Follow-up</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leads as $lead)
                                <tr wire:key="lead-{{ $lead->id }}">
                                    <td>
                                        <div class="fw-semibold">{{ $lead->displayName() }}</div>
                                        <div class="text-muted small">{{ $lead->phone }}</div>
                                        @if ($lead->email)
                                            <div class="text-muted small">{{ $lead->email }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $lead->requested_vehicle ?: 'No vehicle' }}</div>
                                        <div class="text-muted small">
                                            @if ($lead->pickup_date)
                                                {{ $lead->pickup_date->format('Y-m-d') }}
                                            @else
                                                No date
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $lead->source ?: 'No channel' }}</div>
                                        <div class="text-muted small">{{ $lead->discovery_source ?: 'No discovery source' }}</div>
                                    </td>
                                    <td>
                                        @if ($lead->next_follow_up_at)
                                            <span class="badge {{ $lead->isFollowUpDue() ? 'bg-label-danger' : 'bg-label-secondary' }}">
                                                {{ $lead->next_follow_up_at->format('Y-m-d H:i') }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Not set</span>
                                        @endif
                                    </td>
                                    <td>{{ $lead->assignedUser?->fullName() ?? 'Unassigned' }}</td>
                                    <td>
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="badge {{ $statusClasses[$lead->status] ?? 'bg-label-secondary' }}">
                                                {{ $statuses[$lead->status] ?? ucfirst($lead->status) }}
                                            </span>
                                            <span class="badge {{ $priorityClasses[$lead->priority] ?? 'bg-label-secondary' }}">
                                                {{ $priorities[$lead->priority] ?? ucfirst($lead->priority) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            @if ($lead->customer_id)
                                                <a class="btn btn-sm btn-outline-info"
                                                    href="{{ route('customer.detail', $lead->customer_id) }}"
                                                    title="Customer">
                                                    <i class="bx bx-user"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-sm btn-outline-success" type="button"
                                                    wire:click="prepareConversion({{ $lead->id }})" title="Convert">
                                                    <i class="bx bx-user-check"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-primary" type="button"
                                                wire:click="edit({{ $lead->id }})" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">No leads found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white border-0">
                    {{ $leads->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .leads-page .lead-toolbar {
            min-width: min(100%, 720px);
        }

        .leads-page .lead-toolbar .input-group {
            min-width: 260px;
        }

        .leads-page .lead-toolbar .form-select {
            min-width: 150px;
        }

        .leads-page .lead-form-card {
            position: sticky;
            top: 1rem;
        }

        .leads-page .table td,
        .leads-page .table th {
            white-space: nowrap;
        }

        .leads-page textarea {
            resize: vertical;
        }

        @media (max-width: 1199.98px) {
            .leads-page .lead-form-card {
                position: static;
            }
        }

        @media (max-width: 767.98px) {
            .leads-page .lead-toolbar,
            .leads-page .lead-toolbar .input-group,
            .leads-page .lead-toolbar .form-select {
                min-width: 100%;
            }
        }
    </style>
@endpush
