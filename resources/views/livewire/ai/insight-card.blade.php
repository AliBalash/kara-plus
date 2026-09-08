<section class="card shadow-sm border-0 kara-ai-card" wire:init="load" aria-live="polite">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div><div class="text-primary small fw-bold text-uppercase"><i class="bx bx-sparkles me-1"></i>Kara AI</div><h6 class="mb-0">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $feature)) }}</h6></div>
            @if($cached)<span class="badge bg-label-secondary">Cached</span>@endif
        </div>
        @if($state === 'idle' || $state === 'loading')
            <div class="placeholder-glow"><span class="placeholder col-8 mb-2"></span><span class="placeholder col-11 mb-2"></span><span class="placeholder col-6"></span></div>
        @elseif($state === 'disabled')
            <p class="text-muted small mb-0">AI Copilot is disabled for this environment.</p>
        @elseif($state === 'unavailable')
            <div class="text-muted small">Insights are temporarily unavailable. Core operations are unaffected.</div>
            <button class="btn btn-sm btn-outline-primary mt-3" wire:click="load">Try again</button>
        @elseif($state === 'busy')
            <div class="text-muted small">An identical insight is already being prepared. Try again in a moment.</div>
            <button class="btn btn-sm btn-outline-primary mt-3" wire:click="load">Check again</button>
        @else
            @if($feature === 'contract_brief' && isset($meta['score']))
                <div class="d-flex align-items-center gap-3 rounded-3 bg-light p-3 mb-3"><div class="fs-3 fw-bold text-primary">{{ $meta['score'] }}/100</div><div><div class="fw-semibold">Contract Pulse · {{ $meta['label'] }}</div><div class="small text-muted">{{ $meta['issues_count'] }} verified item(s) deserve review</div></div></div>
            @endif
            <div class="fw-semibold mb-1">{{ $insight['headline'] }}</div><p class="text-muted small mb-3">{{ $insight['summary'] }}</p>
            @foreach(['critical_alerts' => 'Critical', 'watchlist' => 'Review today', 'positive_signals' => 'Positive'] as $field => $label)
                @if(!empty($insight[$field]))
                    <div class="mb-2"><div class="small text-uppercase text-muted fw-semibold mb-1">{{ $label }}</div>
                    @foreach($insight[$field] as $alert)
                        @php($fact = collect($facts)->firstWhere('fact_id', $alert['fact_id']))
                        <div class="border-start border-3 border-primary ps-2 mb-2"><div class="small fw-semibold">{{ $alert['title'] }}</div><div class="small text-muted">{{ $alert['reason'] }}</div>@if($fact['evidence_url'] ?? false)<a class="small" href="{{ $fact['evidence_url'] }}">{{ $alert['check_now'] }}</a>@endif</div>
                    @endforeach</div>
                @endif
            @endforeach
            <button class="btn btn-sm btn-outline-primary mt-2" wire:click="load" wire:loading.attr="disabled"><span wire:loading.remove wire:target="load">Refresh</span><span wire:loading wire:target="load">Refreshing…</span></button>
        @endif
    </div>
</section>
