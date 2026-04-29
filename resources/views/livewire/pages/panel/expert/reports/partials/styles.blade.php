@once
    @push('styles')
        <style>
            .report-hero {
                position: relative;
                overflow: hidden;
                border: 0;
                background:
                    radial-gradient(circle at top left, rgba(29, 78, 216, 0.18), transparent 38%),
                    radial-gradient(circle at bottom right, rgba(15, 118, 110, 0.12), transparent 34%),
                    linear-gradient(135deg, #0f172a 0%, #1e293b 48%, #0f766e 100%);
                color: #fff;
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            }

            .report-hero::after {
                content: "";
                position: absolute;
                inset: auto -10% -38% auto;
                width: 280px;
                height: 280px;
                background: rgba(255, 255, 255, 0.08);
                border-radius: 999px;
                filter: blur(12px);
            }

            .report-eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                border-radius: 999px;
                padding: 0.45rem 0.75rem;
                background: rgba(255, 255, 255, 0.12);
                color: rgba(255, 255, 255, 0.92);
                font-size: 0.75rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .report-subtitle {
                max-width: 760px;
                color: rgba(255, 255, 255, 0.82);
                margin-bottom: 0;
            }

            .report-nav-card {
                border: 1px solid rgba(148, 163, 184, 0.16);
                box-shadow: 0 14px 26px rgba(15, 23, 42, 0.05);
            }

            .report-nav-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
                gap: 0.75rem;
            }

            .report-nav-grid .nav-link {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                border-radius: 14px;
                padding: 0.9rem 1rem;
                color: #334155;
                font-weight: 600;
                border: 1px solid rgba(148, 163, 184, 0.18);
                background: #f8fafc;
            }

            .report-nav-grid .nav-link.active {
                background: linear-gradient(135deg, #dbeafe 0%, #ecfeff 100%);
                color: #0f172a;
                border-color: rgba(37, 99, 235, 0.28);
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.12);
            }

            .report-kpi {
                height: 100%;
                border: 1px solid rgba(148, 163, 184, 0.16);
                border-radius: 18px;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            }

            .report-kpi .metric-label {
                font-size: 0.78rem;
                font-weight: 700;
                color: #64748b;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .report-kpi .metric-value {
                font-size: 1.8rem;
                line-height: 1.05;
                font-weight: 800;
                color: #0f172a;
            }

            .report-kpi .metric-note {
                color: #64748b;
                margin-bottom: 0;
            }

            .report-filter-card {
                border-radius: 20px;
                border: 1px solid rgba(148, 163, 184, 0.16);
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
            }

            .filter-field {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                background: #f8fafc;
                border: 1px solid rgba(148, 163, 184, 0.18);
                border-radius: 16px;
                padding: 0.95rem 1rem;
                height: 100%;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            }

            .filter-field:focus-within {
                background: #fff;
                border-color: rgba(37, 99, 235, 0.3);
                box-shadow: 0 10px 18px rgba(37, 99, 235, 0.08);
                transform: translateY(-1px);
            }

            .filter-label {
                margin: 0;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #475569;
            }

            .filter-hint {
                font-size: 0.72rem;
                color: #94a3b8;
            }

            .report-results-card {
                border-radius: 20px;
                border: 1px solid rgba(148, 163, 184, 0.16);
                overflow: hidden;
                box-shadow: 0 14px 28px rgba(15, 23, 42, 0.05);
            }

            .report-results-meta {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1.1rem 1.4rem;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            }

            .report-filter-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .report-filter-badges .badge {
                background: #e2e8f0;
                color: #334155;
                font-weight: 600;
                padding: 0.5rem 0.7rem;
            }

            .report-table th {
                white-space: nowrap;
                font-size: 0.76rem;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #64748b;
            }

            .report-table td {
                vertical-align: top;
            }

            .report-table .cell-title {
                font-weight: 700;
                color: #0f172a;
            }

            .report-table .cell-subtitle {
                display: block;
                color: #64748b;
                font-size: 0.83rem;
                margin-top: 0.2rem;
            }

            .report-table .cell-metric {
                display: block;
                font-weight: 700;
                color: #0f172a;
            }

            .report-empty {
                display: grid;
                place-items: center;
                min-height: 280px;
                text-align: center;
                color: #64748b;
                background: radial-gradient(circle at top, rgba(59, 130, 246, 0.08), transparent 42%), #fff;
            }

            .report-empty i {
                font-size: 2rem;
                color: #94a3b8;
            }

            @media (max-width: 767.98px) {
                .report-results-meta {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .report-nav-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush
@endonce
