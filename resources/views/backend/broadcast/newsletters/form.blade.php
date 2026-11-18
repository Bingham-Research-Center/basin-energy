@extends('backend.layouts.app')

@section('title', $newsletter->exists ? __('Newsletter') : __('New Newsletter'))

@section('content')
<div class="card">
    <div class="card-header"><strong>{{ $newsletter->exists ? 'Newsletter — '.$newsletter->title : 'Create monthly newsletter' }}</strong></div>
    <div class="card-body">
        @if ($newsletter->exists && ! $newsletter->isEditable())
            <div class="alert alert-info">Delivery has started or completed. This issue is read-only.</div>
        @elseif ($newsletter->exists && $newsletter->status === 'scheduled')
            <div class="alert alert-warning">This issue is scheduled. Editing will return it to Draft and require scheduling again.</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if (! $newsletter->exists || $newsletter->isEditable())
            <form method="POST" action="{{ $newsletter->exists ? route('admin.broadcast.newsletters.update', $newsletter) : route('admin.broadcast.newsletters.store') }}">
                @csrf
                @if ($newsletter->exists) @method('PUT') @endif

                <div class="form-group">
                    <label for="issue_month">Issue month</label>
                    <input id="issue_month" name="issue_month" type="month" class="form-control"
                        value="{{ old('issue_month', optional($newsletter->issue_month)->format('Y-m')) }}" required>
                    <small class="form-text text-muted">One newsletter per calendar month.</small>
                </div>
                <div class="form-group">
                    <label for="title">Newsletter title</label>
                    <input id="title" name="title" class="form-control" maxlength="180"
                        value="{{ old('title', $newsletter->title) }}" required>
                </div>
                <div class="form-group">
                    <label for="body">Content</label>
                    <textarea id="body" name="body" class="form-control" rows="14" maxlength="30000"
                        required>{{ old('body', $newsletter->body) }}</textarea>
                    <small class="form-text text-muted">Plain text with line breaks. HTML is escaped for email safety.</small>
                </div>
                <button type="submit" class="btn btn-primary">Save draft</button>
            </form>
        @else
            <p><strong>Month:</strong> {{ $newsletter->issue_month->format('F Y') }}</p>
            <h4>{{ $newsletter->title }}</h4>
            <div class="border p-3 mb-3" style="white-space: pre-line">{{ $newsletter->body }}</div>
        @endif

        @if ($newsletter->exists && $newsletter->isEditable())
            <hr>
            <form method="POST" class="d-inline" action="{{ route('admin.broadcast.newsletters.schedule', $newsletter) }}">
                @csrf
                <button type="submit" class="btn btn-success" onclick="return confirm('Approve this issue for month-end delivery?')">
                    {{ $newsletter->status === 'scheduled' ? 'Keep scheduled / approve' : 'Schedule for month end' }}
                </button>
            </form>
            <form method="POST" class="d-inline ml-2" action="{{ route('admin.broadcast.newsletters.delete', $newsletter) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this issue?')">Delete</button>
            </form>
        @endif
        <a href="{{ route('admin.broadcast.newsletters.index') }}" class="btn btn-link">Back to all newsletters</a>
    </div>
</div>
@endsection
