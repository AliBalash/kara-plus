<aside class="kara-ai-rail" aria-label="Kara AI Copilot">
    <button type="button" class="kara-ai-rail__trigger shadow" wire:click="toggle" aria-expanded="{{ $open ? 'true' : 'false' }}">
        <i class="bx bx-sparkles"></i><span>Kara AI</span>
    </button>
    @if($open)
        <div class="kara-ai-rail__panel shadow-lg">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div><div class="text-primary small fw-bold text-uppercase">Read-only Copilot</div><div class="fw-semibold">Operations focus</div></div>
                <button type="button" class="btn-close" wire:click="toggle" aria-label="Close Kara AI"></button>
            </div>
            <div class="d-grid gap-3">
                <livewire:ai.insight-card feature="dashboard_operations" :key="'ai-rail-dashboard'" />
                <livewire:ai.insight-card feature="changes_since_login" :key="'ai-rail-changes'" />
            </div>
        </div>
    @endif
</aside>

@once
    <style>
        .kara-ai-rail { position: fixed; right: 1.25rem; bottom: 1.5rem; z-index: 1080; }
        .kara-ai-rail__trigger { border: 0; border-radius: 999px; background: #696cff; color: #fff; padding: .75rem 1rem; display: inline-flex; gap: .5rem; align-items: center; font-weight: 600; }
        .kara-ai-rail__panel { width: min(27rem, calc(100vw - 2rem)); max-height: min(46rem, calc(100vh - 7rem)); overflow-y: auto; position: absolute; right: 0; bottom: 3.5rem; border-radius: 1rem; background: #fff; padding: 1rem; border: 1px solid rgba(105,108,255,.15); }
        @media (max-width: 576px) { .kara-ai-rail { right: .75rem; bottom: .75rem; } .kara-ai-rail__trigger span { display: none; } }
    </style>
@endonce
