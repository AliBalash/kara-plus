@php
    $latestStatus = $contract->relationLoaded('latestStatus')
        ? $contract->latestStatus
        : $contract->latestStatus()->first();
    $latestActor = $latestStatus?->user;
@endphp

@if ($latestActor)
    <span class="badge bg-success" title="{{ $latestActor->fullName() }}">{{ $latestActor->shortName() }}</span>
@elseif ($latestStatus)
    <span class="badge bg-label-secondary text-muted" title="System updated">System</span>
@else
    <span class="badge bg-label-secondary text-muted">No updates</span>
@endif
