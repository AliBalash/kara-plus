<section class="card border-0 kara-ai-card" wire:init="load" aria-live="polite">
    @php
        $featureCopy = [
            'contract_brief' => ['title' => 'Contract 360', 'subtitle' => 'Everything about this request — customer, lifecycle, vehicle, docs & payments'],
            'dashboard_operations' => ['title' => 'Today’s operations', 'subtitle' => 'Actionable priorities for you — overdue, pending, and today’s schedule'],
            'payment_queue' => ['title' => 'Payment priorities', 'subtitle' => 'What needs your approval next — oldest & largest first'],
            'changes_since_login' => ['title' => 'Team changes', 'subtitle' => 'Business activity since your last login — no technical noise'],
        ][$feature] ?? ['title' => \Illuminate\Support\Str::headline($feature), 'subtitle' => 'Verified operational insight'];
        $generated = $generatedAt ? \Illuminate\Support\Carbon::parse($generatedAt) : null;
        $expires = $expiresAt ? \Illuminate\Support\Carbon::parse($expiresAt) : null;
    @endphp

    <div class="card-body position-relative">
        <div class="kara-ai-card__header">
            <div class="kara-ai-card__head-text">
                <div class="kara-ai-card__eyebrow"><i class="bx bx-sparkles"></i> Kara AI · verified</div>
                <h6 class="kara-ai-card__title">{{ $featureCopy['title'] }}</h6>
                <p class="kara-ai-card__subtitle">{{ $featureCopy['subtitle'] }}</p>
            </div>
            <div class="kara-ai-card__head-meta">
                @if($cached)
                    <span class="kara-ai-cache-badge" title="Facts unchanged, reused valid cache"><i class="bx bx-check-circle"></i> Up to date</span>
                @else
                    <span class="kara-ai-live-badge"><span class="live-dot"></span> Live</span>
                @endif
            </div>
        </div>

        @if($state === 'idle' || $state === 'loading')
            <div class="kara-ai-card__loading">
                <span class="kara-ai-card__loader" aria-hidden="true"></span>
                <div><strong>Reading this workspace…</strong><small>Collecting verified facts from the database.</small></div>
            </div>
        @elseif($state === 'disabled')
            <div class="kara-ai-card__message"><i class="bx bx-power-off"></i><div><strong>Assistant is disabled</strong><small>Enable KARA_AI_ENABLED to use it.</small></div></div>
        @elseif($state === 'unavailable')
            <div class="kara-ai-card__message"><i class="bx bx-cloud-lightning"></i><div><strong>Insight is temporarily unavailable</strong><small>Panel workflows are unaffected.</small></div></div>
            <button class="btn btn-sm kara-btn-outline mt-3" wire:click="load" wire:loading.attr="disabled">Try again</button>
        @elseif($state === 'busy')
            <div class="kara-ai-card__message"><i class="bx bx-loader-circle bx-spin"></i><div><strong>Already preparing</strong><small>Check again shortly — duplicate call skipped.</small></div></div>
            <button class="btn btn-sm kara-btn-outline mt-3" wire:click="load" wire:loading.attr="disabled">Check again</button>
        @else
            @if($feature === 'contract_brief' && isset($meta['score']))
                @php($pulseTone = $meta['score'] >= 85 ? 'success' : ($meta['score'] >= 60 ? 'warning' : 'danger'))
                <div class="kara-ai-pulse-card is-{{ $pulseTone }}">
                    <div class="kara-ai-pulse-card__score">{{ $meta['score'] }}<span>/100</span></div>
                    <div class="kara-ai-pulse-card__text"><strong>Contract pulse · {{ $meta['label'] }}</strong><small>{{ $meta['issues_count'] }} item(s) need your eye</small></div>
                    <i class="bx bx-pulse pulse-icon"></i>
                </div>
            @endif

            <div class="kara-ai-card__answer">
                <h6>{{ $insight['headline'] ?? 'Operational brief' }}</h6>
                <p>{{ $insight['summary'] ?? 'No summary was returned.' }}</p>
            </div>

            @foreach([
                'critical_alerts' => ['Critical', 'danger', 'bx-error-circle'],
                'watchlist' => ['Review today', 'warning', 'bx-time-five'],
                'positive_signals' => ['Positive', 'success', 'bx-check-circle'],
                'data_quality_warnings' => ['Data quality', 'secondary', 'bx-data'],
            ] as $field => [$label, $tone, $icon])
                @if(!empty($insight[$field]))
                    <div class="kara-ai-signal-group">
                        <div class="kara-ai-signal-group__title is-{{ $tone }}"><i class="bx {{ $icon }}"></i>{{ $label }} · {{ count($insight[$field]) }}</div>
                        @foreach($insight[$field] as $alert)
                            @php($fact = collect($facts)->firstWhere('fact_id', $alert['fact_id'] ?? null))
                            <article class="kara-ai-signal is-{{ $tone }}">
                                <div class="signal-head">
                                    <strong>{{ $alert['title'] ?? 'Review item' }}</strong>
                                    @if(($fact['metrics']['event_count'] ?? null) && $fact['metrics']['event_count'] > 1)
                                        <span class="signal-count">{{ $fact['metrics']['event_count'] }}</span>
                                    @endif
                                </div>
                                @if(!empty($alert['reason']))
                                    <p>{{ $alert['reason'] }}</p>
                                @endif
                                @if($fact['evidence_url'] ?? false)
                                    <a href="{{ $fact['evidence_url'] }}" class="signal-link">{{ $alert['check_now'] ?? 'Open verified record' }} <i class="bx bx-right-arrow-alt"></i></a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            @endforeach

            @if(!empty($insight['insufficient_data']))
                <div class="kara-ai-data-note"><i class="bx bx-info-circle"></i><span>Some checks were skipped — not enough panel data yet.</span></div>
            @endif

            @if(empty($insight['critical_alerts']) && empty($insight['watchlist']) && empty($insight['positive_signals']))
                <div class="kara-ai-empty-brief"><i class="bx bx-check-shield"></i><span>All clear — no urgent items for this workspace.</span></div>
            @endif

            <div class="kara-ai-freshness">
                <div class="freshness-row">
                    <i class="bx bx-history"></i>
                    <div>
                        <strong>{{ $cached ? 'Reused — facts unchanged' : 'Fresh from live database' }}</strong>
                        <small>
                            @if($generated)
                                {{ $generated->diffForHumans() }}@if($expires) · valid until {{ $expires->format('H:i') }}@endif
                            @else
                                Valid for {{ max(1, (int) ceil(config('ai.cache_ttl') / 60)) }} min
                            @endif
                        </small>
                    </div>
                </div>
            </div>

            <div class="kara-ai-card__actions">
                <div class="action-left">
                    <button class="kara-btn-primary" wire:click="load" wire:loading.attr="disabled" wire:target="load,regenerate">
                        <span wire:loading.remove wire:target="load"><i class="bx bx-refresh me-1"></i>Check latest</span>
                        <span wire:loading wire:target="load"><i class="bx bx-loader-alt bx-spin me-1"></i>Checking…</span>
                    </button>
                    <button class="kara-btn-ghost" wire:click="regenerate" wire:loading.attr="disabled" wire:target="load,regenerate" title="New wording with same facts">
                        <span wire:loading.remove wire:target="regenerate"><i class="bx bx-sparkles me-1"></i>Rephrase</span>
                        <span wire:loading wire:target="regenerate"><i class="bx bx-loader-alt bx-spin me-1"></i>…</span>
                    </button>
                </div>
                <div class="action-right">
                    <span class="feedback-label">Helpful?</span>
                    @if($feedbackHelpful === null)
                        <button class="kara-btn-icon" wire:click="feedback(true)" aria-label="Useful"><i class="bx bx-like"></i></button>
                        <button class="kara-btn-icon" wire:click="feedback(false)" aria-label="Not useful"><i class="bx bx-dislike"></i></button>
                    @else
                        <span class="feedback-thanks"><i class="bx bx-check"></i> Thanks</span>
                    @endif
                </div>
            </div>
        @endif

        <div class="kara-ai-card__overlay" wire:loading.flex wire:target="load,regenerate">
            <span class="kara-ai-card__loader" aria-hidden="true"></span>
            <strong>Updating insight…</strong>
        </div>
    </div>

    @once
        <style>
            .kara-ai-card{ --kara-red:#ed1c24; --kara-ink:#111214; --kara-muted:#6b7280; --kara-border:#eef0f4; --kara-soft:#f8f9fb; --kara-success:#16a34a; --kara-warning:#d97706; --radius:20px; border:1px solid var(--kara-border)!important; border-radius:var(--radius)!important; background:#fff!important; box-shadow:0 4px 24px rgba(17,20,24,.06)!important; overflow:hidden; transition:box-shadow .2s ease; }
            .kara-ai-card:hover{ box-shadow:0 8px 32px rgba(17,20,24,.08)!important; }
            .kara-ai-card .card-body{ padding:18px 18px 16px!important; }
            .kara-ai-card__header{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:16px; }
            .kara-ai-card__eyebrow{ display:inline-flex; align-items:center; gap:6px; color:var(--kara-red); font-size:10.5px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; margin-bottom:6px; }
            .kara-ai-card__eyebrow i{ font-size:12px; }
            .kara-ai-card__title{ font-size:15px!important; font-weight:750!important; color:var(--kara-ink)!important; margin:0 0 3px!important; letter-spacing:-.01em; }
            .kara-ai-card__subtitle{ color:var(--kara-muted)!important; font-size:12.5px!important; line-height:1.5!important; margin:0!important; max-width:32ch; }
            .kara-ai-cache-badge, .kara-ai-live-badge{ display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; border:1px solid; }
            .kara-ai-cache-badge{ background:#f0fdf4; color:#15803d; border-color:#dcfce7; }
            .kara-ai-live-badge{ background:#fff1f2; color:#be123c; border-color:#ffe4e6; }
            .kara-ai-live-badge .live-dot{ width:7px; height:7px; border-radius:50%; background:var(--kara-red); box-shadow:0 0 0 4px rgba(237,28,36,.12); animation:kara-pulse 1.8s infinite; }
            .kara-ai-card__loading,.kara-ai-card__message{ display:flex; align-items:center; gap:14px; padding:16px; border-radius:14px; background:var(--kara-soft); border:1px solid var(--kara-border); color:#1f2328; }
            .kara-ai-card__loading strong,.kara-ai-card__message strong{ font-size:13px; }
            .kara-ai-card__loading small,.kara-ai-card__message small{ display:block; margin-top:3px; color:var(--kara-muted); font-size:12px; line-height:1.4; }
            .kara-ai-card__message>i{ font-size:22px; color:var(--kara-red); flex:0 0 auto; }
            .kara-ai-card__loader{ width:22px; height:22px; border:2.5px solid rgba(237,28,36,.14); border-top-color:var(--kara-red); border-radius:50%; animation:kara-spin .75s linear infinite; flex:0 0 22px; }
            .kara-ai-pulse-card{ display:flex; align-items:center; gap:14px; padding:14px 16px; border-radius:14px; background:linear-gradient(135deg, #fff 0%, #f8fafb 100%); border:1px solid var(--kara-border); position:relative; overflow:hidden; margin-bottom:16px; }
            .kara-ai-pulse-card::before{ content:''; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--kara-muted); }
            .kara-ai-pulse-card.is-success::before{ background:var(--kara-success); } .kara-ai-pulse-card.is-warning::before{ background:var(--kara-warning); } .kara-ai-pulse-card.is-danger::before{ background:var(--kara-red); }
            .kara-ai-pulse-card__score{ min-width:64px; font-size:26px; font-weight:850; color:var(--kara-ink); letter-spacing:-.02em; line-height:1; }
            .kara-ai-pulse-card__score span{ font-size:11px; color:var(--kara-muted); font-weight:600; margin-left:1px; }
            .kara-ai-pulse-card__text strong{ display:block; font-size:13px; color:var(--kara-ink); } .kara-ai-pulse-card__text small{ display:block; font-size:12px; color:var(--kara-muted); margin-top:2px; }
            .kara-ai-pulse-card .pulse-icon{ margin-left:auto; font-size:18px; opacity:.18; }
            .kara-ai-card__answer{ background:var(--kara-soft); border:1px solid var(--kara-border); border-radius:14px; padding:14px 15px; margin-bottom:14px; }
            .kara-ai-card__answer h6{ font-size:14px!important; font-weight:750!important; color:var(--kara-ink)!important; margin:0 0 6px!important; line-height:1.35; }
            .kara-ai-card__answer p{ margin:0!important; color:#343840!important; font-size:13px!important; line-height:1.65!important; }
            .kara-ai-signal-group{ margin-top:14px; }
            .kara-ai-signal-group__title{ display:flex; align-items:center; gap:6px; font-size:11px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; margin-bottom:8px; }
            .kara-ai-signal-group__title.is-danger{ color:var(--kara-red); } .kara-ai-signal-group__title.is-warning{ color:var(--kara-warning); } .kara-ai-signal-group__title.is-success{ color:var(--kara-success); } .kara-ai-signal-group__title.is-secondary{ color:#6b7280; }
            .kara-ai-signal{ background:#fff; border:1px solid var(--kara-border); border-radius:12px; padding:12px 13px; margin-bottom:8px; position:relative; transition:border-color .15s, box-shadow .15s; }
            .kara-ai-signal:hover{ border-color:#e2e5ea; box-shadow:0 2px 10px rgba(0,0,0,.04); }
            .kara-ai-signal.is-danger{ border-left:3px solid var(--kara-red); } .kara-ai-signal.is-warning{ border-left:3px solid var(--kara-warning); } .kara-ai-signal.is-success{ border-left:3px solid var(--kara-success); } .kara-ai-signal.is-secondary{ border-left:3px solid #d1d5db; }
            .kara-ai-signal .signal-head{ display:flex; align-items:center; justify-content:space-between; gap:8px; }
            .kara-ai-signal strong{ font-size:13px; color:var(--kara-ink); line-height:1.35; font-weight:700; }
            .signal-count{ background:var(--kara-ink); color:#fff; font-size:11px; font-weight:800; padding:2px 7px; border-radius:999px; min-width:22px; text-align:center; }
            .kara-ai-signal.is-success .signal-count{ background:var(--kara-success); } .kara-ai-signal.is-warning .signal-count{ background:var(--kara-warning); } .kara-ai-signal.is-danger .signal-count{ background:var(--kara-red); }
            .kara-ai-signal p{ margin:5px 0 0; color:#5b6070; font-size:12.5px; line-height:1.55; }
            .signal-link{ display:inline-flex; align-items:center; gap:3px; margin-top:7px; color:var(--kara-ink)!important; font-size:12px; font-weight:700; text-decoration:none; border-bottom:1px solid transparent; }
            .signal-link:hover{ border-bottom-color:var(--kara-ink); } .signal-link i{ font-size:14px; }
            .kara-ai-data-note{ display:flex; gap:8px; align-items:flex-start; background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:10px; padding:10px 12px; font-size:12.5px; line-height:1.5; margin-top:12px; }
            .kara-ai-empty-brief{ display:flex; gap:10px; align-items:center; background:#f0fdf4; border:1px solid #dcfce7; color:#166534; border-radius:12px; padding:12px 14px; font-size:13px; font-weight:600; margin-top:12px; }
            .kara-ai-empty-brief i{ font-size:18px; }
            .kara-ai-freshness{ margin-top:14px; background:#fff; border:1px solid var(--kara-border); border-radius:12px; padding:12px 13px; }
            .freshness-row{ display:flex; gap:10px; align-items:flex-start; }
            .freshness-row>i{ color:var(--kara-muted); font-size:16px; margin-top:1px; }
            .freshness-row strong{ display:block; font-size:12.5px; color:#1f2328; } .freshness-row small{ display:block; color:var(--kara-muted); font-size:11.5px; margin-top:2px; }
            .kara-ai-card__actions{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; padding-top:14px; border-top:1px solid var(--kara-border); flex-wrap:wrap; }
            .action-left{ display:flex; gap:8px; } .action-right{ display:flex; align-items:center; gap:8px; }
            .kara-btn-primary{ display:inline-flex; align-items:center; justify-content:center; gap:4px; background:var(--kara-ink); color:#fff; border:1px solid var(--kara-ink); border-radius:11px; padding:8px 14px; font-size:12.5px; font-weight:700; cursor:pointer; transition:background .15s, transform .1s; }
            .kara-btn-primary:hover{ background:#000; transform:translateY(-1px); } .kara-btn-primary:active{ transform:none; }
            .kara-btn-ghost{ display:inline-flex; align-items:center; gap:4px; background:#fff; color:var(--kara-ink); border:1px solid var(--kara-border); border-radius:11px; padding:8px 13px; font-size:12.5px; font-weight:600; cursor:pointer; }
            .kara-btn-ghost:hover{ background:var(--kara-soft); }
            .kara-btn-outline{ border-radius:10px; padding:7px 12px; font-weight:600; }
            .kara-btn-icon{ width:32px; height:32px; display:grid; place-items:center; background:#fff; border:1px solid var(--kara-border); border-radius:9px; color:var(--kara-muted); cursor:pointer; transition:all .15s; }
            .kara-btn-icon:hover{ background:var(--kara-soft); color:var(--kara-ink); border-color:#d1d5db; }
            .feedback-label{ font-size:12px; color:var(--kara-muted); font-weight:600; } .feedback-thanks{ display:inline-flex; align-items:center; gap:4px; color:var(--kara-success); font-size:12.5px; font-weight:700; }
            .kara-ai-card__overlay{ position:absolute; inset:0; z-index:5; background:rgba(255,255,255,.82); backdrop-filter:blur(4px); display:none; flex-direction:column; align-items:center; justify-content:center; gap:10px; border-radius:var(--radius); color:#1f2328; font-size:13px; font-weight:600; }
            @keyframes kara-spin{ to{ transform:rotate(360deg); } } @keyframes kara-pulse{ 0%,100%{ transform:scale(1); opacity:1; } 50%{ transform:scale(1.15); opacity:.7; } }
            @media (max-width:640px){ .kara-ai-card .card-body{ padding:16px!important; } .kara-ai-card__actions{ flex-direction:column; align-items:stretch; } .action-left,.action-right{ justify-content:space-between; } }
            @media (prefers-reduced-motion:reduce){ .kara-ai-card__loader,.live-dot{ animation:none!important; } }
        </style>
    @endonce
</section>
