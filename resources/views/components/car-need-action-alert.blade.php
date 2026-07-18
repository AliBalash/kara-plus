@props([
    'car' => null,
    'compact' => false,
    'showEditLink' => false,
    'title' => 'Need Action Required',
    'message' => null,
    'showContext' => true,
])

@if ($car?->needsAction())
    @php
        $contextNote = $car->operationalStatusContextNote();
        $tag = $car->id ? 'a' : 'div';
    @endphp
    <{{ $tag }} @if ($car->id) href="{{ route('car.edit', $car->id) }}" @endif {{ $attributes->class([
        'alert alert-danger border-0 shadow-sm need-action-alert mb-0',
        'need-action-alert--clickable text-decoration-none d-block' => (bool) $car->id,
        'py-2 px-3' => $compact,
        'p-3' => ! $compact,
    ]) }} role="alert">
        <div class="d-flex gap-2 align-items-start">
            <i class="bx bx-error-circle {{ $compact ? 'fs-5' : 'fs-4' }} flex-shrink-0 mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-bold">{{ $title }}</div>
                <div class="small">{{ $message ?? $car->needActionAlertMessage() }}</div>
                @if ($showContext && $contextNote)
                    <div class="small mt-1 text-dark">{{ $contextNote }}</div>
                @endif
                @if ($showEditLink)
                    <span class="btn btn-sm btn-light mt-2">
                        Open car edit
                    </span>
                @endif
            </div>
        </div>
    </{{ $tag }}>
@endif
