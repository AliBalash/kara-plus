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
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="catalog-active" wire:model.defer="isActive">
                            <label class="form-check-label" for="catalog-active">Available for public reservation</label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-end gap-2">
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
                <input class="form-control" wire:model.live.debounce.300ms="search" placeholder="Search by code, name or website slug">
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th><th>Vehicle</th><th>CRM match</th><th>Website slug</th><th>Reservation link</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr wire:key="catalog-item-{{ $item->id }}">
                                <td><code>{{ $item->code }}</code></td>
                                <td>{{ $item->display_name }} · {{ $item->manufacturing_year }}@if($item->trim) <small class="text-muted">({{ $item->trim }})</small> @endif</td>
                                <td>{{ $item->match_brand }} {{ $item->match_model }}</td>
                                <td>{{ $item->website_slug }}</td>
                                <td><a href="{{ $reservationUrl }}/?vehicle={{ urlencode($item->code) }}" target="_blank" rel="noreferrer">Open link</a></td>
                                <td><span class="badge bg-label-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" wire:click="edit({{ $item->id }})">Edit</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No catalogue vehicles found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $items->links() }}</div>
        </div>
    </div>
</div>
