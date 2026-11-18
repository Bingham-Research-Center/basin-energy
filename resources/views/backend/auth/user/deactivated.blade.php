@extends('backend.layouts.app')

@section('title', __('Deactivated Users'))

@section('breadcrumb-links')
    @include('backend.auth.user.includes.breadcrumb-links')
@endsection

@section('content')
    <x-backend.card>
        <x-slot name="header">
            @lang('Deactivated Users')
        </x-slot>

        <x-slot name="body">
            <div class="brc-admin-data-table">
            <livewire:backend.users-table status="deactivated" />
            </div>
        </x-slot>
    </x-backend.card>
@endsection
