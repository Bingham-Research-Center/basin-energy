@extends('backend.layouts.app')

@section('title', $announcement->exists ? __('Announcement') : __('Create Announcement'))

@push('after-styles')
<link rel="stylesheet" href="{{ asset('css/broadcast-editor.css') }}?v=1">
@endpush

@section('content')
<div class="card">
    <div class="card-header"><strong>{{ $announcement->exists ? $announcement->title : 'Create announcement' }}</strong></div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if($announcement->exists && ! $announcement->isEditable())
            <div class="alert alert-info">This announcement has been published and cannot be edited or deleted.</div>
            <div class="broadcast-rich-content border rounded p-3 mb-4">
                {!! app(\App\Services\BroadcastRichText::class)->render($announcement->body, $announcement->body_format) !!}
            </div>
        @else
            <form method="POST"
                action="{{ $announcement->exists ? route('admin.broadcast.announcements.update', $announcement) : route('admin.broadcast.announcements.store') }}">
                @csrf
                @if($announcement->exists) @method('PUT') @endif
                @include('backend.broadcast.announcements.fields', ['editorPrefix' => 'announcement-page'])
                <button type="submit" class="btn btn-outline-primary" name="intent" value="draft">Save Draft</button>
                <button type="submit" class="btn btn-primary" name="intent" value="publish"
                    onclick="return confirm('Publish this announcement now?')">Publish announcement</button>
            </form>
            @if($announcement->exists)
                <hr>
                <form method="POST" action="{{ route('admin.broadcast.announcements.delete', $announcement) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Delete this draft?')">Delete draft</button>
                </form>
            @endif
        @endif
        <a href="{{ route('admin.broadcast.announcements.index') }}" class="btn btn-link">Back to announcements</a>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('js/broadcast-editor.js') }}?v=1" defer></script>
@endpush
