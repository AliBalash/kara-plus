@once
    @push('styles')
        <style>
            .filter-field {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                background: #f9fafb;
                border: 1px solid rgba(145, 158, 171, 0.18);
                border-radius: 14px;
                padding: 0.9rem 1rem;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .filter-field:focus-within {
                border-color: rgba(105, 108, 255, 0.5);
                box-shadow: 0 6px 16px rgba(105, 108, 255, 0.12);
                background: #fff;
            }

            .filter-label {
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #4b5563;
                margin: 0;
            }

            .filter-hint {
                font-size: 0.7rem;
                color: #94a3b8;
            }
        </style>
    @endpush
@endonce
