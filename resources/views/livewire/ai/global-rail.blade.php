<aside @class(['kara-ai-rail', 'is-open' => $open]) aria-label="Kara AI Copilot">
    <div class="kara-ai-rail__dock">
        <button type="button" class="kara-ai-rail__trigger" wire:click="toggle"
            aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="kara-ai-context-panel">
            <span class="kara-ai-rail__trigger-copy">
                <strong>Kara AI</strong>
                <small>{{ count($presets) ? 'Ready for this page' : 'AI ready' }}</small>
            </span>
            <i class="bx bx-chevron-left kara-ai-rail__chevron" aria-hidden="true"></i>
            <span class="kara-ai-orbit" aria-hidden="true">
                <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-assistant.png') }}" alt="">
            </span>
        </button>
    </div>

    @if($open)
        <section id="kara-ai-context-panel" class="kara-ai-rail__panel" aria-live="polite">
            <header class="kara-ai-rail__header">
                <div class="kara-ai-rail__header-main">
                    <span class="kara-ai-orbit kara-ai-orbit--large" aria-hidden="true">
                        <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-assistant.png') }}" alt="">
                    </span>
                    <div class="kara-ai-rail__header-text">
                        <div class="kara-ai-rail__eyebrow-row">
                            <span class="kara-ai-eyebrow">Page-aware · verified</span>
                            <span class="kara-ai-status"><i></i> Live</span>
                        </div>
                        <h5>{{ $contextTitle }}</h5>
                        <p>{{ $contextDescription }}</p>
                    </div>
                </div>
                <button type="button" class="kara-ai-rail__close" wire:click="toggle" aria-label="Close Kara AI">
                    <i class="bx bx-x"></i>
                </button>
            </header>

            <div class="kara-ai-scope-note">
                <i class="bx bx-shield-quarter"></i>
                <span><strong>Read-only:</strong> Explains verified database records. Never edits or approves.</span>
            </div>

            @if(count($presets))
                <div class="kara-ai-rail__content">
                    @foreach($presets as $preset)
                        <div class="kara-ai-preset-label">
                            <span class="preset-dot"></span>
                            {{ $preset['label'] }}
                            <span class="preset-page-hint">· page-aware</span>
                        </div>
                        <livewire:ai.insight-card
                            :feature="$preset['feature']"
                            :entity-id="$preset['entity_id']"
                            :key="'ai-rail-'.$preset['feature'].'-'.($preset['entity_id'] ?? 'global')" />
                    @endforeach
                </div>
            @else
                <div class="kara-ai-empty">
                    <span class="kara-ai-empty__icon"><i class="bx bx-sparkles"></i></span>
                    <h6>AI is ready</h6>
                    <p>Open any workspace — Dashboard, Contract or Payments — to see a page-aware brief.</p>
                    <a href="{{ route('expert.dashboard') }}" class="kara-rail-cta">Open Dashboard <i class="bx bx-right-arrow-alt"></i></a>
                </div>
            @endif

            <div class="kara-ai-capabilities">
                <span><i class="bx bx-layout"></i>Dashboard</span>
                <span><i class="bx bx-file-blank"></i>Contract</span>
                <span><i class="bx bx-credit-card"></i>Payments</span>
                <span><i class="bx bx-group"></i>Team</span>
            </div>
            <div class="kara-ai-rail__footer">Verified data only · No guesswork</div>
        </section>
    @endif

    @once
        <style>
            .kara-ai-rail{ --kara-red:#ed1c24; --kara-ink:#111214; --kara-border:#eceef2; --kara-muted:#6b7280; --kara-soft:#f8f9fb; position:fixed; inset:0 0 auto auto; z-index:1085; pointer-events:none; }
            /* Dock — minimal, eye-friendly, expand on hover */
            .kara-ai-rail__dock{ pointer-events:auto; position:fixed; right:0; top:46%; width:66px; height:66px; overflow:hidden; border-radius:18px 0 0 18px; background:#fff; border:1px solid var(--kara-border); border-right:0; box-shadow:0 8px 28px rgba(17,20,24,.10); transition:width .32s cubic-bezier(.22,1,.36,1), box-shadow .2s; }
            .kara-ai-rail__dock:hover, .kara-ai-rail__dock:focus-within, .kara-ai-rail.is-open .kara-ai-rail__dock{ width:208px; box-shadow:0 12px 36px rgba(17,20,24,.14); }
            .kara-ai-rail__trigger{ position:absolute; inset:0; width:100%; height:100%; padding:8px 10px 8px 14px; border:0; background:transparent; color:var(--kara-ink); display:flex; align-items:center; cursor:pointer; }
            .kara-ai-rail__trigger:focus{ outline:none; }
            .kara-ai-rail__trigger:focus-visible{ box-shadow:inset 0 0 0 2px var(--kara-red); border-radius:18px 0 0 18px; }
            .kara-ai-rail__trigger-copy{ position:absolute; left:14px; top:50%; transform:translateY(-50%) translateX(6px); opacity:0; display:flex; flex-direction:column; align-items:flex-start; line-height:1.15; white-space:nowrap; transition:opacity .2s ease .06s, transform .28s ease .04s; }
            .kara-ai-rail__dock:hover .kara-ai-rail__trigger-copy, .kara-ai-rail__dock:focus-within .kara-ai-rail__trigger-copy, .kara-ai-rail.is-open .kara-ai-rail__trigger-copy{ opacity:1; transform:translateY(-50%) translateX(0); }
            .kara-ai-rail__trigger-copy strong{ font-size:13.5px; font-weight:750; letter-spacing:-.01em; color:var(--kara-ink); }
            .kara-ai-rail__trigger-copy small{ color:var(--kara-muted); font-size:11px; margin-top:3px; font-weight:600; }
            .kara-ai-rail__chevron{ position:absolute; right:60px; top:50%; transform:translateY(-50%); opacity:0; color:var(--kara-muted); font-size:16px; transition:opacity .2s, transform .25s; }
            .kara-ai-rail__dock:hover .kara-ai-rail__chevron, .kara-ai-rail__dock:focus-within .kara-ai-rail__chevron, .kara-ai-rail.is-open .kara-ai-rail__chevron{ opacity:1; }
            .kara-ai-rail.is-open .kara-ai-rail__chevron{ transform:translateY(-50%) rotate(180deg); }
            .kara-ai-orbit{ position:absolute; right:9px; top:9px; width:46px; height:46px; padding:2px; display:grid; place-items:center; border-radius:13px; isolation:isolate; background:#fff; border:1px solid var(--kara-border); }
            .kara-ai-orbit::before{ content:''; position:absolute; inset:-2px; z-index:-2; border-radius:inherit; background:conic-gradient(from 0deg, transparent 0 30%, var(--kara-red) 50%, #ff8a8e 54%, transparent 70%); opacity:.95; animation:kara-ai-orbit 3.2s linear infinite; }
            .kara-ai-orbit::after{ content:''; position:absolute; inset:0; z-index:-1; border-radius:11px; background:#fff; }
            .kara-ai-orbit img{ width:100%; height:100%; object-fit:contain; border-radius:9px; position:relative; }
            .kara-ai-orbit--large{ position:relative; inset:auto; width:52px; height:52px; flex:0 0 52px; border-radius:14px; }
            .kara-ai-orbit--large::after{ border-radius:12px; }
            /* Panel — clean white, soft shadow, generous whitespace */
            .kara-ai-rail__panel{ pointer-events:auto; position:fixed; right:16px; top:50%; transform:translateY(-50%); width:min(440px, calc(100vw - 24px)); max-height:calc(100vh - 32px); overflow-y:auto; overscroll-behavior:contain; border:1px solid var(--kara-border); border-radius:20px; background:#fff; box-shadow:0 20px 60px rgba(17,20,24,.14), 0 4px 16px rgba(17,20,24,.06); animation:kara-ai-panel-in .32s cubic-bezier(.22,1,.36,1); scrollbar-width:thin; scrollbar-color:#e2e5ea transparent; }
            .kara-ai-rail__panel::-webkit-scrollbar{ width:6px; } .kara-ai-rail__panel::-webkit-scrollbar-thumb{ background:#e2e5ea; border-radius:999px; }
            .kara-ai-rail__header{ position:sticky; top:0; z-index:2; padding:18px 16px; background:rgba(255,255,255,.96); backdrop-filter:blur(10px); border-bottom:1px solid var(--kara-border); border-radius:20px 20px 0 0; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
            .kara-ai-rail__header-main{ display:flex; gap:12px; align-items:flex-start; min-width:0; flex:1; }
            .kara-ai-rail__header-text{ min-width:0; flex:1; }
            .kara-ai-rail__eyebrow-row{ display:flex; align-items:center; gap:8px; margin-bottom:5px; }
            .kara-ai-eyebrow{ color:var(--kara-red); font-size:10px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
            .kara-ai-status{ display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:999px; background:#f0fdf4; border:1px solid #dcfce7; color:#166534; font-size:10.5px; font-weight:700; }
            .kara-ai-status i{ width:6px; height:6px; border-radius:50%; background:#16a34a; box-shadow:0 0 0 4px rgba(22,163,74,.12); animation:kara-ai-pulse 2s ease-out infinite; }
            .kara-ai-rail__header h5{ font-size:14.5px!important; font-weight:750!important; color:var(--kara-ink)!important; margin:0 0 4px!important; letter-spacing:-.015em; line-height:1.25; }
            .kara-ai-rail__header p{ color:var(--kara-muted)!important; font-size:12.5px!important; line-height:1.5!important; margin:0!important; max-width:30ch; }
            .kara-ai-rail__close{ flex:0 0 34px; width:34px; height:34px; border:1px solid var(--kara-border); border-radius:10px; background:#fff; color:var(--kara-muted); display:grid; place-items:center; font-size:18px; cursor:pointer; transition:all .15s; }
            .kara-ai-rail__close:hover{ background:var(--kara-soft); color:var(--kara-ink); border-color:#d1d5db; }
            .kara-ai-scope-note{ margin:12px 12px 0; padding:10px 11px; border-radius:11px; background:#f8fafb; border:1px solid var(--kara-border); color:#4b5563; display:flex; gap:8px; align-items:flex-start; font-size:11.5px; line-height:1.5; }
            .kara-ai-scope-note i{ color:var(--kara-red); font-size:15px; margin-top:1px; flex:0 0 auto; }
            .kara-ai-rail__content{ display:grid; gap:14px; padding:12px; }
            .kara-ai-preset-label{ display:flex; align-items:center; gap:7px; color:var(--kara-muted); font-size:10.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; padding:2px 2px 0; }
            .preset-dot{ width:6px; height:6px; border-radius:50%; background:var(--kara-red); box-shadow:0 0 0 4px rgba(237,28,36,.10); } .preset-page-hint{ font-weight:600; letter-spacing:.02em; text-transform:none; color:#9ca3af; font-size:10px; }
            .kara-ai-empty{ margin:12px; padding:28px 20px; border:1.5px dashed #e5e7eb; border-radius:16px; background:#fff; text-align:center; }
            .kara-ai-empty__icon{ width:48px; height:48px; margin:0 auto 12px; border-radius:14px; background:#fef2f2; color:var(--kara-red); display:grid; place-items:center; font-size:20px; border:1px solid #fee2e2; }
            .kara-ai-empty h6{ font-size:14px; font-weight:750; color:var(--kara-ink); margin:0 0 6px; } .kara-ai-empty p{ color:var(--kara-muted); font-size:12.5px; line-height:1.55; margin:0 0 14px; }
            .kara-rail-cta{ display:inline-flex; align-items:center; gap:5px; background:var(--kara-ink); color:#fff; padding:8px 14px; border-radius:10px; font-size:12.5px; font-weight:700; text-decoration:none; transition:background .15s; } .kara-rail-cta:hover{ background:#000; color:#fff; }
            .kara-ai-capabilities{ margin:0 12px; padding:12px 0; border-top:1px solid var(--kara-border); display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
            .kara-ai-capabilities span{ padding:9px 4px; border-radius:10px; background:var(--kara-soft); border:1px solid var(--kara-border); color:#4b5563; display:flex; flex-direction:column; align-items:center; gap:4px; text-align:center; font-size:10.5px; font-weight:600; line-height:1.2; }
            .kara-ai-capabilities i{ color:var(--kara-red); font-size:16px; }
            .kara-ai-rail__footer{ text-align:center; padding:10px 12px 14px; color:#9ca3af; font-size:10.5px; font-weight:600; letter-spacing:.02em; }
            @keyframes kara-ai-orbit{ to{ transform:rotate(360deg); } }
            @keyframes kara-ai-panel-in{ from{ opacity:0; transform:translate(16px, -50%) scale(.98); } to{ opacity:1; transform:translate(0, -50%) scale(1); } }
            @keyframes kara-ai-pulse{ 0%,100%{ box-shadow:0 0 0 3px rgba(22,163,74,.14); } 50%{ box-shadow:0 0 0 7px rgba(22,163,74,0); } }
            @media (max-width:767.98px){ .kara-ai-rail__dock{ top:auto; bottom:88px; width:64px; height:64px; border-radius:16px 0 0 16px; } .kara-ai-rail__dock:hover{ width:64px; } .kara-ai-rail__trigger-copy, .kara-ai-rail__chevron{ display:none; } .kara-ai-rail__panel{ right:8px; bottom:8px; top:auto; width:calc(100vw - 16px); max-height:calc(100vh - 16px); transform:none; border-radius:18px; animation-name:kara-ai-panel-mobile-in; } }
            @keyframes kara-ai-panel-mobile-in{ from{ opacity:0; transform:translateY(14px); } to{ opacity:1; transform:translateY(0); } }
            @media (prefers-reduced-motion:reduce){ .kara-ai-orbit::before, .kara-ai-status i{ animation:none; } .kara-ai-rail__dock, .kara-ai-rail__panel{ transition:none; animation:none; } }
        </style>
    @endonce
</aside>
