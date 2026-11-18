@php
    $item = $announcement ?? new \App\Models\BroadcastAnnouncement();
    $selectedRoles = array_map('intval', old('role_ids', $item->role_ids ?? []));
@endphp
<div class="form-group">
    <label for="{{ $editorPrefix }}-title">Announcement title</label>
    <input id="{{ $editorPrefix }}-title" name="title" maxlength="180" class="form-control"
        placeholder="Important research update" value="{{ old('title', $item->title) }}">
</div>
<div class="form-group">
    <label for="{{ $editorPrefix }}-body">Announcement content</label>
    <textarea id="{{ $editorPrefix }}-body" name="body" rows="12" maxlength="10000"
        class="form-control js-broadcast-rich-editor"
        placeholder="Write here...">{{ old('body', $item->body_format === 'html'
            ? $item->body
            : app(\App\Services\BroadcastRichText::class)->render($item->body, 'plain')) }}</textarea>
    <small class="form-text text-muted">Rich formatting is supported. Images must use external HTTPS URLs.</small>
</div>
<div class="form-group">
    <label for="{{ $editorPrefix }}-audience">Recipients</label>
    <select id="{{ $editorPrefix }}-audience" name="audience" class="form-control">
        <option value="all_users" {{ old('audience', $item->audience ?? 'all_users') === 'all_users' ? 'selected' : '' }}>
            All regular Users (not administrators)
        </option>
        <option value="roles" {{ old('audience', $item->audience) === 'roles' ? 'selected' : '' }}>
            Selected User roles
        </option>
    </select>
</div>
<fieldset class="border rounded p-3 mb-3" data-broadcast-role-options>
    <legend class="w-auto px-2 h6">User roles (only when using Selected User roles)</legend>
    @forelse($roles as $role)
        <label class="d-block mb-2">
            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                {{ in_array($role->id, $selectedRoles, true) ? 'checked' : '' }}>
            {{ $role->name }}
        </label>
    @empty
        <p class="text-muted">No User roles are configured.</p>
    @endforelse
</fieldset>
