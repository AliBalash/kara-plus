<div wire:poll.30s>
    <section class="website-review-hero mb-4">
        <div>
            <div class="website-review-eyebrow"><i class="bx bx-globe"></i> Website intake</div>
            <h3 class="mb-2">Review requests before they reserve a car</h3>
            <p class="mb-0">Check live vehicle and date conflicts, take ownership, then approve only the requests that are ready.</p>
        </div>
        <div class="website-review-counts" aria-label="Review queue summary">
            <div><strong>{{ $summary['total'] }}</strong><span>In queue</span></div>
            <div><strong>{{ $summary['unassigned'] }}</strong><span>Unassigned</span></div>
            <div><strong>{{ $summary['mine'] }}</strong><span>Mine</span></div>
        </div>
    </section>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label fw-semibold" for="websiteReviewSearch">Find a request</label>
                    <form wire:submit.prevent="applySearch" class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input id="websiteReviewSearch" type="search" class="form-control" wire:model.defer="searchInput"
                            placeholder="Customer, phone, vehicle, plate or reference">
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:target="applySearch">Search</button>
                    </form>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold" for="websiteReviewAssignment">Ownership</label>
                    <select id="websiteReviewAssignment" class="form-select" wire:model.live="assignmentFilter">
                        <option value="all">All requests</option>
                        <option value="unassigned">Unassigned</option>
                        <option value="mine">Assigned to me</option>
                        <option value="others">Assigned to others</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters">
                        <i class="bx bx-reset me-1"></i> Clear filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="website-review-note mb-3">
        <i class="bx bx-info-circle"></i>
        <span>“Ready to approve” is checked live. Approval checks it once more inside a protected transaction, so another booking cannot slip through at the same time.</span>
    </div>

    <div class="row g-4">
        @forelse ($requests as $request)
            @php
                $diagnostic = $diagnosticsById[$request->id];
                $isMine = (int) $request->user_id === $expertId;
                $isOwnedByOther = $request->user_id && ! $isMine;
            @endphp
            <div class="col-12" wire:key="website-review-request-{{ $request->id }}">
                <article class="card website-review-card border-0 shadow-sm {{ $diagnostic['ready'] ? 'website-review-card--ready' : 'website-review-card--attention' }}">
                    <div class="card-body p-0">
                        <div class="website-review-card__top">
                            <div class="d-flex align-items-start gap-3">
                                <div class="website-review-card__avatar">{{ strtoupper(mb_substr($request->customer?->first_name ?? 'W', 0, 1)) }}</div>
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <h5 class="mb-0">{{ $request->customer?->fullName() ?? 'Customer not set' }}</h5>
                                        <span class="badge rounded-pill bg-label-warning text-warning">Website request</span>
                                        @if ($diagnostic['ready'])
                                            <span class="badge rounded-pill bg-label-success text-success"><i class="bx bx-check-circle me-1"></i>Ready to approve</span>
                                        @else
                                            <span class="badge rounded-pill bg-label-danger text-danger"><i class="bx bx-error-circle me-1"></i>Needs attention</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted d-flex flex-wrap gap-3">
                                        <span><i class="bx bx-hash"></i> {{ $request->reference_number ?? 'Request #' . $request->id }}</span>
                                        <span><i class="bx bx-phone"></i> {{ $request->customer?->phone ?? 'No phone' }}</span>
                                        <span><i class="bx bx-time-five"></i> {{ $request->created_at?->format('d M Y, H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="website-review-owner mt-3 mt-lg-0">
                                @if ($isMine)
                                    <span class="badge bg-primary"><i class="bx bx-user-check me-1"></i>Assigned to you</span>
                                @elseif ($isOwnedByOther)
                                    <span class="badge bg-label-secondary text-muted"><i class="bx bx-user me-1"></i>{{ $request->user?->shortName() ?? 'Another expert' }}</span>
                                @else
                                    <span class="badge bg-label-secondary text-muted"><i class="bx bx-user-plus me-1"></i>Unassigned</span>
                                @endif
                            </div>
                        </div>

                        <div class="website-review-card__body">
                            <div class="website-review-facts">
                                <div class="website-review-fact">
                                    <span class="website-review-fact__label">Requested vehicle</span>
                                    <strong>{{ $request->requestedCar?->fullName() ?? $request->car?->fullName() ?? 'Not selected' }}</strong>
                                    @if ($request->requested_car_id && $request->requested_car_id !== $request->car_id)
                                        <small class="text-warning"><i class="bx bx-transfer-alt"></i> Current review vehicle: {{ $request->car?->fullName() ?? 'Not selected' }}</small>
                                    @endif
                                </div>
                                <div class="website-review-fact">
                                    <span class="website-review-fact__label">Rental window</span>
                                    <strong>{{ $request->pickup_date?->format('d M Y, H:i') ?? 'Not set' }}</strong>
                                    <small>to {{ $request->return_date?->format('d M Y, H:i') ?? 'Not set' }}</small>
                                </div>
                                <div class="website-review-fact">
                                    <span class="website-review-fact__label">Quoted total</span>
                                    <strong>AED {{ number_format((float) $request->total_price, 2) }}</strong>
                                    <small>Confirm price in the review form</small>
                                </div>
                            </div>

                            <div class="website-review-diagnostics {{ $diagnostic['ready'] ? 'website-review-diagnostics--ready' : '' }}">
                                @if ($diagnostic['ready'])
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bx bx-check-shield"></i>
                                        <div><strong>No blocking issue found</strong><span>The selected vehicle and time window are currently clear.</span></div>
                                    </div>
                                @else
                                    <div class="website-review-diagnostics__heading"><i class="bx bx-error"></i> Resolve before approval</div>
                                    <ul>
                                        @foreach ($diagnostic['issues'] as $issue)
                                            <li><strong>{{ $issue['title'] }}</strong><span>{{ $issue['detail'] }}</span></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <footer class="website-review-card__actions">
                            @if (! $request->user_id)
                                <button type="button" class="btn btn-outline-primary" wire:click="claim({{ $request->id }})" wire:loading.attr="disabled" wire:target="claim({{ $request->id }})">
                                    <i class="bx bx-user-plus me-1"></i> Claim review
                                </button>
                            @endif

                            <a href="{{ route('rental-requests.edit', $request->id) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-edit-alt me-1"></i> {{ $diagnostic['ready'] ? 'Review details' : 'Review & fix' }}
                            </a>

                            @if ($diagnostic['ready'] && ! $isOwnedByOther)
                                <button type="button" class="btn btn-success ms-sm-auto" wire:click="approveAndOpen({{ $request->id }})" wire:loading.attr="disabled" wire:target="approveAndOpen({{ $request->id }})"
                                    onclick="return window.confirm('Approve this request, assign it to you and open the reservation?')">
                                    <i class="bx bx-check-shield me-1"></i> Approve & open reservation
                                </button>
                            @elseif ($isOwnedByOther)
                                <span class="website-review-card__locked ms-sm-auto"><i class="bx bx-lock-alt"></i> Owned by another expert</span>
                            @endif
                        </footer>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="website-review-empty-icon"><i class="bx bx-check-double"></i></div>
                        <h5 class="mt-3">No website requests to review</h5>
                        <p class="text-muted mb-0">New public reservation requests will appear here automatically.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    @if ($requests->hasPages())
        <div class="d-flex justify-content-center mt-4">{{ $requests->links() }}</div>
    @endif

    @pushOnce('styles', 'website-review-queue-styles')
        <style>
            .website-review-hero { display:flex; justify-content:space-between; gap:2rem; align-items:center; padding:1.75rem 2rem; color:#fff; border-radius:1.25rem; background:linear-gradient(120deg,#172554,#1d4ed8 56%,#0f766e); box-shadow:0 18px 44px rgba(30,64,175,.2); }
            .website-review-hero h3 { color:#fff; font-weight:750; }
            .website-review-hero p { color:rgba(255,255,255,.78); max-width:44rem; }
            .website-review-eyebrow { color:#bfdbfe; text-transform:uppercase; letter-spacing:.09em; font-size:.71rem; font-weight:800; margin-bottom:.5rem; }
            .website-review-counts { display:flex; flex-wrap:wrap; gap:.65rem; }
            .website-review-counts div { min-width:4.8rem; padding:.7rem .85rem; text-align:center; border:1px solid rgba(255,255,255,.18); border-radius:.85rem; background:rgba(255,255,255,.1); }
            .website-review-counts strong,.website-review-counts span { display:block; } .website-review-counts strong { font-size:1.2rem; } .website-review-counts span { color:#dbeafe; font-size:.7rem; }
            .website-review-note { display:flex; gap:.65rem; align-items:flex-start; padding:.75rem 1rem; border:1px solid #bfdbfe; border-radius:.85rem; color:#1e40af; background:#eff6ff; font-size:.86rem; }
            .website-review-note i { font-size:1.1rem; margin-top:.05rem; }
            .website-review-card { overflow:hidden; border-left:4px solid #dc2626!important; } .website-review-card--ready { border-left-color:#16a34a!important; }
            .website-review-card__top { display:flex; justify-content:space-between; padding:1.2rem 1.35rem; border-bottom:1px solid #eef2f7; background:#fcfdff; }
            .website-review-card__avatar { display:grid; flex:0 0 auto; width:2.55rem; height:2.55rem; place-items:center; border-radius:50%; color:#1d4ed8; background:#dbeafe; font-weight:800; }
            .website-review-owner { white-space:nowrap; }
            .website-review-card__body { padding:1.2rem 1.35rem; }
            .website-review-facts { display:grid; grid-template-columns:1.35fr 1fr .7fr; gap:1.25rem; padding-bottom:1.2rem; }
            .website-review-fact { min-width:0; display:flex; flex-direction:column; gap:.2rem; } .website-review-fact strong { overflow-wrap:anywhere; color:#1e293b; }
            .website-review-fact small { color:#64748b; } .website-review-fact__label { color:#64748b; font-size:.71rem; font-weight:800; letter-spacing:.055em; text-transform:uppercase; }
            .website-review-diagnostics { padding:1rem; border:1px solid #fecaca; border-radius:.85rem; background:#fff7f7; color:#991b1b; } .website-review-diagnostics--ready { border-color:#bbf7d0; background:#f0fdf4; color:#166534; }
            .website-review-diagnostics__heading { font-weight:800; margin-bottom:.55rem; } .website-review-diagnostics ul { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:.55rem 1rem; padding:0; margin:0; list-style:none; }
            .website-review-diagnostics li { display:flex; flex-direction:column; gap:.12rem; font-size:.86rem; } .website-review-diagnostics li span,.website-review-diagnostics--ready span { color:#64748b; font-size:.82rem; }
            .website-review-card__actions { display:flex; align-items:center; flex-wrap:wrap; gap:.65rem; padding:1rem 1.35rem; border-top:1px solid #eef2f7; background:#fff; } .website-review-card__locked { color:#64748b; font-size:.84rem; }
            .website-review-empty-icon { display:grid; width:4rem; height:4rem; margin:auto; place-items:center; border-radius:50%; color:#16a34a; background:#dcfce7; font-size:2rem; }
            @media (max-width: 991.98px) { .website-review-hero,.website-review-card__top { align-items:flex-start; flex-direction:column; } .website-review-facts { grid-template-columns:1fr; gap:.9rem; } .website-review-owner { margin-top:0!important; } }
            @media (max-width: 575.98px) { .website-review-hero { padding:1.35rem; gap:1.25rem; } .website-review-counts { width:100%; } .website-review-counts div { flex:1; min-width:0; } .website-review-card__top,.website-review-card__body,.website-review-card__actions { padding-left:1rem; padding-right:1rem; } .website-review-card__actions .btn { width:100%; } .website-review-card__actions .ms-sm-auto { margin-left:0!important; } }
        </style>
    @endPushOnce
</div>
