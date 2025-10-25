@once
    @push('styles')
        <style>
            .search-offcanvas {
                width: min(460px, 100vw);
            }

            .search-offcanvas .offcanvas-body {
                padding: 1.9rem 1.75rem;
                background: linear-gradient(180deg, rgba(248, 250, 252, 0.96) 0%, rgba(255, 255, 255, 0.98) 100%);
            }

            .search-input-wrapper {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                border: 1px solid rgba(79, 70, 229, 0.18);
                border-radius: 999px;
                padding: 0.7rem 1.1rem;
                background: rgba(248, 250, 252, 0.88);
                box-shadow: inset 0 1px 2px rgba(79, 70, 229, 0.12);
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .search-input-wrapper:focus-within {
                border-color: rgba(79, 70, 229, 0.5);
                box-shadow: 0 8px 30px rgba(79, 70, 229, 0.15);
            }

            .search-input-wrapper .form-control {
                border: none;
                background: transparent;
                box-shadow: none;
                padding-left: 0;
            }

            .search-results {
                position: relative;
                min-height: 200px;
            }

            .result-card {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                border-radius: 1.25rem;
                border: 1px solid rgba(15, 23, 42, 0.08);
                padding: 1.25rem 1.4rem;
                text-decoration: none;
                transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
                position: relative;
                overflow: hidden;
                background: #fff;
            }

            .result-card:hover,
            .result-card:focus-visible {
                transform: translateY(-4px);
                border-color: rgba(79, 70, 229, 0.35);
                box-shadow: 0 28px 60px -32px rgba(79, 70, 229, 0.55);
            }

            .result-card:focus-visible {
                outline: none;
            }

            .result-card-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
                margin-bottom: 1.1rem;
            }

            .result-card-heading {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }

            .vehicle-name {
                font-weight: 700;
                font-size: 1.08rem;
                color: #0f172a;
                letter-spacing: -0.01em;
            }

            .vehicle-sub {
                font-size: 0.82rem;
                color: #64748b !important;
            }

            .status-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.4rem 0.9rem;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #1f2937;
                background: rgba(148, 163, 184, 0.2);
                border: 1px solid rgba(148, 163, 184, 0.32);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.45);
            }

            .status-chip i {
                font-size: 1rem;
            }

            .status-chip.is-success {
                background: rgba(16, 185, 129, 0.18);
                color: #0f5132;
                border-color: rgba(16, 185, 129, 0.35);
            }

            .status-chip.is-warning {
                background: rgba(234, 179, 8, 0.18);
                color: #854d0e;
                border-color: rgba(234, 179, 8, 0.3);
            }

            .status-chip.is-danger {
                background: rgba(248, 113, 113, 0.22);
                color: #7f1d1d;
                border-color: rgba(248, 113, 113, 0.35);
            }

            .status-chip.is-secondary {
                background: rgba(148, 163, 184, 0.24);
                color: #1f2937;
                border-color: rgba(148, 163, 184, 0.4);
            }

            .result-card-highlights {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 0.9rem;
                margin-bottom: 1.25rem;
            }

            .highlight-card {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                padding: 0.85rem 1rem;
                border-radius: 1rem;
                background: rgba(15, 23, 42, 0.03);
                border: 1px solid rgba(15, 23, 42, 0.06);
                min-height: 92px;
            }

            .highlight-label {
                font-size: 0.68rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #64748b;
                font-weight: 600;
            }

            .highlight-value {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                font-size: 0.9rem;
                font-weight: 600;
                color: #0f172a;
            }

            .highlight-value i {
                color: #4f46e5;
                font-size: 1.1rem;
            }

            .highlight-hint {
                display: block;
                font-size: 0.75rem;
                color: #64748b;
            }

            .reservation-card {
                border-radius: 1.1rem;
                border: 1px dashed rgba(79, 70, 229, 0.38);
                background: rgba(79, 70, 229, 0.08);
                padding: 1.1rem 1.2rem;
                margin-bottom: 1.25rem;
                backdrop-filter: blur(2px);
            }

            .reservation-card-heading {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                margin-bottom: 0.9rem;
            }

            .reservation-card-heading i {
                font-size: 1.25rem;
                color: #4f46e5;
            }

            .reservation-title {
                display: block;
                font-weight: 700;
                color: #312e81;
                font-size: 0.95rem;
            }

            .reservation-subtitle {
                display: block;
                font-size: 0.8rem;
                color: #6366f1;
            }

            .reservation-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 0.9rem;
            }

            .reservation-item {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                padding: 0.85rem 0.95rem;
                border-radius: 0.95rem;
                background: rgba(255, 255, 255, 0.72);
                border: 1px solid rgba(99, 102, 241, 0.16);
            }

            .reservation-label {
                font-size: 0.65rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6366f1;
                font-weight: 600;
            }

            .reservation-value {
                font-weight: 600;
                color: #1e1b4b;
                font-size: 0.88rem;
            }

            .reservation-sub {
                font-size: 0.75rem;
                color: #475569;
            }

            .result-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                border-top: 1px solid rgba(15, 23, 42, 0.08);
                padding-top: 1.1rem;
            }

            .result-card-footer-meta {
                display: flex;
                flex-direction: column;
                gap: 0.3rem;
                min-width: 0;
            }

            .meta-label {
                font-size: 0.65rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #94a3b8;
                font-weight: 600;
            }

            .meta-value {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.85rem;
                font-weight: 600;
                color: #1f2937;
            }

            .meta-value i {
                color: #4c1d95;
            }

            .result-card-arrow {
                width: 2.35rem;
                height: 2.35rem;
                border-radius: 999px;
                background: rgba(79, 70, 229, 0.12);
                color: #4f46e5;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.25s ease, background-color 0.25s ease, color 0.25s ease;
                flex-shrink: 0;
            }

            .result-card:hover .result-card-arrow,
            .result-card:focus-visible .result-card-arrow {
                transform: translateX(6px);
                background: #4f46e5;
                color: #fff;
            }

            .search-placeholder {
                color: #94a3b8;
            }

            .search-placeholder i {
                color: #e2e8f0;
            }

            .search-loading {
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.72);
                border-radius: 1.25rem;
                z-index: 2;
            }

            .search-results.is-loading .result-card,
            .search-results.is-loading .search-placeholder {
                opacity: 0.25;
                filter: blur(1px);
            }

            .search-filters {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
                font-size: 0.85rem;
                color: #475569;
            }

            .filter-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.35rem 0.75rem;
                border-radius: 999px;
                background: rgba(148, 163, 184, 0.18);
                font-weight: 600;
                font-size: 0.78rem;
                color: #475569;
            }

            .filter-pill i {
                color: #64748b;
            }

            .filter-pill strong {
                color: #0f172a;
                font-weight: 700;
            }

            .search-offcanvas .offcanvas-header {
                padding-bottom: 0;
            }

            .recent-searches {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .recent-searches button {
                border: 1px solid rgba(15, 23, 42, 0.08);
                background: rgba(15, 23, 42, 0.02);
                border-radius: 999px;
                padding: 0.35rem 0.85rem;
                font-size: 0.78rem;
                color: #475569;
                transition: all 0.2s ease;
            }

            .recent-searches button:hover {
                background: #fff;
                border-color: rgba(79, 70, 229, 0.35);
                color: #4f46e5;
                box-shadow: 0 10px 25px -15px rgba(79, 70, 229, 0.35);
            }

            @media (max-width: 991.98px) {
                .search-offcanvas {
                    width: min(520px, 100vw);
                }

                .result-card-highlights {
                    gap: 0.75rem;
                }
            }

            @media (max-width: 575.98px) {
                .search-offcanvas {
                    width: 100vw;
                }

                .search-results {
                    max-height: calc(100vh - 170px);
                }

                .result-card-footer {
                    flex-direction: column;
                    align-items: stretch;
                }

                .result-card-arrow {
                    align-self: flex-end;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .search-input-wrapper,
                .search-results,
                .result-card,
                .result-card-arrow {
                    transition: none !important;
                }
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            (() => {
                const MENU_KEY = 'layout-menu';
                const RESIZE_FLAG = '__panelMenuResizeBound';

                const getToggles = () => document.querySelectorAll(`[data-menu-toggle="${MENU_KEY}"]`);
                const getOverlay = () => document.querySelector('.layout-overlay.layout-menu-toggle');
                const getMenu = () => document.getElementById(MENU_KEY);

                const updateState = () => {
                    const menu = getMenu();
                    if (!menu) {
                        return;
                    }

                    const expanded = document.documentElement.classList.contains('layout-menu-expanded');
                    const isSmallScreen = window.matchMedia('(max-width: 1199.98px)').matches;
                    const ariaExpanded = expanded ? 'true' : 'false';

                    getToggles().forEach((toggle) => toggle.setAttribute('aria-expanded', ariaExpanded));

                    if (isSmallScreen) {
                        menu.setAttribute('aria-hidden', expanded ? 'false' : 'true');
                    } else {
                        menu.removeAttribute('aria-hidden');
                    }
                };

                const scheduleUpdate = () => window.requestAnimationFrame(updateState);

                const bind = () => {
                    const toggles = getToggles();
                    const overlay = getOverlay();

                    if (!toggles.length) {
                        return;
                    }

                    toggles.forEach((toggle) => {
                        if (toggle.dataset.menuToggleBound === 'true') {
                            return;
                        }

                        toggle.addEventListener('click', scheduleUpdate);
                        toggle.dataset.menuToggleBound = 'true';
                    });

                    if (overlay && overlay.dataset.menuToggleBound !== 'true') {
                        overlay.addEventListener('click', scheduleUpdate);
                        overlay.dataset.menuToggleBound = 'true';
                    }

                    if (!window[RESIZE_FLAG]) {
                        window.addEventListener('resize', scheduleUpdate);
                        window[RESIZE_FLAG] = true;
                    }

                    updateState();
                };

                if (document.readyState !== 'loading') {
                    bind();
                } else {
                    document.addEventListener('DOMContentLoaded', bind, { once: true });
                }

                window.addEventListener('livewire:navigated', bind);
            })();

            document.addEventListener('shown.bs.offcanvas', (event) => {
                if (event.target && event.target.id === 'quickSearchOffcanvas') {
                    const input = event.target.querySelector('#quickSearchInput');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                }
            });
        </script>
    @endpush
@endonce
