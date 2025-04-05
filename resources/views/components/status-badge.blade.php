@php
    switch ($status) {
        case 'pending': $badgeClass = 'bg-label-warning'; break;
        case 'assigned': $badgeClass = 'bg-label-info'; break;
        case 'under_review': $badgeClass = 'bg-label-secondary'; break;
        case 'reserved': $badgeClass = 'bg-label-primary'; break;
        case 'delivery': $badgeClass = 'bg-label-dark'; break;
        case 'agreement_inspection': $badgeClass = 'bg-label-light'; break;
        case 'awaiting_return': $badgeClass = 'bg-label-warning'; break;
        case 'returned': $badgeClass = 'bg-label-success'; break;
        case 'complete': $badgeClass = 'bg-label-success'; break;
        case 'cancelled': $badgeClass = 'bg-label-danger'; break;
        case 'rejected': $badgeClass = 'bg-label-danger'; break;
        default: $badgeClass = 'bg-label-secondary';
    }
@endphp

<span class="badge {{ $badgeClass }}">
    {{ ucfirst($status) }}
</span>
