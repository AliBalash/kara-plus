<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold">Fleet Price Management</h4>
    <p class="text-muted">Select exact vehicles, preview changes, then confirm. Filters never determine which vehicles are updated.</p>
    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @if ($feedback)<div class="alert alert-success" role="status">{{ $feedback }}</div>@endif
    <form wire:submit="applyFilters" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label" for="fleet-search">Search</label><input id="fleet-search" class="form-control" wire:model="filters.search" placeholder="Brand, model, plate or vehicle ID"></div>
            <div class="col-6 col-md-2"><label class="form-label" for="fleet-brand">Brand</label><select id="fleet-brand" class="form-select" wire:model="filters.brand"><option value="">All brands</option>@foreach ($models->pluck('brand')->unique() as $brand)<option>{{ $brand }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label" for="fleet-model">Model</label><select id="fleet-model" class="form-select" wire:model="filters.model"><option value="">All models</option>@foreach ($models as $model)<option value="{{ $model->id }}">{{ $model->brand }} {{ $model->model }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label" for="fleet-year">Year</label><input id="fleet-year" type="number" class="form-control" wire:model="filters.year" placeholder="Any"></div>
            <div class="col-6 col-md-2"><label class="form-label" for="fleet-owner">Ownership</label><select id="fleet-owner" class="form-select" wire:model="filters.ownership"><option value="">All ownership</option>@foreach (['company'=>'Our Fleet','golden_key'=>'Golden Key','liverpool'=>'Liverpool','safe_drive'=>'Safe Drive','other'=>'Other Fleet'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12 col-md-3"><label class="form-label" for="fleet-status">Operational status</label><select id="fleet-status" class="form-select" wire:model="filters.status"><option value="">All statuses</option>@foreach (\App\Models\Car::operationalStatusLabels() as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            @foreach (['min'=>'Minimum short rental (AED)', 'max'=>'Maximum short rental (AED)'] as $key=>$label)
                <div class="col-6 col-md-3"><label class="form-label" for="fleet-{{ $key }}">{{ $label }}</label><input id="fleet-{{ $key }}" type="number" min="0" step="0.01" class="form-control" wire:model="filters.{{ $key }}" placeholder="No limit"></div>
            @endforeach
            <div class="col-12 col-md-3 d-flex gap-2"><button class="btn btn-primary">Apply Filters</button><button type="button" class="btn btn-outline-secondary" wire:click="resetFilters">Reset</button></div>
        </div>
    </form>
    <section class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <strong>{{ count($selectedIds) }} selected</strong>
                <button class="btn btn-sm btn-outline-primary" wire:click="selectPage">Add current page ({{ $cars->count() }})</button>
                <button class="btn btn-sm btn-outline-primary" wire:click="selectAllFiltered">Add all matching ({{ $cars->total() }})</button>
                <button class="btn btn-sm btn-outline-secondary" wire:click="clearSelection">Clear selection</button>
                <label class="ms-auto"><input type="checkbox" wire:model.live="showInsurance"> Show insurance columns</label>
            </div>
            <p class="small text-muted mb-0">{{ $selectionContext }}. {{ count(array_intersect($selectedIds, $cars->pluck('id')->all())) }} selected on this page. {{ $cars->total() }} matching vehicles.</p>
            @if ($selectedIds)<details class="mt-2 small"><summary>Review selected vehicle IDs</summary><div class="text-break">{{ implode(', ', $selectedIds) }}</div></details>@endif
        </div>
        <div class="table-responsive"><table class="table table-sm align-middle">
            <thead><tr><th>Select</th><th>Vehicle</th><th>Year / plate</th><th>Ownership / status</th><th>Rental S / M / L (AED)</th>@if ($showInsurance)<th>LDW S / M / L</th><th>SCDW S / M / L</th>@endif</tr></thead>
            <tbody>@forelse ($cars as $car)<tr wire:key="fleet-{{ $car->id }}">
                <td><input type="checkbox" aria-label="Select vehicle {{ $car->id }}" @checked(in_array($car->id, $selectedIds, true)) wire:click="toggleSelection({{ $car->id }})"></td>
                <td>#{{ $car->id }}<br>{{ $car->carModel?->brand }} {{ $car->carModel?->model }}</td><td>{{ $car->manufacturing_year }}<br>{{ $car->plate_number }}</td>
                <td>{{ $car->ownershipLabel() }}<br>{{ $car->operationalStatusLabel() }}</td>
                @foreach (['price_per_day', ...($showInsurance ? ['ldw_price','scdw_price'] : [])] as $prefix)
                    <td class="text-nowrap">@foreach (['short','mid','long'] as $tier){{ $car->{$prefix.'_'.$tier} ?? '—' }}{{ !$loop->last ? ' / ' : '' }}@endforeach</td>
                @endforeach
            </tr>@empty<tr><td colspan="7" class="text-center py-4">No matching vehicles. Existing selections are preserved.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="card-body">{{ $cars->links() }}</div>
    </section>
    <div class="card card-body mb-3">
        <label for="pricing-mode" class="form-label">Operation mode</label>
        <select id="pricing-mode" class="form-select" wire:model.live="mode"><option value="base">Base Prices</option><option value="date">Date-Based Prices</option></select>
    </div>
    <form wire:submit="preview" class="card card-body mb-3">
        <h5>{{ $mode === 'date' ? 'Date-Based Prices' : 'Base Prices' }}</h5>
        @if ($mode === 'date')
            <p class="text-muted small">Dubai calendar dates, inclusive. Applies only to selected rental tiers on the exact selected vehicles. Insurance and base tariffs stay unchanged. Percentage rules use the regular tariff in effect when a new quote is calculated. Existing contracts are preserved.</p>
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-4"><label class="form-label" for="event-name">Event name</label><input id="event-name" class="form-control" wire:model="window.name" maxlength="120"></div>
                <div class="col-12 col-md-3"><label class="form-label" for="event-start">Start date (Dubai)</label><input id="event-start" type="date" class="form-control" wire:model="window.starts_on" min="{{ now('Asia/Dubai')->toDateString() }}"></div>
                <div class="col-12 col-md-3"><label class="form-label" for="event-end">End date (Dubai)</label><input id="event-end" type="date" class="form-control" wire:model="window.ends_on"></div>
                <div class="col-12 col-md-2 d-flex align-items-end"><label><input type="checkbox" wire:model="window.active"> Active on apply</label></div>
            </div>
        @else
            <p class="text-muted small">Enable only the fields you intend to change. Blank legacy tariffs need an exact value before a relative adjustment. Disabled fields stay untouched.</p>
        @endif
        <div class="row g-3">
            @foreach ($fields as $field=>$label)
                @if ($mode === 'date' && !str_starts_with($field, 'price_per_day_')) @continue @endif
                <div class="col-12 col-md-6 col-xl-4" wire:key="operation-{{ $field }}">
                    <label class="form-label"><input type="checkbox" wire:model.live="operations.{{ $field }}.enabled"> {{ $label }}</label>
                    <div class="d-flex gap-2"><select aria-label="{{ $label }} method" class="form-select" wire:model="operations.{{ $field }}.method" @disabled(!$operations[$field]['enabled'])>@foreach ($methods as $method=>$methodLabel)<option value="{{ $method }}">{{ $methodLabel }}</option>@endforeach</select>
                    <input aria-label="{{ $label }} value" class="form-control" style="min-width:0" type="number" min="0" step="0.01" wire:model="operations.{{ $field }}.value" @disabled(!$operations[$field]['enabled']) placeholder="Enter value"></div>
                    @error('operations.'.$field.'.value')<div class="small text-danger">{{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>
        <button class="btn btn-primary mt-3 align-self-start" wire:loading.attr="disabled">Preview changes</button>
    </form>
    @if ($priceWindows)
        <section class="card card-body mb-3">
            <h5>Date pricing events</h5>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Event</th><th>Dubai dates</th><th>Exact vehicles / tiers</th><th>State</th><th>Action</th></tr></thead><tbody>
            @forelse ($priceWindows as $event)
                <tr><td>{{ $event->name }}</td><td class="text-nowrap">{{ $event->starts_on->format('Y-m-d') }} – {{ $event->ends_on->format('Y-m-d') }}</td><td><details><summary>{{ $event->rates->pluck('car_id')->unique()->count() }} vehicles · {{ $event->rates->pluck('tier')->unique()->implode(', ') }}</summary><span class="text-break">IDs: {{ $event->rates->pluck('car_id')->unique()->implode(', ') }}</span></details></td><td>{{ $event->active ? 'Active' : 'Inactive' }}</td><td><button class="btn btn-sm btn-outline-primary" wire:click="previewEventState({{ $event->id }}, {{ $event->active ? 'false' : 'true' }})">Preview {{ $event->active ? 'deactivation' : 'activation' }}</button></td></tr>
            @empty<tr><td colspan="5">No date pricing events yet.</td></tr>@endforelse
            </tbody></table></div>{{ $priceWindows->links() }}
        </section>
    @endif
    @if ($preview)
        <section class="card card-body">
            <h5>Preview · {{ $preview->mode === 'base' ? 'Base Prices' : 'Date-Based Prices' }} · {{ count($preview->payload['ids']) }} vehicles · {{ count($preview->payload['operations']) }} enabled fields</h5>
            @if (isset($preview->payload['window']))
                @if ($preview->mode === 'date_state')<p>Event state: {{ $preview->payload['previous_active'] ? 'Active' : 'Inactive' }} → {{ $preview->payload['window']['active'] ? 'Active' : 'Inactive' }}</p>@endif
                <div class="alert alert-info">{{ $preview->payload['window']['name'] }} · {{ $preview->payload['window']['starts_on'] }} – {{ $preview->payload['window']['ends_on'] }} (Asia/Dubai) · {{ $preview->payload['window']['active'] ? 'Active' : 'Inactive' }}<br>Exact vehicle IDs: {{ implode(', ', $preview->payload['ids']) }}. Base prices will not be changed.</div>
            @endif
            <p>{{ collect($preview->payload['rows'])->sum(fn ($row) => count(array_filter(array_keys($row['after']), fn ($field) => $row['after'][$field] !== $row['before'][$field]))) }} changed price cells across {{ collect($preview->payload['rows'])->where('changed', true)->count() }} vehicles.</p>
            <p>{{ collect($preview->payload['rows'])->where('changed', false)->count() }} unchanged rows. Highlighted rows contain an adjustment of at least 50% or 1,000 AED. Preview expires in 30 minutes.</p>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Vehicle</th>@foreach ($preview->payload['operations'] as $field=>$operation)<th>{{ $fields[$field] }}<br>Before → after (AED)</th>@endforeach</tr></thead>
            <tbody>@foreach ($preview->payload['rows'] as $row)<tr class="{{ $row['large'] ? 'table-warning' : '' }}"><td>#{{ $row['id'] }} {{ $row['label'] }} @unless ($row['changed'])<span class="badge bg-label-secondary">Unchanged</span>@endunless</td>@foreach ($row['before'] as $field=>$before)<td class="text-nowrap">{{ $before ?? '—' }} → <strong>{{ $row['after'][$field] }}</strong></td>@endforeach</tr>@endforeach</tbody></table></div>
            @unless ($preview->applied_at)
                <label class="mt-2"><input type="checkbox" wire:model.live="confirmed"> I reviewed the selected vehicle IDs and exact changes above.</label>
                <button class="btn btn-danger mt-3 align-self-start" wire:click="apply" wire:loading.attr="disabled" @disabled(!$confirmed)>Confirm and apply atomically</button>
            @endunless
        </section>
    @endif
</div>
