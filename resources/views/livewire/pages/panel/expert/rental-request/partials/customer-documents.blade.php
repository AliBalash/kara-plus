@php use Illuminate\Support\Str; @endphp
@php $hasCustomerDocs = collect($customerDocuments ?? [])->flatten(1)->isNotEmpty(); @endphp

@if ($hasCustomerDocs)
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h6 class="fw-semibold mb-0">Customer-submitted Documents</h6>
            <span class="text-muted small">Preview or download the files uploaded during onboarding.</span>
        </div>
        <div class="row g-3">
            @foreach ($customerDocuments as $type => $documents)
                @if (! empty($documents))
                    <div class="col-12 col-lg-6 col-xl-3">
                        <div class="border rounded-4 h-100 p-3 shadow-sm bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">{{ Str::of($type)->replace('_', ' ')->title() }}</span>
                                <span class="badge bg-primary-subtle text-primary">{{ count($documents) }}</span>
                            </div>
                            <ul class="list-unstyled mb-0">
                                @foreach ($documents as $document)
                                    <li class="py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="avatar avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                                    <i class="bx {{ $document['is_pdf'] ? 'bx-file' : 'bx-image' }}"></i>
                                                </span>
                                                <div>
                                                    <div class="fw-semibold small">{{ $document['label'] }}</div>
                                                    <div class="text-muted text-truncate" style="max-width: 140px;">{{ $document['filename'] }}</div>
                                                </div>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                @unless ($document['is_pdf'])
                                                    <button type="button" class="btn btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#documentPreviewModal"
                                                        data-preview="{{ $document['url'] }}"
                                                        data-download="{{ $document['url'] }}"
                                                        data-title="{{ Str::of($type)->upper() }} — {{ $document['label'] }}">
                                                        <i class="bx bx-show"></i>
                                                    </button>
                                                @endunless
                                                <a href="{{ $document['url'] }}" class="btn btn-outline-secondary"
                                                    target="_blank" rel="noopener" download>
                                                    <i class="bx bx-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@else
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="bx bx-info-circle me-2"></i>
        <span>No customer documents have been uploaded for this contract.</span>
    </div>
@endif
