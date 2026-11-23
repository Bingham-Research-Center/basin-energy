@php
    $oldEditId = ctype_digit((string) old('_broadcast_edit_id'))
        ? (int) old('_broadcast_edit_id') : null;
    $oldAction = $oldEditId
        ? route('admin.broadcast.announcements.update', ['announcement' => $oldEditId])
        : route('admin.broadcast.announcements.store');
@endphp
<div class="modal fade broadcast-editor-modal" id="announcementEditorModal"
    data-broadcast-modal="announcement" tabindex="-1" role="dialog"
    data-backdrop="static" data-keyboard="false"
    aria-labelledby="announcementEditorTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h5" id="announcementEditorTitle" data-broadcast-modal-title>Create announcement</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <p class="alert alert-info small" data-broadcast-modal-hint>
                    Save a draft to continue later, or publish now to the selected User roles.
                </p>
                <form id="announcementPopupForm" action="{{ $oldAction }}" method="POST"
                    data-broadcast-editor-form="announcement"
                    data-broadcast-delete-template="{{ route('admin.broadcast.announcements.delete', ['announcement' => '__ID__']) }}"
                    data-create-url="{{ route('admin.broadcast.announcements.store') }}"
                    data-autosave-url="{{ route('admin.broadcast.editor.autosave', ['kind' => 'announcement']) }}"
                    data-image-upload-url="{{ route('admin.broadcast.editor.images') }}">
                    @csrf
                    <input name="_method" type="hidden" value="PUT" data-broadcast-method {{ $oldEditId ? '' : 'disabled' }}>
                    <input name="_broadcast_edit_id" type="hidden" value="{{ $oldEditId }}">
                    @include('backend.broadcast.announcements.fields', [
                        'editorPrefix' => 'announcement-popup',
                        'announcement' => new \App\Models\BroadcastAnnouncement(),
                        'roles' => $roles,
                    ])
                </form>
            </div>
            <div class="px-3 pb-1 small text-muted" role="status" aria-live="polite"
                data-broadcast-save-status>Changes are saved automatically while you work.</div>
            <div class="modal-footer d-flex flex-wrap justify-content-between">
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-danger mr-2"
                        data-broadcast-discard disabled>Discard Draft</button>
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="submit" class="btn btn-outline-primary mr-2"
                        name="intent" value="draft" form="announcementPopupForm"
                        data-broadcast-save-button>Save Draft</button>
                    <button type="submit" class="btn btn-primary" name="intent" value="publish"
                        form="announcementPopupForm" data-broadcast-publish-button
                        data-broadcast-confirm-publish>
                        Publish announcement
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
