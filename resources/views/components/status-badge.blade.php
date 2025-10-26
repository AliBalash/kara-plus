@php
    $badgeClass = \App\Support\ContractStatus::badgeClass($status);
    $displayLabel = \App\Support\ContractStatus::label($status);
@endphp

<span class="badge {{ $badgeClass }}">
    {{ $displayLabel }}
</span>
