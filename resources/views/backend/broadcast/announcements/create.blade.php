@extends('backend.layouts.app')

@section('title', __('Create Announcement'))

@section('content')
<div class="card">
    <div class="card-header"><strong>New announcement</strong></div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.broadcast.announcements.store') }}">
            @csrf
            <div class="form-group">
                <label for="title">Title</label>
                <input id="title" name="title" class="form-control" value="{{ old('title') }}" maxlength="180" required>
            </div>
            <div class="form-group">
                <label for="body">Announcement</label>
                <textarea id="body" name="body" class="form-control" maxlength="10000" rows="9" required>{{ old('body') }}</textarea>
            </div>
            <div class="form-group">
                <label for="audience">Recipients</label>
                <select name="audience" id="audience" class="form-control" required>
                    <option value="all_users" {{ old('audience') === 'all_users' ? 'selected' : '' }}>All regular Users (not administrators)</option>
                    <option value="roles" {{ old('audience') === 'roles' ? 'selected' : '' }}>Select User role(s)</option>
                </select>
            </div>
            <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 h6">User roles (used only when selected above)</legend>
                @forelse ($roles as $role)
                    <label class="d-block">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                            {{ in_array($role->id, old('role_ids', [])) ? 'checked' : '' }}>
                        {{ $role->name }}
                    </label>
                @empty
                    <p class="text-muted">No User roles are configured yet.</p>
                @endforelse
            </fieldset>
            <button class="btn btn-primary" type="submit" onclick="return confirm('Publish to selected recipients now?')">Publish announcement</button>
            <a class="btn btn-link" href="{{ route('admin.broadcast.announcements.index') }}">Cancel</a>
        </form>
    </div>
</div>
@endsection
