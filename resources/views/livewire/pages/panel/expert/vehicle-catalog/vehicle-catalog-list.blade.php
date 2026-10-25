<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1">Reservation Vehicle Catalogue</h5>
                    <p class="text-muted mb-0">Each model year is listed separately with its own variants and fleet prices.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary" wire:click="resetEditor">New item</button>
            </div>
            <div class="card-body border-top">
                <form wire:submit.prevent="save" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Vehicle code</label>
                        <input class="form-control @error('code') is-invalid @enderror" wire:model.defer="code" placeholder="KIA-PIC-22" style="text-transform: uppercase">
                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Website product slug</label>
                        <input class="form-control @error('websiteSlug') is-invalid @enderror" wire:model.defer="websiteSlug" placeholder="kia-picanto-2022">
                        @error('websiteSlug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Public display name</label>
                        <input class="form-control @error('displayName') is-invalid @enderror" wire:model.defer="displayName" placeholder="KIA Picanto">
                        @error('displayName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Marketing year</label>
                        <input type="number" class="form-control @error('manufacturingYear') is-invalid @enderror" wire:model.defer="manufacturingYear" placeholder="2022">
                        @error('manufacturingYear') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Display brand</label>
                        <input class="form-control @error('brand') is-invalid @enderror" wire:model.defer="brand">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Display model</label>
                        <input class="form-control @error('model') is-invalid @enderror" wire:model.defer="model">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CRM brand match</label>
                        <input class="form-control @error('matchBrand') is-invalid @enderror" wire:model.defer="matchBrand" placeholder="KIA">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CRM model match</label>
                        <input class="form-control @error('matchModel') is-invalid @enderror" wire:model.defer="matchModel" placeholder="PICANTO">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Trim (optional)</label>
                        <input class="form-control @error('trim') is-invalid @enderror" wire:model.defer="trim" placeholder="SE Titanium">
                    </div>
                    <div class="col-md-8 d-flex align-items-end justify-content-end gap-2">
                        <label class="me-auto"><input type="checkbox" wire:model="isActive"> Include in public reservation</label>
                        @if ($editingId)
                            <button type="button" class="btn btn-outline-secondary" wire:click="resetEditor">Cancel</button>
                        @endif
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            {{ $editingId ? 'Save changes' : 'Add catalogue item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body border-bottom">
                <input class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search by model, year, trim, code or website slug">
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle model</th><th>Year</th><th>Variants</th><th>Public</th><th>CRM match</th><th>Reservation link</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $group)
                            <tr wire:key="catalog-year-{{ md5($group['key']) }}">
                                <td>{{ $group['name'] }}</td>
                                <td>{{ $group['year'] }}</td>
                                <td>{{ $group['variant_count'] }}</td>
                                <td>{{ $group['active_count'] === $group['variant_count'] ? 'Included' : ($group['active_count'] > 0 ? 'Partially included' : 'Not included') }}</td>
                                <td>{{ $group['match_brand'] }} {{ $group['match_model'] }}</td>
                                <td><a href="{{ $reservationUrl }}/?vehicle={{ urlencode($group['reservation_code']) }}" target="_blank" rel="noopener noreferrer">Open link · {{ $group['year'] }}</a></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" wire:click="toggleYear({{ $group['id'] }})">{{ $expandedYearKey === $group['key'] ? 'Hide variants' : 'View variants' }}</button>
                                    @if ($canManagePrices)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('car.prices', ['catalogItem' => $group['id']]) }}">Manage prices · {{ $group['year'] }}</a>
                                    @endif
                                </td>
                            </tr>
                            @if ($expandedYearKey === $group['key'])
                                <tr wire:key="catalog-year-variants-{{ md5($group['key']) }}"><td colspan="7" class="bg-light p-0">
                                    <div class="p-3 border-bottom d-flex flex-wrap align-items-center gap-2">
                                        <strong class="me-2">{{ $group['name'] }} · {{ $group['year'] }}</strong>
                                        <button type="button" class="btn btn-sm {{ $group['active_count'] === $group['variant_count'] ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setYearVisibility({{ $group['id'] }}, true)">Include this year</button>
                                        <button type="button" class="btn btn-sm {{ $group['active_count'] === 0 ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setYearVisibility({{ $group['id'] }}, false)">Exclude this year</button>
                                    </div>
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th class="ps-4">Variant / trim</th><th>Code</th><th>Website slug</th><th>Short / Mid / Long (AED)</th><th>Reservation link</th><th>Public / edit</th></tr></thead>
                                        <tbody>
                                        @foreach ($group['variants'] as $item)
                                            <tr wire:key="catalog-item-{{ $item->id }}">
                                                <td class="ps-4">{{ $item->trim ?: 'Standard' }}<small class="d-block text-muted">{{ $item->display_name }}</small></td><td><code>{{ $item->code }}</code></td><td>{{ $item->website_slug }}</td>
                                                <td>{{ count($item->price_signatures) ? implode(' · ', $item->price_signatures) : 'No matching fleet price' }}</td>
                                                <td><a href="{{ $reservationUrl }}/?vehicle={{ urlencode($item->code) }}" target="_blank" rel="noopener noreferrer">Open link · {{ $item->manufacturing_year }}</a></td>
                                                <td><span class="badge bg-label-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Included' : 'Not included' }}</span> <button type="button" class="btn btn-sm btn-outline-primary ms-2" wire:click="edit({{ $item->id }})">Edit variant</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody></table>
                                </td></tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No catalogue model years found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $items->links() }}</div>
        </div>
    </div>
</div>
