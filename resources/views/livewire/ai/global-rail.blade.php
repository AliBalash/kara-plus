<aside @class(['kara-ai-rail', 'is-open' => $open]) aria-label="Kara AI Copilot">
    <div class="kara-ai-rail__dock">
        <button type="button" class="kara-ai-rail__trigger" wire:click="toggle"
            wire:loading.attr="disabled" wire:target="toggle"
            aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="kara-ai-context-panel">
            <span class="kara-ai-rail__trigger-copy">
                <strong>Kara AI</strong>
                <small>{{ $contextTitle }}</small>
            </span>
            <i class="bx bx-chevron-left kara-ai-rail__chevron" aria-hidden="true"></i>
            <span class="kara-ai-orbit" aria-hidden="true">
                <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-logo.jpg') }}" alt="">
            </span>
        </button>
    </div>

    @if($open)
        <section id="kara-ai-context-panel" class="kara-ai-rail__panel" aria-live="polite">
            <header class="kara-ai-rail__header">
                <div class="kara-ai-rail__header-main">
                    <span class="kara-ai-orbit kara-ai-orbit--large" aria-hidden="true">
                        <img src="{{ asset('assets/panel/assets/img/ai/kara-ai-logo.jpg') }}" alt="">
                    </span>
                    <div class="kara-ai-rail__header-text">
                        <h5>Kara AI · {{ $contextTitle }}</h5>
                    </div>
                </div>
                <button type="button" class="kara-ai-rail__close" wire:click="toggle" aria-label="Close Kara AI">
                    <i class="bx bx-x"></i>
                </button>
            </header>

            @if(count($presets))
                <div class="kara-ai-rail__content">
                    @if($showSaveReminder)
                        <p class="text-muted small mb-0"><i class="bx bx-info-circle me-1" aria-hidden="true"></i>Save changes before refreshing AI analysis.</p>
                    @endif
                    @foreach($presets as $preset)
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

        </section>
    @endif

    @once
        <style>
            /* Kara AI — light assistant with the Kara Plus mark */
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
            .kara-ai-orbit{ position:absolute; right:11px; top:50%; transform:translateY(-50%); width:46px; height:46px; padding:2px; display:grid; place-items:center; border-radius:15px; background:#fff; border:1px solid #ffe4e6; box-shadow:0 5px 13px rgba(15,23,42,.11), 0 2px 4px rgba(237,28,36,.12); transition:transform .25s ease, box-shadow .25s ease; animation:kara-ai-mark-breathe 4s ease-in-out infinite; }
            .kara-ai-rail__dock:hover .kara-ai-orbit,.kara-ai-rail__dock:focus-within .kara-ai-orbit{ transform:translateY(-52%) scale(1.07) rotate(-3deg); box-shadow:0 9px 20px rgba(15,23,42,.15), 0 3px 8px rgba(237,28,36,.15); animation:none; }
            .kara-ai-orbit img{ width:100%; height:100%; object-fit:cover; border-radius:11px; display:block; }
            .kara-ai-orbit--large{ position:relative; inset:auto; transform:none; width:50px; height:50px; flex:0 0 50px; border-radius:15px; box-shadow:0 5px 14px rgba(15,23,42,.1); animation:none; }
            .kara-ai-orbit--large img{ border-radius:11px; }
            @keyframes kara-ai-mark-breathe{ 0%,100%{ box-shadow:0 5px 13px rgba(15,23,42,.11), 0 2px 4px rgba(237,28,36,.12); } 50%{ box-shadow:0 8px 18px rgba(15,23,42,.14), 0 3px 9px rgba(237,28,36,.2); } }
            /* Panel — premium white sheet */
            .kara-ai-rail__panel{ pointer-events:auto; position:fixed; right:16px; top:50%; transform:translateY(-50%); width:min(440px, calc(100vw - 24px)); max-height:calc(100vh - 28px); overflow-y:auto; overscroll-behavior:contain; border:1px solid var(--kara-border); border-radius:22px; background:#fff; box-shadow:0 24px 64px rgba(15,23,42,.14), 0 8px 24px rgba(15,23,42,.08); animation:kara-ai-panel-in .32s cubic-bezier(.22,1,.36,1); scrollbar-width:thin; scrollbar-color:#e2e8f0 transparent; }
            .kara-ai-rail__panel::-webkit-scrollbar{ width:6px; } .kara-ai-rail__panel::-webkit-scrollbar-thumb{ background:#e2e8f0; border-radius:999px; }
            .kara-ai-rail__header{ position:sticky; top:0; z-index:2; padding:14px 16px; background:rgba(255,255,255,.94); backdrop-filter:blur(12px); border-bottom:1px solid #f1f5f9; border-radius:22px 22px 0 0; display:flex; align-items:center; justify-content:space-between; gap:12px; }
            .kara-ai-rail__header-main{ display:flex; gap:12px; align-items:flex-start; min-width:0; flex:1; }
            .kara-ai-rail__header-text{ min-width:0; flex:1; }
            .kara-ai-rail__header h5{ font-size:14.5px!important; font-weight:800!important; color:var(--kara-ink)!important; margin:0!important; letter-spacing:-.02em; line-height:1.25; }
            .kara-ai-rail__close{ flex:0 0 36px; width:36px; height:36px; border:1px solid #f1f5f9; border-radius:11px; background:#fff; color:var(--kara-muted); display:grid; place-items:center; font-size:18px; cursor:pointer; transition:all .16s ease; box-shadow:0 1px 3px rgba(15,23,42,.04); }
            .kara-ai-rail__close:hover{ background:#f8fafc; color:var(--kara-ink); border-color:#e2e8f0; transform:translateY(-1px); box-shadow:0 4px 10px rgba(15,23,42,.06); }
            .kara-ai-rail__content{ display:grid; gap:14px; padding:12px; }
            .kara-ai-empty{ margin:12px; padding:28px 20px; border:1.5px dashed #e2e8f0; border-radius:16px; background:#fff; text-align:center; }
            .kara-ai-empty__icon{ width:48px; height:48px; margin:0 auto 12px; border-radius:14px; background:#fff1f2; color:var(--kara-red); display:grid; place-items:center; font-size:20px; border:1px solid #ffe4e6; }
            .kara-ai-empty h6{ font-size:14px; font-weight:800; color:var(--kara-ink); margin:0 0 6px; } .kara-ai-empty p{ color:var(--kara-muted); font-size:12.5px; line-height:1.6; margin:0 0 14px; }
            .kara-rail-cta{ display:inline-flex; align-items:center; gap:6px; background:var(--kara-ink); color:#fff; padding:9px 16px; border-radius:11px; font-size:12.5px; font-weight:700; text-decoration:none; transition:all .18s ease; box-shadow:0 4px 12px rgba(15,23,42,.12); } .kara-rail-cta:hover{ background:#1e293b; color:#fff; transform:translateY(-1px); box-shadow:0 8px 20px rgba(15,23,42,.16); }
            @keyframes kara-ai-panel-in{ from{ opacity:0; transform:translate(16px, -50%) scale(.99); } to{ opacity:1; transform:translate(0, -50%) scale(1); } }
            @media (max-width:767.98px){ .kara-ai-rail__dock{ top:auto; bottom:88px; width:64px; height:64px; border-radius:16px 0 0 16px; } .kara-ai-rail__dock:hover,.kara-ai-rail__dock:focus-within,.kara-ai-rail.is-open .kara-ai-rail__dock{ width:64px; transform:none; } .kara-ai-rail__trigger-copy, .kara-ai-rail__chevron{ display:none; } .kara-ai-rail__panel{ right:8px; bottom:8px; top:auto; width:calc(100vw - 16px); max-height:calc(100vh - 16px); transform:none; border-radius:18px; animation-name:kara-ai-panel-mobile-in; } }
            @keyframes kara-ai-panel-mobile-in{ from{ opacity:0; transform:translateY(14px); } to{ opacity:1; transform:translateY(0); } }
            @media (prefers-reduced-motion:reduce){ .kara-ai-rail__dock, .kara-ai-rail__panel, .kara-ai-orbit{ transition:none!important; animation:none!important; } }
        </style>
    @endonce
</aside>
