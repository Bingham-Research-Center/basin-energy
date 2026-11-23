@extends('backend.layouts.app')

@section('title', $newsletter->exists ? __('Newsletter') : __('New Newsletter'))

@push('after-styles')
<link rel="stylesheet" href="{{ asset('css/broadcast-editor.css') }}?v=1">
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <strong>{{ $newsletter->exists ? 'Newsletter — '.$newsletter->title : 'Create monthly newsletter' }}</strong>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if($newsletter->exists && ! $newsletter->isEditable())
            <div class="alert alert-info">This newsletter has started sending or has been sent. It cannot be edited or deleted.</div>
            <p><strong>Issue:</strong> {{ $newsletter->issue_month->format('F Y') }}</p>
            <h4>{{ $newsletter->title }}</h4>
            <div class="broadcast-rich-content border rounded p-3 mb-4">
                {!! app(\App\Services\BroadcastRichText::class)->render($newsletter->body, $newsletter->body_format) !!}
            </div>
        @else
            @if($newsletter->exists && $newsletter->status === 'scheduled')
                <div class="alert alert-warning">Editing a scheduled issue returns it to Draft and requires approval again.</div>
            @endif
            <form action="{{ $newsletter->exists ? route('admin.broadcast.newsletters.update', $newsletter) : route('admin.broadcast.newsletters.store') }}"
                method="POST">
                @csrf
                @if($newsletter->exists) @method('PUT') @endif
                @include('backend.broadcast.newsletters.fields', ['editorPrefix' => 'newsletter-page'])
                <button type="submit" class="btn btn-primary">Save Draft</button>
            </form>
            @if($newsletter->exists)
                <hr>
                <form method="POST" action="{{ route('admin.broadcast.newsletters.schedule', $newsletter) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success"
                        onclick="return confirm('Schedule this newsletter for month-end delivery?')">Schedule newsletter</button>
                </form>
                <form method="POST" action="{{ route('admin.broadcast.newsletters.delete', $newsletter) }}" class="d-inline ml-2">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Delete this newsletter?')">Delete</button>
                </form>
            @endif
        @endif
        <a href="{{ route('admin.broadcast.newsletters.index') }}" class="btn btn-link">Back to newsletters</a>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('js/broadcast-editor.js') }}?v=1" defer></script>
@endpush
