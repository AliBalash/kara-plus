@pushOnce('styles', 'kara-form-error-styles')
    <style>
        .validation-error-highlight {
            animation: validationPulse 1.4s ease;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.35) !important;
        }

        @keyframes validationPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.3);
            }

            70% {
                box-shadow: 0 0 0 0.6rem rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
@endPushOnce

@pushOnce('scripts', 'kara-form-error-scripts')
    <script>
        (() => {
            const highlightClass = 'validation-error-highlight';

            const candidateSelectors = (field) => [
                `[data-validation-field="${field}"]`,
                `[wire\\:model="${field}"]`,
                `[wire\\:model\\.live="${field}"]`,
                `[wire\\:model\\.defer="${field}"]`,
                `[wire\\:model\\.lazy="${field}"]`,
                `[name="${field}"]`,
            ];

            const findFieldElement = (field) => {
                if (!field) {
                    return null;
                }

                for (const selector of candidateSelectors(field)) {
                    const element = document.querySelector(selector);
                    if (element) {
                        return element;
                    }
                }

                return null;
            };

            const findContainer = (element) => {
                if (!element) {
                    return null;
                }

                return element.closest('[data-validation-field]')
                    ?? element.closest('.input-group, .form-group, .form-floating, .form-check, .card, .document-card, .mb-3, .row, .col-md-4, .col-md-6, .col-lg-4, .col-lg-6')
                    ?? element;
            };

            document.addEventListener('kara-scroll-to-error', (event) => {
                const field = event?.detail?.field;
                if (!field) {
                    return;
                }

                const baseField = field.split('.')[0];
                let element = findFieldElement(field) ?? findFieldElement(baseField);

                if (!element) {
                    return;
                }

                const container = findContainer(element);
                if (!container) {
                    return;
                }

                container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                container.classList.add(highlightClass);
                setTimeout(() => container.classList.remove(highlightClass), 2200);
            });
        })();
    </script>
@endPushOnce
