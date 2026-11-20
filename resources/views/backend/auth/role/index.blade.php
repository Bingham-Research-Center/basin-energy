@extends('backend.layouts.app')

@section('title', __('Role Management'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('css/role-default-switch.css') }}?v=1">
@endpush

@section('content')
    <x-backend.card>
        <x-slot name="header">
            @lang('Role Management')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link
                icon="c-icon cil-plus"
                class="card-header-action"
                :href="route('admin.auth.role.create')"
                :text="__('Create Role')"
            />
        </x-slot>

        <x-slot name="body">
            <p class="small text-muted mb-3">
                @lang('The default User role is assigned automatically to newly registered users, including Google sign-ups. Select one role; switching it off leaves no default. Existing accounts are unchanged.')
            </p>
            <div class="brc-admin-data-table">
            <livewire:backend.roles-table />
            </div>
        </x-slot>
    </x-backend.card>
@endsection

@push('after-scripts')
    <script src="{{ asset('js/role-default-switch.js') }}?v=1"></script>
@endpush
