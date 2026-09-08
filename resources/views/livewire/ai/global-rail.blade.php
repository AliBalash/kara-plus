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
                <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-assistant-light.png') }}" alt="Kara AI">
            </span>
        </button>
    </div>

    @if($open)
        <section id="kara-ai-context-panel" class="kara-ai-rail__panel" aria-live="polite">
            <header class="kara-ai-rail__header">
                <div class="kara-ai-rail__header-main">
                    <span class="kara-ai-orbit kara-ai-orbit--large" aria-hidden="true">
                        <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-assistant-light.png') }}" alt="Kara AI">
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
            /* Kara AI — premium light assistant, no black, no red glow */
            .kara-ai-rail{ --kara-red:#ed1c24; --kara-ink:#0f172a; --kara-border:#eef2f7; --kara-muted:#64748b; --kara-soft:#f8fafc; position:fixed; inset:0 0 auto auto; z-index:1085; pointer-events:none; }
            /* Dock — premium floating card, glass + soft shadow */
            .kara-ai-rail__dock{ pointer-events:auto; position:fixed; right:0; top:44%; width:70px; height:70px; overflow:hidden; border-radius:20px 0 0 20px; background:rgba(255,255,255,.92); backdrop-filter:blur(14px) saturate(1.2); border:1px solid rgba(15,23,42,.06); border-right:0; box-shadow:0 10px 30px rgba(15,23,42,.08), 0 4px 12px rgba(15,23,42,.06); transition:width .34s cubic-bezier(.22,1,.36,1), box-shadow .25s ease, transform .25s ease; }
            .kara-ai-rail__dock:hover, .kara-ai-rail__dock:focus-within, .kara-ai-rail.is-open .kara-ai-rail__dock{ width:216px; box-shadow:0 16px 40px rgba(15,23,42,.12), 0 6px 18px rgba(15,23,42,.08); transform:translateX(-1px); }
            .kara-ai-rail__trigger{ position:absolute; inset:0; width:100%; height:100%; padding:10px 12px 10px 16px; border:0; background:transparent; color:var(--kara-ink); display:flex; align-items:center; cursor:pointer; }
            .kara-ai-rail__trigger:focus{ outline:none; }
            .kara-ai-rail__trigger:focus-visible{ box-shadow:inset 0 0 0 2px var(--kara-red); border-radius:20px 0 0 20px; }
            .kara-ai-rail__trigger-copy{ position:absolute; left:16px; top:50%; transform:translateY(-50%) translateX(8px); opacity:0; display:flex; flex-direction:column; align-items:flex-start; line-height:1.15; white-space:nowrap; transition:opacity .22s ease .06s, transform .30s cubic-bezier(.22,1,.36,1) .04s; }
            .kara-ai-rail__dock:hover .kara-ai-rail__trigger-copy, .kara-ai-rail__dock:focus-within .kara-ai-rail__trigger-copy, .kara-ai-rail.is-open .kara-ai-rail__trigger-copy{ opacity:1; transform:translateY(-50%) translateX(0); }
            .kara-ai-rail__trigger-copy strong{ font-size:14px; font-weight:800; letter-spacing:-.02em; color:var(--kara-ink); display:flex; align-items:center; gap:6px; }
            .kara-ai-rail__trigger-copy strong::before{ content:''; width:6px; height:6px; border-radius:50%; background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.15); }
            .kara-ai-rail__trigger-copy small{ color:var(--kara-muted); font-size:11px; margin-top:4px; font-weight:600; letter-spacing:.01em; }
            .kara-ai-rail__chevron{ position:absolute; right:62px; top:50%; transform:translateY(-50%); opacity:0; color:#94a3b8; font-size:15px; transition:opacity .2s ease, transform .28s cubic-bezier(.22,1,.36,1); }
            .kara-ai-rail__dock:hover .kara-ai-rail__chevron, .kara-ai-rail__dock:focus-within .kara-ai-rail__chevron, .kara-ai-rail.is-open .kara-ai-rail__chevron{ opacity:1; }
            .kara-ai-rail.is-open .kara-ai-rail__chevron{ transform:translateY(-50%) rotate(180deg); color:var(--kara-ink); }
            /* AI mark — pure light card, no black, no animation */
            .kara-ai-orbit{ position:absolute; right:11px; top:50%; transform:translateY(-50%); width:44px; height:44px; padding:4px; display:grid; place-items:center; border-radius:13px; background:#fff; border:1px solid #f1f5f9; box-shadow:0 2px 8px rgba(15,23,42,.06), 0 1px 2px rgba(15,23,42,.04); transition:transform .2s ease, box-shadow .2s ease; }
            .kara-ai-rail__dock:hover .kara-ai-orbit{ transform:translateY(-50%) scale(1.03); box-shadow:0 4px 14px rgba(15,23,42,.08); }
            .kara-ai-orbit img{ width:100%; height:100%; object-fit:contain; border-radius:8px; display:block; }
            .kara-ai-orbit--large{ position:relative; inset:auto; transform:none; width:48px; height:48px; flex:0 0 48px; border-radius:13px; border:1px solid #f1f5f9; box-shadow:0 4px 12px rgba(15,23,42,.07); background:#fff; }
            .kara-ai-orbit--large img{ border-radius:9px; }
            /* Panel — premium white sheet */
            .kara-ai-rail__panel{ pointer-events:auto; position:fixed; right:16px; top:50%; transform:translateY(-50%); width:min(440px, calc(100vw - 24px)); max-height:calc(100vh - 28px); overflow-y:auto; overscroll-behavior:contain; border:1px solid var(--kara-border); border-radius:22px; background:#fff; box-shadow:0 24px 64px rgba(15,23,42,.14), 0 8px 24px rgba(15,23,42,.08); animation:kara-ai-panel-in .32s cubic-bezier(.22,1,.36,1); scrollbar-width:thin; scrollbar-color:#e2e8f0 transparent; }
            .kara-ai-rail__panel::-webkit-scrollbar{ width:6px; } .kara-ai-rail__panel::-webkit-scrollbar-thumb{ background:#e2e8f0; border-radius:999px; }
            .kara-ai-rail__header{ position:sticky; top:0; z-index:2; padding:18px 16px; background:rgba(255,255,255,.94); backdrop-filter:blur(12px); border-bottom:1px solid #f1f5f9; border-radius:22px 22px 0 0; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
            .kara-ai-rail__header-main{ display:flex; gap:12px; align-items:flex-start; min-width:0; flex:1; }
            .kara-ai-rail__header-text{ min-width:0; flex:1; }
            .kara-ai-rail__eyebrow-row{ display:flex; align-items:center; gap:8px; margin-bottom:5px; }
            .kara-ai-eyebrow{ color:var(--kara-red); font-size:10px; font-weight:800; letter-spacing:.09em; text-transform:uppercase; display:flex; align-items:center; gap:5px; }
            .kara-ai-eyebrow::before{ content:'✦'; color:var(--kara-red); font-size:10px; }
            .kara-ai-status{ display:inline-flex; align-items:center; gap:6px; padding:4px 9px; border-radius:999px; background:#f0fdf4; border:1px solid #dcfce7; color:#166534; font-size:10.5px; font-weight:700; letter-spacing:.01em; }
            .kara-ai-status i{ width:6px; height:6px; border-radius:50%; background:#10b981; box-shadow:0 0 0 4px rgba(16,185,129,.14); }
            .kara-ai-rail__header h5{ font-size:14.5px!important; font-weight:800!important; color:var(--kara-ink)!important; margin:0 0 4px!important; letter-spacing:-.02em; line-height:1.25; }
            .kara-ai-rail__header p{ color:var(--kara-muted)!important; font-size:12.5px!important; line-height:1.55!important; margin:0!important; max-width:32ch; }
            .kara-ai-rail__close{ flex:0 0 36px; width:36px; height:36px; border:1px solid #f1f5f9; border-radius:11px; background:#fff; color:var(--kara-muted); display:grid; place-items:center; font-size:18px; cursor:pointer; transition:all .16s ease; box-shadow:0 1px 3px rgba(15,23,42,.04); }
            .kara-ai-rail__close:hover{ background:#f8fafc; color:var(--kara-ink); border-color:#e2e8f0; transform:translateY(-1px); box-shadow:0 4px 10px rgba(15,23,42,.06); }
            .kara-ai-scope-note{ margin:12px 12px 0; padding:11px 12px; border-radius:12px; background:#f8fafc; border:1px solid #f1f5f9; color:#475569; display:flex; gap:9px; align-items:flex-start; font-size:11.5px; line-height:1.55; }
            .kara-ai-scope-note i{ color:var(--kara-red); font-size:15px; margin-top:1px; flex:0 0 auto; }
            .kara-ai-rail__content{ display:grid; gap:14px; padding:12px; }
            .kara-ai-preset-label{ display:flex; align-items:center; gap:7px; color:#64748b; font-size:10.5px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; padding:2px 2px 0; }
            .preset-dot{ width:7px; height:7px; border-radius:50%; background:var(--kara-red); box-shadow:0 0 0 4px rgba(237,28,36,.08); } .preset-page-hint{ font-weight:600; letter-spacing:.02em; text-transform:none; color:#94a3b8; font-size:10px; }
            .kara-ai-empty{ margin:12px; padding:28px 20px; border:1.5px dashed #e2e8f0; border-radius:16px; background:#fff; text-align:center; }
            .kara-ai-empty__icon{ width:48px; height:48px; margin:0 auto 12px; border-radius:14px; background:#fff1f2; color:var(--kara-red); display:grid; place-items:center; font-size:20px; border:1px solid #ffe4e6; }
            .kara-ai-empty h6{ font-size:14px; font-weight:800; color:var(--kara-ink); margin:0 0 6px; } .kara-ai-empty p{ color:var(--kara-muted); font-size:12.5px; line-height:1.6; margin:0 0 14px; }
            .kara-rail-cta{ display:inline-flex; align-items:center; gap:6px; background:var(--kara-ink); color:#fff; padding:9px 16px; border-radius:11px; font-size:12.5px; font-weight:700; text-decoration:none; transition:all .18s ease; box-shadow:0 4px 12px rgba(15,23,42,.12); } .kara-rail-cta:hover{ background:#1e293b; color:#fff; transform:translateY(-1px); box-shadow:0 8px 20px rgba(15,23,42,.16); }
            .kara-ai-capabilities{ margin:0 12px; padding:12px 0; border-top:1px solid #f1f5f9; display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
            .kara-ai-capabilities span{ padding:10px 4px; border-radius:11px; background:#f8fafc; border:1px solid #f1f5f9; color:#475569; display:flex; flex-direction:column; align-items:center; gap:4px; text-align:center; font-size:10.5px; font-weight:600; line-height:1.2; transition:all .15s; }
            .kara-ai-capabilities span:hover{ background:#fff; border-color:#e2e8f0; transform:translateY(-1px); box-shadow:0 2px 8px rgba(15,23,42,.04); }
            .kara-ai-capabilities i{ color:var(--kara-red); font-size:16px; }
            .kara-ai-rail__footer{ text-align:center; padding:10px 12px 14px; color:#94a3b8; font-size:10.5px; font-weight:600; letter-spacing:.02em; }
            @keyframes kara-ai-panel-in{ from{ opacity:0; transform:translate(16px, -50%) scale(.99); } to{ opacity:1; transform:translate(0, -50%) scale(1); } }
            @media (max-width:767.98px){ .kara-ai-rail__dock{ top:auto; bottom:88px; width:64px; height:64px; border-radius:16px 0 0 16px; } .kara-ai-rail__dock:hover{ width:64px; } .kara-ai-rail__trigger-copy, .kara-ai-rail__chevron{ display:none; } .kara-ai-rail__panel{ right:8px; bottom:8px; top:auto; width:calc(100vw - 16px); max-height:calc(100vh - 16px); transform:none; border-radius:18px; animation-name:kara-ai-panel-mobile-in; } }
            @keyframes kara-ai-panel-mobile-in{ from{ opacity:0; transform:translateY(14px); } to{ opacity:1; transform:translateY(0); } }
            @media (prefers-reduced-motion:reduce){ .kara-ai-rail__dock, .kara-ai-rail__panel{ transition:none; animation:none; } }
        </style>
    @endonce
</aside>
