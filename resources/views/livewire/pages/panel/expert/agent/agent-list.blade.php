<div class="container-xl py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1"><span class="text-muted fw-light">Settings /</span> Sales Agents</h4>
            <p class="text-muted mb-0">Manage sales agents, activation status, and assignments.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="input-group">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="search" class="form-control" placeholder="Search agents" wire:model.live.debounce.400ms="searchInput"
                    autocomplete="off" enterkeyhint="search">
            </div>
            <button class="btn btn-secondary" type="button" wire:click="resetForm">
                <i class="bx bx-reset me-1"></i> Reset
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">{{ $editingId ? 'Edit Agent' : 'Add Agent' }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" for="agent_name">Agent name</label>
                        <input type="text" id="agent_name" class="form-control" placeholder="Full name or company"
                            wire:model.defer="name">
                        <x-panel.form-error-highlighter field="name" />
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="agent_active" wire:model.defer="is_active">
                        <label class="form-check-label" for="agent_active">Active</label>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 d-flex justify-content-between">
                    <button class="btn btn-outline-secondary" type="button" wire:click="resetForm">
                        <i class="bx bx-x me-1"></i> Cancel
                    </button>
                    <button class="btn btn-primary" type="button" wire:click="save">
                        <i class="bx bx-save me-1"></i> {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Agents</h5>
                    <span class="badge bg-label-info">{{ $agents->total() }} total</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Agent</th>
                                <th>Contracts</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td>{{ $agent->id }}</td>
                                    <td class="fw-semibold">{{ $agent->name }}</td>
                                    <td>
                                        <span class="badge bg-label-secondary">{{ $agent->contracts_count }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $agent->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ $agent->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" type="button" wire:click="edit({{ $agent->id }})">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" type="button" wire:click="toggleActive({{ $agent->id }})">
                                                <i class="bx {{ $agent->is_active ? 'bx-low-vision' : 'bx-show' }}"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" type="button" wire:click="delete({{ $agent->id }})">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No agents found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0">
                    {{ $agents->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
