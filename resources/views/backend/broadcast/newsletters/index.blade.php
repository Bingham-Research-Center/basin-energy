@extends('backend.layouts.app')

@section('title', __('Newsletters'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>@lang('Broadcast / Newsletters')</strong>
        <a href="{{ route('admin.broadcast.newsletters.create') }}" class="btn btn-primary btn-sm">Create newsletter</a>
    </div>
    <div class="card-body">
        <p class="text-muted">Prepare monthly newsletters, then explicitly schedule them for delivery on the final day of the selected month at 6:00 PM Mountain time. Once sending begins, newsletters cannot be edited or deleted.</p>
        <div class="brc-admin-data-table">
            <livewire:backend.broadcast-newsletters-table />
        </div>
    </div>
</div>
@endsection
