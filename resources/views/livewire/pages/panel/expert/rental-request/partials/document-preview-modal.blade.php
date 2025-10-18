<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentPreviewTitle">Document preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="ratio ratio-4x3 bg-white border rounded-4 overflow-hidden shadow-sm">
                    <img id="documentPreviewImage" src="" alt="Document preview" class="w-100 h-100 object-fit-contain">
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <small class="text-muted">Use download for higher resolution if needed.</small>
                <a id="documentDownloadLink" href="#" target="_blank" rel="noopener" download class="btn btn-primary">
                    <i class="bx bx-download me-1"></i>Download
                </a>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            const previewModal = document.getElementById('documentPreviewModal');
            if (previewModal) {
                previewModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;
                    if (!button) return;

                    const image = previewModal.querySelector('#documentPreviewImage');
                    const downloadLink = previewModal.querySelector('#documentDownloadLink');
                    const title = previewModal.querySelector('#documentPreviewTitle');

                    const previewSrc = button.getAttribute('data-preview');
                    const downloadSrc = button.getAttribute('data-download');
                    const modalTitle = button.getAttribute('data-title') || 'Document preview';

                    if (image && previewSrc) {
                        image.src = previewSrc;
                    }

                    if (downloadLink && downloadSrc) {
                        downloadLink.href = downloadSrc;
                    }

                    if (title) {
                        title.textContent = modalTitle;
                    }
                });

                previewModal.addEventListener('hidden.bs.modal', () => {
                    const image = previewModal.querySelector('#documentPreviewImage');
                    if (image) {
                        image.src = '';
                    }
                });
            }
        </script>
    @endpush
@endonce
