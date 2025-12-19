@props(['car'])

@php
    $label = $car?->ownershipLabel() ?? 'Fleet';
    $class = $car?->ownershipBadgeClass() ?? 'bg-label-secondary';
@endphp

@if ($car)
    <span {{ $attributes->merge(['class' => "badge {$class}"]) }}>
        {{ $label }}
    </span>
@endif
