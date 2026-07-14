<style>
                .driver-dashboard .next-task-card {
                    border: 1px solid #e0e6ef;
                    border-radius: 1rem;
                    background: #f7f9fc;
                    padding: 1.1rem 1.25rem;
                    box-shadow: 0 10px 24px rgba(32, 56, 90, 0.12);
                    min-height: 170px;
                }

                .next-task-label {
                    display: inline-flex;
                    align-items: center;
                    gap: 0.35rem;
                    padding: 0.25rem 0.7rem;
                    border-radius: 999px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.08em;
                }

                .next-task-label--pickup {
                    background: rgba(58, 134, 255, 0.15);
                    color: #1f57d6;
                }

                .next-task-label--return {
                    background: rgba(46, 204, 113, 0.2);
                    color: #1f8a49;
                }

                .next-task-label--idle {
                    background: rgba(133, 146, 163, 0.18);
                    color: #5c6b7a;
                }

                .driver-stat-card {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    border-radius: 1rem;
                    padding: 0.85rem 1rem;
                    border: 1px solid rgba(224, 230, 239, 0.7);
                    background: #fff;
                    box-shadow: 0 8px 20px rgba(32, 56, 90, 0.06);
                    min-height: 92px;
                }

                .driver-stat-card .stat-icon {
                    width: 2.4rem;
                    height: 2.4rem;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                    color: inherit;
                    background: rgba(33, 56, 90, 0.08);
                }

                .driver-stat-card .stat-meta {
                    display: flex;
                    flex-direction: column;
                    gap: 0.2rem;
                }

                .driver-stat-card .stat-value {
                    font-weight: 700;
                    font-size: 1.2rem;
                }

                .driver-stat-card .stat-label {
                    font-size: 0.78rem;
                    text-transform: uppercase;
                    letter-spacing: 0.06em;
                    color: #6f7f92;
                }

                .driver-stat-card.accent-pickup {
                    border-color: rgba(58, 134, 255, 0.25);
                    color: #1f57d6;
                }

                .driver-stat-card.accent-return {
                    border-color: rgba(46, 204, 113, 0.3);
                    color: #1f8a49;
                }

                .driver-stat-card.accent-active {
                    border-color: rgba(17, 138, 178, 0.28);
                    color: #107dac;
                }

                .driver-stat-card.accent-warning {
                    border-color: rgba(255, 152, 0, 0.28);
                    color: #c17200;
                }

                .driver-task-card .task-item {
                    padding: 0.9rem 0;
                    border-bottom: 1px dashed rgba(224, 230, 239, 0.8);
                }

                .driver-task-card .task-item:last-child {
                    border-bottom: none;
                }

                .driver-task-card .task-info h6 {
                    font-weight: 600;
                }

                @media (max-width: 575.98px) {
                    .driver-stat-card {
                        padding: 0.75rem 0.9rem;
                    }

                    .driver-dashboard .next-task-card {
                        min-height: 150px;
                    }
                }

            .fleet-status-hero {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at 0% 0%, rgba(51, 153, 255, 0.2), transparent 48%),
                    radial-gradient(circle at 100% 100%, rgba(0, 206, 170, 0.16), transparent 45%),
                    linear-gradient(132deg, #f8fbff 0%, #eef5ff 52%, #f8fdfb 100%);
                border: 1px solid rgba(191, 210, 236, 0.6);
            }

            .fleet-status-hero__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.8rem;
            }

            .fleet-status-hero__eyebrow {
                display: inline-flex;
                align-items: center;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-weight: 700;
                color: #2f5aa3;
            }

            .fleet-status-hero__meta {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .fleet-status-hero__pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.4rem 0.72rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                border: 1px solid rgba(198, 212, 232, 0.75);
                color: #3a526d;
                font-size: 0.78rem;
                white-space: nowrap;
                box-shadow: 0 6px 14px rgba(26, 48, 78, 0.06);
            }

            .fleet-status-hero__pill strong {
                color: #172c47;
                margin-left: 0.15rem;
            }

            .fleet-status-card {
                display: flex;
                align-items: center;
                gap: 0.8rem;
                min-height: 104px;
                border-radius: 1rem;
                border: 1px solid rgba(198, 213, 234, 0.75);
                background: rgba(255, 255, 255, 0.84);
                backdrop-filter: blur(2px);
                padding: 0.85rem 0.95rem;
                box-shadow: 0 10px 24px rgba(31, 56, 86, 0.08);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .fleet-status-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 26px rgba(31, 56, 86, 0.14);
            }

            .fleet-status-card__icon {
                width: 2.6rem;
                height: 2.6rem;
                border-radius: 0.8rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.15rem;
                flex-shrink: 0;
            }

            .fleet-status-card__content {
                min-width: 0;
            }

            .fleet-status-card__value {
                font-size: 1.35rem;
                font-weight: 700;
                color: #1f3858;
                line-height: 1.1;
            }

            .fleet-status-card__label {
                margin-top: 0.12rem;
                font-size: 0.8rem;
                font-weight: 600;
                color: #304c6f;
                letter-spacing: 0.01em;
            }

            .fleet-status-card__hint {
                margin-top: 0.12rem;
                font-size: 0.73rem;
                color: #60758f;
            }

            .fleet-status-card--available .fleet-status-card__icon {
                background: rgba(46, 204, 113, 0.18);
                color: #1b8f4a;
            }

            .fleet-status-card--unavailable .fleet-status-card__icon {
                background: rgba(255, 99, 71, 0.16);
                color: #b5412f;
            }

            .fleet-status-card--booked .fleet-status-card__icon {
                background: rgba(255, 179, 0, 0.2);
                color: #a56b00;
            }

            .fleet-status-card--reservations .fleet-status-card__icon {
                background: rgba(79, 131, 255, 0.18);
                color: #2d55c7;
            }

            .fleet-status-card--sold .fleet-status-card__icon {
                background: rgba(54, 63, 79, 0.16);
                color: #2b3344;
            }

            .fleet-reason-strip {
                display: flex;
                flex-direction: column;
                gap: 0.7rem;
                padding-top: 0.95rem;
                border-top: 1px dashed rgba(164, 182, 208, 0.7);
            }

            .fleet-reason-strip__label {
                font-size: 0.78rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #4a6688;
            }

            .fleet-reason-strip__items {
                display: flex;
                flex-wrap: wrap;
                gap: 0.55rem;
            }

            .fleet-reason-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.48rem 0.8rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.9);
                border: 1px solid rgba(194, 208, 227, 0.8);
                color: #38506d;
                box-shadow: 0 8px 18px rgba(22, 45, 75, 0.05);
            }

            .fleet-reason-chip strong {
                color: #132c4a;
            }

            .attention-queue-card {
                height: 100%;
                border-radius: 1rem;
                border: 1px solid rgba(221, 102, 94, 0.18);
                background: linear-gradient(145deg, rgba(255, 252, 251, 0.96), rgba(255, 247, 245, 0.98));
                box-shadow: 0 12px 30px rgba(50, 61, 80, 0.08);
                padding: 1rem 1.05rem;
                display: flex;
                flex-direction: column;
                gap: 0.65rem;
            }

            .attention-queue-card__reason {
                font-weight: 700;
                color: #7b2f26;
            }

            .attention-queue-card__note {
                font-size: 0.82rem;
                color: #a05b28;
            }

            .attention-queue-card__action {
                color: #2f4662;
                font-size: 0.87rem;
            }

            .attention-queue-card__meta {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
                font-size: 0.8rem;
                color: #697d95;
            }

            @media (max-width: 991.98px) {
                .fleet-status-hero__header {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .fleet-status-hero__meta {
                    justify-content: flex-start;
                }
            }

            @media (max-width: 575.98px) {
                .fleet-status-hero__pill {
                    width: 100%;
                    justify-content: space-between;
                    padding: 0.46rem 0.7rem;
                }

                .fleet-status-card {
                    min-height: 96px;
                }
            }

            .available-fleet-toolbar {
                display: grid;
                grid-template-columns: minmax(260px, 1fr) minmax(0, 2.25fr);
                align-items: center;
                gap: 0.5rem;
                padding: 0.72rem;
                border-radius: 1rem;
                border: 1px solid rgba(224, 230, 239, 0.8);
                background: linear-gradient(138deg, rgba(246, 250, 255, 0.97), rgba(255, 255, 255, 0.98));
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8), 0 8px 20px rgba(24, 49, 79, 0.05);
            }

            .available-fleet-toolbar__search {
                display: flex;
                align-items: center;
                gap: 0.42rem;
                width: 100%;
                border: 1px solid rgba(203, 214, 231, 0.95);
                border-radius: 0.78rem;
                background: #fff;
                padding: 0 0.58rem;
                min-height: 2.45rem;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
            }

            .available-fleet-toolbar__search:focus-within {
                border-color: rgba(82, 109, 255, 0.68);
                box-shadow: 0 0 0 0.2rem rgba(82, 109, 255, 0.12);
            }

            .available-fleet-toolbar__search i {
                color: #60758f;
                font-size: 0.86rem;
                width: 1rem;
                text-align: center;
            }

            .available-fleet-toolbar__search .form-control {
                min-height: 2.3rem;
                height: 2.3rem;
                padding-left: 0;
                font-size: 0.83rem;
                background: transparent;
                box-shadow: none;
            }

            .available-fleet-toolbar__search .form-control::placeholder {
                color: #8b9db3;
            }

            .available-fleet-toolbar__panel {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.42rem;
                min-width: 0;
            }

            .available-fleet-toolbar__filters {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                align-items: center;
                gap: 0.42rem;
                min-width: 0;
            }

            .available-fleet-toolbar__filters .form-select {
                width: 100%;
                min-width: 0;
                min-height: 2.45rem;
                border-color: rgba(205, 216, 233, 0.95);
                border-radius: 0.68rem;
                font-size: 0.8rem;
                box-shadow: none;
                background-position: right 0.6rem center;
                padding-right: 1.7rem;
            }

            .available-fleet-toolbar__filters .form-select:focus {
                border-color: rgba(82, 109, 255, 0.58);
                box-shadow: 0 0 0 0.18rem rgba(82, 109, 255, 0.1);
            }

            .available-fleet-toolbar__actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 0.42rem;
            }

            .available-fleet-toolbar__actions .btn {
                min-height: 2.45rem;
                border-radius: 0.68rem;
                padding-inline: 0.82rem;
            }

            .available-fleet-toolbar__reset {
                white-space: nowrap;
            }

            .available-fleet-feedback {
                color: #5e7087;
                font-size: 0.78rem;
                padding-inline: 0.2rem;
            }

            .available-fleet-feedback strong {
                color: #243d5d;
                font-size: 0.82rem;
                letter-spacing: 0.01em;
            }

            .available-fleet-loading {
                position: absolute;
                inset: 0;
                z-index: 8;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.84);
                color: #344d6b;
                font-weight: 600;
                font-size: 0.86rem;
                backdrop-filter: blur(1px);
            }

            @media (max-width: 1199.98px) {
                .available-fleet-toolbar {
                    grid-template-columns: 1fr;
                }

                .available-fleet-toolbar__panel {
                    grid-template-columns: 1fr;
                }

                .available-fleet-toolbar__filters {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .available-fleet-toolbar__actions {
                    justify-content: flex-start;
                }
            }

            @media (max-width: 575.98px) {
                .available-fleet-toolbar {
                    gap: 0.55rem;
                    padding: 0.64rem;
                    border-radius: 0.85rem;
                }

                .available-fleet-toolbar__search,
                .available-fleet-toolbar__panel,
                .available-fleet-toolbar__filters,
                .available-fleet-toolbar__actions {
                    width: 100%;
                    min-width: 100%;
                }

                .available-fleet-toolbar__filters {
                    grid-template-columns: 1fr;
                }

                .available-fleet-toolbar__actions {
                    display: grid;
                    grid-template-columns: 1fr;
                }

                .available-fleet-toolbar__filters .form-select,
                .available-fleet-toolbar__actions .btn {
                    width: 100%;
                }
            }

            .compliance-monitor-card {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at 0% 0%, rgba(20, 184, 166, 0.12), transparent 38%),
                    radial-gradient(circle at 100% 0%, rgba(59, 130, 246, 0.12), transparent 36%),
                    linear-gradient(135deg, #fcfffe 0%, #f6fbff 54%, #f8fdfc 100%);
                border: 1px solid rgba(198, 217, 231, 0.72);
            }

            .compliance-monitor__header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 1rem;
            }

            .compliance-monitor__eyebrow {
                display: inline-flex;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                font-weight: 700;
                color: #0f766e;
            }

            .compliance-monitor__legend {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 0.5rem;
            }

            .compliance-monitor__legend-item {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.42rem 0.72rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.88);
                border: 1px solid rgba(205, 218, 231, 0.8);
                color: #38506b;
                font-size: 0.78rem;
                box-shadow: 0 10px 22px rgba(30, 56, 84, 0.06);
            }

            .compliance-kpi {
                display: flex;
                align-items: center;
                gap: 0.9rem;
                min-height: 104px;
                padding: 0.95rem 1rem;
                border-radius: 1.1rem;
                border: 1px solid rgba(203, 217, 232, 0.8);
                background: rgba(255, 255, 255, 0.9);
                box-shadow: 0 16px 30px rgba(25, 50, 78, 0.08);
            }

            .compliance-kpi__icon {
                width: 3rem;
                height: 3rem;
                border-radius: 0.95rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.25rem;
                flex-shrink: 0;
            }

            .compliance-kpi__value {
                font-size: 1.5rem;
                font-weight: 700;
                color: #17324f;
                line-height: 1.05;
            }

            .compliance-kpi__label {
                margin-top: 0.18rem;
                color: #50647b;
                font-size: 0.82rem;
                font-weight: 600;
            }

            .compliance-kpi--insurance-month .compliance-kpi__icon {
                background: rgba(59, 130, 246, 0.14);
                color: #1d4ed8;
            }

            .compliance-kpi--insurance-urgent .compliance-kpi__icon {
                background: rgba(249, 115, 22, 0.14);
                color: #c2410c;
            }

            .compliance-kpi--passing-month .compliance-kpi__icon {
                background: rgba(16, 185, 129, 0.14);
                color: #047857;
            }

            .compliance-kpi--passing-urgent .compliance-kpi__icon {
                background: rgba(168, 85, 247, 0.14);
                color: #7e22ce;
            }

            .compliance-list-card {
                border-radius: 1.15rem;
                border: 1px solid rgba(207, 219, 232, 0.85);
                background: rgba(255, 255, 255, 0.92);
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95), 0 12px 26px rgba(29, 53, 82, 0.06);
                padding: 1rem;
            }

            .compliance-list-card__badge {
                min-width: 2.1rem;
                height: 2.1rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                background: rgba(30, 64, 175, 0.08);
                color: #1e40af;
                font-weight: 700;
            }

            .compliance-list-card__body {
                max-height: 420px;
                overflow-y: auto;
                padding-right: 0.2rem;
            }

            .compliance-item {
                padding: 0.95rem 0;
                border-bottom: 1px dashed rgba(207, 219, 232, 0.92);
            }

            .compliance-item:first-child {
                padding-top: 0.1rem;
            }

            .compliance-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }

            .compliance-item__countdown {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0.4rem 0.72rem;
                border-radius: 999px;
                background: rgba(148, 163, 184, 0.14);
                color: #475569;
                font-size: 0.76rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .compliance-item__countdown.is-urgent {
                background: rgba(245, 158, 11, 0.16);
                color: #b45309;
            }

            .compliance-item__countdown.is-overdue {
                background: rgba(239, 68, 68, 0.16);
                color: #b91c1c;
            }

            .compliance-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 0.42rem 0.7rem;
                background: rgba(226, 232, 240, 0.6);
                color: #334155;
                font-weight: 600;
            }

            .compliance-chip--month {
                background: rgba(59, 130, 246, 0.12);
                color: #1d4ed8;
            }

            .compliance-chip--urgent {
                background: rgba(245, 158, 11, 0.16);
                color: #b45309;
            }

            .compliance-chip--overdue {
                background: rgba(239, 68, 68, 0.14);
                color: #b91c1c;
            }

            @media (max-width: 991.98px) {
                .compliance-monitor__header {
                    flex-direction: column;
                }

                .compliance-monitor__legend {
                    justify-content: flex-start;
                }
            }

            @media (max-width: 575.98px) {
                .compliance-kpi {
                    min-height: 96px;
                }

                .compliance-item__countdown {
                    white-space: normal;
                    text-align: center;
                }
            }

            .booking-card {
                border: 1px solid rgba(224, 230, 239, 0.7);
                background: #fff;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .booking-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 18px 32px rgba(32, 56, 90, 0.12);
            }

            .booking-card__image {
                width: 70px;
                height: 70px;
                object-fit: cover;
                flex-shrink: 0;
            }

            .booking-card--reserved .booking-timeline__leg--accent {
                background: rgba(255, 196, 0, 0.12);
                border: 1px dashed rgba(255, 171, 0, 0.45);
            }

            .booking-card--return .booking-timeline__leg--accent {
                background: rgba(46, 204, 113, 0.12);
                border: 1px dashed rgba(46, 204, 113, 0.45);
            }

            .booking-timeline {
                display: flex;
                align-items: stretch;
                gap: 1rem;
                flex-wrap: wrap;
            }

            .booking-timeline__leg {
                flex: 1 1 240px;
                background: rgba(133, 146, 163, 0.08);
                border-radius: 0.9rem;
                padding: 0.9rem 1rem;
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                border: 1px solid rgba(224, 230, 239, 0.8);
            }

            .booking-timeline__label {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                color: #6f7f92;
                font-weight: 600;
            }

            .booking-timeline__date {
                font-weight: 600;
                color: #20385a;
            }

            .booking-timeline__meta {
                font-size: 0.78rem;
                color: #5c6b7a;
                display: flex;
                align-items: center;
            }

            .booking-timeline__divider {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 0 0.5rem;
                color: #a0acb8;
                min-width: 48px;
            }

            .booking-timeline__distance {
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.12em;
                color: #8592a3;
            }

            .booking-timeline__divider i {
                font-size: 1.5rem;
            }

            .booking-timeline__leg--accent {
                background: rgba(105, 108, 255, 0.1);
                border-color: rgba(105, 108, 255, 0.35);
            }

            .meta-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem 0.85rem;
                border-radius: 999px;
                background: rgba(133, 146, 163, 0.16);
                color: #495d6c;
                font-size: 0.78rem;
                font-weight: 600;
                letter-spacing: 0.01em;
                white-space: nowrap;
            }

            .meta-chip i {
                font-size: 0.95rem;
            }

            .meta-chip--calendar {
                background: rgba(80, 136, 247, 0.18);
                color: #1d4fc4;
            }

            .meta-chip--person {
                background: rgba(255, 214, 153, 0.28);
                color: #a16000;
            }

            .meta-chip--location {
                background: rgba(111, 207, 151, 0.2);
                color: #1f7a46;
            }

            .meta-chip--muted {
                background: rgba(133, 146, 163, 0.16);
                color: #495d6c;
            }

            .meta-chip--request {
                background: rgba(255, 171, 0, 0.18);
                color: #a66a00;
            }

            .meta-chip--agreement {
                background: rgba(32, 56, 90, 0.12);
                color: #20385a;
            }

            .meta-chip--status {
                background: rgba(105, 108, 255, 0.16);
                color: #3f45d4;
            }

            .meta-chip--duration {
                background: rgba(3, 195, 236, 0.14);
                color: #0b7a92;
            }

            .operations-hero {
                position: relative;
                overflow: hidden;
                background:
                    radial-gradient(circle at 10% 12%, rgba(255, 174, 66, 0.2), transparent 38%),
                    radial-gradient(circle at 88% 16%, rgba(44, 182, 125, 0.18), transparent 36%),
                    linear-gradient(125deg, #fff9ef 0%, #f7fbff 48%, #effaf4 100%);
                border: 1px solid rgba(223, 215, 190, 0.72);
            }

            .operations-hero__header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.8rem;
            }

            .operations-hero__eyebrow {
                display: inline-flex;
                align-items: center;
                font-size: 0.72rem;
                text-transform: uppercase;
                letter-spacing: 0.11em;
                font-weight: 700;
                color: #8a5a16;
            }

            .operations-hero__meta {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .operations-hero__pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.4rem 0.74rem;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.86);
                border: 1px solid rgba(224, 211, 179, 0.78);
                color: #6b4b1d;
                font-size: 0.78rem;
                white-space: nowrap;
                box-shadow: 0 6px 14px rgba(77, 62, 32, 0.08);
            }

            .dashboard-highlight-card {
                border: 1px solid rgba(209, 217, 228, 0.84);
                background: rgba(255, 255, 255, 0.78);
                backdrop-filter: blur(2px);
                box-shadow: 0 10px 22px rgba(32, 56, 90, 0.08);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .dashboard-highlight-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 16px 30px rgba(32, 56, 90, 0.14);
            }

            .dashboard-highlight-card__icon {
                width: 52px;
                height: 52px;
                flex-shrink: 0;
            }

            .dashboard-highlight-card--primary .dashboard-highlight-card__icon {
                background: rgba(80, 136, 247, 0.18);
                color: #2757cb;
            }

            .dashboard-highlight-card--success .dashboard-highlight-card__icon {
                background: rgba(46, 204, 113, 0.2);
                color: #19834a;
            }

            .dashboard-highlight-card--info .dashboard-highlight-card__icon {
                background: rgba(3, 195, 236, 0.16);
                color: #0d7488;
            }

            .dashboard-highlight-card--warning .dashboard-highlight-card__icon {
                background: rgba(255, 171, 0, 0.2);
                color: #9f6700;
            }

            .upcoming-delivery-card {
                background: linear-gradient(160deg, #ffffff 0%, #f7fbff 56%, #f4f9ff 100%);
                border: 1px solid rgba(188, 211, 236, 0.58);
            }

            .monthly-contracts-card {
                background:
                    radial-gradient(circle at 8% 6%, rgba(56, 189, 248, 0.12), transparent 34%),
                    radial-gradient(circle at 92% 16%, rgba(99, 102, 241, 0.1), transparent 38%),
                    linear-gradient(156deg, #ffffff 0%, #f8fbff 62%, #f5f8ff 100%);
                border: 1px solid rgba(185, 205, 232, 0.58);
            }

            .monthly-contracts-hero {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }

            .monthly-contracts-hero__eyebrow {
                display: inline-flex;
                align-items: center;
                font-size: 0.72rem;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                font-weight: 700;
                color: #2a4f8f;
            }

            .monthly-kpi-card {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                min-height: 94px;
                border-radius: 1rem;
                border: 1px solid rgba(201, 215, 236, 0.76);
                background: rgba(255, 255, 255, 0.88);
                padding: 0.9rem 1rem;
                box-shadow: 0 10px 20px rgba(30, 62, 102, 0.07);
            }

            .monthly-kpi-card__icon {
                width: 2.55rem;
                height: 2.55rem;
                border-radius: 0.85rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.05rem;
                flex-shrink: 0;
            }

            .monthly-kpi-card__value {
                font-size: 1.35rem;
                line-height: 1;
                font-weight: 800;
                color: #1d3557;
            }

            .monthly-kpi-card__label {
                margin-top: 0.2rem;
                font-size: 0.79rem;
                color: #56708f;
                font-weight: 600;
            }

            .monthly-kpi-card--primary .monthly-kpi-card__icon {
                background: rgba(59, 130, 246, 0.16);
                color: #1d4ed8;
            }

            .monthly-kpi-card--success .monthly-kpi-card__icon {
                background: rgba(16, 185, 129, 0.18);
                color: #047857;
            }

            .monthly-kpi-card--warning .monthly-kpi-card__icon {
                background: rgba(245, 158, 11, 0.2);
                color: #b45309;
            }

            .upcoming-filter-field {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
                padding: 0.8rem 0.9rem;
                border-radius: 0.85rem;
                border: 1px solid rgba(205, 217, 233, 0.74);
                background: rgba(255, 255, 255, 0.84);
            }

            .upcoming-filter-badge {
                background: #e8f0fb;
                color: #264b7f;
                border: 1px solid rgba(135, 168, 209, 0.45);
                font-weight: 600;
            }

            .upcoming-delivery-table thead th {
                font-size: 0.73rem;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #59728f;
                white-space: nowrap;
            }

            @media (max-width: 991.98px) {
                .operations-hero__header {
                    flex-direction: column;
                    align-items: flex-start;
                }
            }

            @media (max-width: 575.98px) {
                .operations-hero__pill {
                    width: 100%;
                    justify-content: center;
                }
            }

            @media (max-width: 575.98px) {
                .meta-chip {
                    width: 100%;
                    justify-content: center;
                }

                .booking-card__image {
                    width: 56px;
                    height: 56px;
                }

                .booking-timeline {
                    flex-direction: column;
                }

                .booking-timeline__divider {
                    flex-direction: row;
                    justify-content: flex-start;
                    gap: 0.5rem;
                    padding: 0;
                }
            }

</style>
