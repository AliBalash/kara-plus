<?php

namespace App\Support;

use Illuminate\Support\Str;

class ContractStatus
{
    /**
     * Status definitions mapped to their label and badge class.
     *
     * @var array<string, array{label?: string, badge?: string}>
     */
    protected const STATUSES = [
        'pending' => [
            'badge' => 'bg-label-warning',
        ],
        'assigned' => [
            'badge' => 'bg-label-info',
        ],
        'under_review' => [
            'badge' => 'bg-label-secondary',
            'label' => 'Under Review',
        ],
        'reserved' => [
            'badge' => 'bg-label-primary',
            'label' => 'Booking',
        ],
        'delivery' => [
            'badge' => 'bg-label-dark',
        ],
        'inspection' => [
            'badge' => 'bg-label-secondary',
            'label' => 'Inspection',
        ],
        'agreement_inspection' => [
            'badge' => 'bg-label-secondary',
            'label' => 'Agreement Inspection',
        ],
        'awaiting_return' => [
            'badge' => 'bg-label-warning',
            'label' => 'Awaiting Return',
        ],
        'payment' => [
            'badge' => 'bg-label-info',
            'label' => 'Payment',
        ],
        'returned' => [
            'badge' => 'bg-label-success',
        ],
        'complete' => [
            'badge' => 'bg-label-success',
        ],
        'cancelled' => [
            'badge' => 'bg-label-danger',
        ],
        'rejected' => [
            'badge' => 'bg-label-danger',
        ],
        'draft' => [
            'badge' => 'bg-label-secondary',
        ],
        'unknown' => [
            'badge' => 'bg-label-secondary',
        ],
    ];

    public static function badgeClass(?string $status): string
    {
        return self::details($status)['badge'];
    }

    public static function label(?string $status): string
    {
        return self::details($status)['label'];
    }

    /**
     * Resolve the normalised status information.
     *
     * @return array{badge: string, label: string}
     */
    protected static function details(?string $status): array
    {
        $normalized = $status ? Str::of($status)->lower()->value() : 'unknown';

        $definition = self::STATUSES[$normalized] ?? [];

        $badge = $definition['badge'] ?? 'bg-label-secondary';
        $label = $definition['label'] ?? Str::headline($normalized);

        return [
            'badge' => $badge,
            'label' => $label,
        ];
    }
}
