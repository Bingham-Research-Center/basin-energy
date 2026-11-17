@extends('frontend.layouts.web')

@section('title', __('Two-Factor Verification'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/auth-pages.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12 has-text-centered">
                <h1 class="text-capitalize mb-4 text-lg">@lang('Two-Factor Verification')</h1>
            </div>
        </div>
    </div>
</section>
<section class="section auth-page">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-10-tablet is-7-desktop is-5-widescreen">
                <div class="auth-card">
                    <div class="has-text-centered mb-5">
                        <h2 class="title is-4 mb-2">@lang('Verify Your Identity')</h2>
                        <p class="has-text-grey">@lang('Enter the six-digit code from your authenticator app.')</p>
                    </div>
                    <x-forms.post :action="route('frontend.auth.2fa.challenge.store')">
                        <div class="field">
                            <label class="label" for="code">@lang('Authentication Code')</label>
                            <div class="control">
                                <input id="code" name="code" type="text" class="input @error('code') is-danger @enderror"
                                    inputmode="numeric" pattern="[0-9]{6}" maxlength="6" minlength="6"
                                    autocomplete="one-time-code" required autofocus
                                    @error('code') aria-invalid="true" aria-describedby="code-error" @enderror>
                            </div>
                            @error('code')
                                <p id="code-error" class="help is-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field">
                            <label class="checkbox auth-checkbox">
                                <input name="remember_device" type="checkbox" value="1">
                                <span>@lang('Remember this device')</span>
                            </label>
                        </div>
                        <div class="field mt-5">
                            <button type="submit" class="btn btn-main auth-submit">@lang('Verify Code')</button>
                        </div>
                    </x-forms.post>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
