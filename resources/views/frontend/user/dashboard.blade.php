@extends('frontend.layouts.app')

@section('title', __('Dashboard'))

@section('content')
    <x-frontend.card>
        <x-slot name="header">
            @lang('Welcome :Name', ['name' => $logged_in_user->name])
        </x-slot>

        <x-slot name="body">
            <p>Welcome to the Dashboard!!!</p>
        </x-slot>
    </x-frontend.card>
@endsection
