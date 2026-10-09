<form class="payment-period-editor" wire:submit.prevent="savePaymentPeriod" x-data x-on:payment-range-edit.window="$el.scrollIntoView({ behavior: 'smooth', block: 'center' }); $el.querySelector('#payment-range-title').focus({ preventScroll: true })">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h6 class="mb-1">{{ $editingPeriodId ? 'Edit saved range' : 'Create a payment range' }}</h6>
            <p class="small text-muted mb-0">Ranges use the payment date. The start is included; the end belongs to the next range. The last range ending on the contract return date also includes payments on that day.</p>
            @if ($editingPeriodId)
                <p class="small text-muted mb-0">Only the last active range by date can be edited. It must stay after earlier ranges without overlapping them.</p>
            @endif
        </div>
        <span class="payment-period-shared"><i class="bi bi-people" aria-hidden="true"></i> Shared with this contract's team</span>
    </div>
    @error('periodForm.period')<div class="alert alert-danger py-2" role="alert">{{ $message }}</div>@enderror
    <div class="row g-3 align-items-start">
        <div class="col-12 col-md-4">
            <label class="form-label" for="payment-range-title">Range name <span class="text-muted fw-normal">(optional)</span></label>
            <input id="payment-range-title" type="text" maxlength="100" class="form-control @error('periodForm.title') is-invalid @enderror" wire:model="periodForm.title" placeholder="e.g. First payment range">
            @error('periodForm.title')<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="payment-range-start">From <span class="text-muted fw-normal">(included)</span></label>
            <input id="payment-range-start" type="date" required class="form-control @error('periodForm.starts_on') is-invalid @enderror" wire:model="periodForm.starts_on" aria-describedby="payment-range-boundary-help">
            @error('periodForm.starts_on')<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>
        <div class="col-12 col-md-4">
            <label class="form-label" for="payment-range-end">Until <span class="text-muted fw-normal">(not included)</span></label>
            <input id="payment-range-end" type="date" required class="form-control @error('periodForm.ends_on') is-invalid @enderror" wire:model="periodForm.ends_on" aria-describedby="payment-range-boundary-help">
            @error('periodForm.ends_on')<div class="invalid-feedback" role="alert">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="payment-period-editor__footer">
        <div>
            <div class="form-check mb-1">
                <input id="payment-range-default" type="checkbox" class="form-check-input" wire:model="periodForm.is_default">
                <label class="form-check-label" for="payment-range-default">Open this range by default for this contract</label>
            </div>
            @error('periodForm.is_default')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
            <p class="small text-muted mb-0" id="payment-range-boundary-help">For example, 14 → 30 includes the 14th through the 29th. The final return date is included for payments in the last range. Rental amounts are allocated from the contract charges; partial ranges share charges by calendar days.</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            @if ($editingPeriodId)
                <button type="button" class="btn btn-light border" wire:click="resetPeriodForm">Cancel edit</button>
            @endif
            <button type="submit" class="btn btn-success" wire:loading.attr="disabled" wire:target="savePaymentPeriod">
                <i class="bi bi-check2" aria-hidden="true"></i> {{ $editingPeriodId ? 'Save changes' : 'Save range' }}
            </button>
        </div>
    </div>
</form>
