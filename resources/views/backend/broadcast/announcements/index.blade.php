@extends('backend.layouts.app')

@section('title', __('Announcements'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>@lang('Broadcast / Announcements')</strong>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.broadcast.announcements.create') }}">Create announcement</a>
    </div>
    <div class="card-body">
        <p class="text-muted">Published announcements are shown in recipients' dashboards and emailed to the selected User roles. The original audience is retained for auditing.</p>
        <div class="brc-admin-data-table">
            <livewire:backend.broadcast-announcements-table />
        </div>
    </div>
</div>
@endsection
