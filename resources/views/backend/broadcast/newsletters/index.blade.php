@extends('backend.layouts.app')

@section('title', __('Newsletters'))

@push('after-styles')
<link rel="stylesheet" href="{{ asset('css/broadcast-editor.css') }}?v=2">
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>@lang('Broadcast / Newsletters')</strong>
        <a href="{{ route('admin.broadcast.newsletters.create') }}" class="btn btn-primary btn-sm"
            data-broadcast-create="newsletter" data-toggle="modal" data-target="#newsletterEditorModal">
            <i class="c-icon cil-plus mr-1"></i>Create newsletter
        </a>
    </div>
    <div class="card-body">
        <p class="text-muted">Create monthly issues in the rich-text editor. Drafts can be reopened and edited at any time.
            <strong>Only explicitly scheduled issues are sent</strong> on the final day of the month at 6:00 PM Mountain time.
            Months without a scheduled issue have no delivery.</p>
        <div class="brc-admin-data-table">
            <livewire:backend.broadcast-newsletters-table />
        </div>
    </div>
</div>
@include('backend.broadcast.newsletters.modal')
@endsection

@push('after-scripts')
<script src="{{ asset('js/broadcast-editor.js') }}?v=2" defer></script>
@if($errors->any())
<script>window.brcBroadcastReopen = 'newsletter';</script>
@endif
@endpush
