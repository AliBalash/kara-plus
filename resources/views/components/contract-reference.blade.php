<span class="text-nowrap">
    #{{ $contract->id }}
    @if (filled($contract->agreement_number))
        <span class="badge bg-label-info text-info ms-1">AG {{ $contract->agreement_number }}</span>
    @endif
</span>
