@php
    $oldEditId = ctype_digit((string) old('_broadcast_edit_id'))
        ? (int) old('_broadcast_edit_id') : null;
    $oldAction = $oldEditId
        ? route('admin.broadcast.newsletters.update', ['newsletter' => $oldEditId])
        : route('admin.broadcast.newsletters.store');
@endphp
<div class="modal fade broadcast-editor-modal" id="newsletterEditorModal"
    data-broadcast-modal="newsletter" tabindex="-1" role="dialog"
    data-backdrop="static" data-keyboard="false"
    aria-labelledby="newsletterEditorTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="newsletterEditorTitle" data-broadcast-modal-title>Create monthly newsletter</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <p class="alert alert-info small" data-broadcast-modal-hint>
                    Save an unfinished draft now, then reopen and schedule it when you are ready. Drafts never send automatically.
                </p>
                <form id="newsletterPopupForm" action="{{ $oldAction }}" method="POST"
                    data-broadcast-editor-form="newsletter"
                    data-create-url="{{ route('admin.broadcast.newsletters.store') }}"
                    data-autosave-url="{{ route('admin.broadcast.editor.autosave', ['kind' => 'newsletter']) }}"
                    data-image-upload-url="{{ route('admin.broadcast.editor.images') }}">
                    @csrf
                    <input name="_method" type="hidden" value="PUT" data-broadcast-method {{ $oldEditId ? '' : 'disabled' }}>
                    <input name="_broadcast_edit_id" type="hidden" value="{{ $oldEditId }}">
                    @include('backend.broadcast.newsletters.fields', ['editorPrefix' => 'newsletter-popup', 'newsletter' => new \App\Models\BroadcastNewsletter()])
                </form>
            </div>
            <div class="px-3 pb-1 small text-muted" role="status" aria-live="polite"
                data-broadcast-save-status>Changes are saved automatically while you work.</div>
            <div class="modal-footer d-flex flex-wrap justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                <div class="d-flex flex-wrap align-items-center">
                    <form method="POST" action="#" data-broadcast-schedule-form class="mr-2 d-none">
                        @csrf
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('Schedule this newsletter for the end of its month?')">
                            Schedule newsletter
                        </button>
                    </form>
                    <button type="submit" class="btn btn-primary" form="newsletterPopupForm"
                        data-broadcast-save-button>Save Draft</button>
                </div>
            </div>
        </div>
    </div>
</div>
