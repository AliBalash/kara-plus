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
