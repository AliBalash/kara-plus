<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1">Reservation Vehicle Catalogue</h5>
                    <p class="text-muted mb-0">Use one stable code for each public vehicle product. It maps to the CRM model and year, never to a licence plate.</p>
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
                <input class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search by family, code, year or website slug">
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Vehicle family</th><th>Years</th><th>Variants</th><th>Public</th><th>CRM match</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $family)
                            <tr wire:key="catalog-family-{{ md5($family['key']) }}">
                                <td>{{ $family['name'] }}</td>
                                <td>{{ $family['years']->join(', ') }}</td>
                                <td>{{ $family['variant_count'] }}</td>
                                <td>{{ $family['public_mode'] === 'all_years' ? 'All years' : 'Latest year only ('.$family['latest_year'].')' }}</td>
                                <td>{{ $family['match_brand'] }} {{ $family['match_model'] }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" wire:click="toggleFamily('{{ $family['key'] }}')">{{ $expandedFamilyKey === $family['key'] ? 'Hide variants' : 'Edit' }}</button></td>
                            </tr>
                            @if ($expandedFamilyKey === $family['key'])
                                <tr wire:key="catalog-family-variants-{{ md5($family['key']) }}"><td colspan="6" class="bg-light p-0">
                                    <div class="p-3 border-bottom d-flex flex-wrap align-items-center gap-2">
                                        <strong class="me-2">Public reservation:</strong>
                                        <button type="button" class="btn btn-sm {{ $family['public_mode'] === 'all_years' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setFamilyYearMode('{{ $family['key'] }}', true)">Show all years</button>
                                        <button type="button" class="btn btn-sm {{ $family['public_mode'] === 'latest_year_only' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="setFamilyYearMode('{{ $family['key'] }}', false)">Latest year only ({{ $family['latest_year'] }})</button>
                                        <small class="text-muted">Same prices become one reservation card; different prices remain separate cards.</small>
                                    </div>
                                    <table class="table table-sm mb-0"><tbody>
                                        @foreach ($family['variants'] as $item)
                                            <tr wire:key="catalog-item-{{ $item->id }}">
                                                <td class="ps-4">{{ $item->manufacturing_year }}</td><td><code>{{ $item->code }}</code></td><td>{{ $item->website_slug }}</td>
                                                <td colspan="2">Short/Mid/Long: {{ count($item->price_signatures) ? implode(' · ', $item->price_signatures) : 'No matching fleet price' }}</td>
                                                <td><span class="badge bg-label-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Included' : 'Not included' }}</span> <button type="button" class="btn btn-sm btn-outline-primary ms-2" wire:click="edit({{ $item->id }})">Edit variant</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody></table>
                                </td></tr>
                            @endif
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No catalogue vehicle families found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $items->links() }}</div>
        </div>
    </div>
</div>
