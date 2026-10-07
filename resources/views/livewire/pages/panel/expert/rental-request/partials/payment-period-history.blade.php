@if ($archivedPaymentPeriods->isNotEmpty())
    <details class="payment-period-history">
        <summary>Deleted ranges (history) <span class="text-muted">({{ $archivedPaymentPeriods->count() }})</span></summary>
        <p class="small text-muted mt-2">Deleting removes the range from active views and keeps it in history. Payments remain unchanged. Restore a range if its dates do not overlap an active one.</p>
        @foreach ($archivedPaymentPeriods as $archivedPeriod)
            <div class="payment-period-history__entry" wire:key="archived-payment-period-{{ $archivedPeriod->id }}">
                <div>
                    <strong>{{ $archivedPeriod->title ?? 'Saved range #'.$archivedPeriod->id }}</strong>
                    <div class="small">{{ $archivedPeriod->starts_on->format('M d, Y') }} → before {{ $archivedPeriod->ends_on->format('M d, Y') }}</div>
                    <div class="small text-muted">Saved by {{ $archivedPeriod->createdBy?->shortName() ?? 'Unknown user' }} · {{ $archivedPeriod->created_at->format('Y-m-d H:i') }}</div>
                    <div class="small text-muted">Deleted by {{ $archivedPeriod->archivedBy?->shortName() ?? 'Unknown user' }} · {{ $archivedPeriod->deleted_at->format('Y-m-d H:i') }}</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="restorePaymentPeriod({{ $archivedPeriod->id }})" wire:loading.attr="disabled">Restore range</button>
            </div>
        @endforeach
    </details>
@endif
