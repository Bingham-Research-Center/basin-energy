@extends('backend.layouts.app')

@section('title', __('Announcements'))

@push('after-styles')
<link rel="stylesheet" href="{{ asset('css/broadcast-editor.css') }}?v=4">
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>@lang('Broadcast / Announcements')</strong>
        <a href="{{ route('admin.broadcast.announcements.create') }}" class="btn btn-primary btn-sm"
            data-broadcast-create="announcement" data-toggle="modal" data-target="#announcementEditorModal">
            <i class="c-icon cil-plus mr-1"></i>Create announcement
        </a>
    </div>
    <div class="card-body">
        <p class="text-muted">Save announcements as drafts and continue later. Publishing freezes the target User groups,
            adds the announcement to recipients' dashboards and queues email delivery.</p>
        <div class="brc-admin-data-table" data-broadcast-progress-table>
            <livewire:backend.broadcast-announcements-table />
        </div>
    </div>
</div>
@include('backend.broadcast.announcements.modal')
@endsection

@push('after-scripts')
<script src="{{ asset('js/broadcast-editor.js') }}?v=5" defer></script>
<script src="{{ asset('js/broadcast-delivery-progress.js') }}?v=1" defer></script>
@if($errors->any())
<script>window.brcBroadcastReopen = 'announcement';</script>
@endif
@endpush
