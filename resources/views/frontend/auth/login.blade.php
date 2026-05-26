@extends('frontend.layouts.web')

@section('title', __('Login'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/auth-pages.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">@lang('Login')</h1>
                </div>
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
                        <h2 class="title is-4 mb-2">@lang('Welcome Back')</h2>
                        <p class="has-text-grey">
                            @lang('Sign in to access your Basin Energy account.')
                        </p>
                    </div>

                    <x-forms.post :action="route('frontend.auth.login')">
                        <div class="field">
                            <label for="email" class="label">@lang('E-mail Address')</label>
                            <div class="control">
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="input"
                                    placeholder="{{ __('E-mail Address') }}"
                                    value="{{ old('email') }}"
                                    maxlength="255"
                                    required
                                    autofocus
                                    autocomplete="email"
                                />
                            </div>
                        </div>

                        <div class="field">
                            <label for="password" class="label">@lang('Password')</label>
                            <div class="control">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="input"
                                    placeholder="{{ __('Password') }}"
                                    maxlength="100"
                                    required
                                    autocomplete="current-password"
                                />
                            </div>
                        </div>

                        <div class="field">
                            <label class="checkbox auth-checkbox">
                                <input
                                    name="remember"
                                    id="remember"
                                    type="checkbox"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <span>@lang('Remember Me')</span>
                            </label>
                        </div>

                        @if(config('boilerplate.access.captcha.login'))
                            <div class="field">
                                @captcha
                                <input type="hidden" name="captcha_status" value="true" />
                            </div>
                        @endif

                        <div class="field mt-5">
                            <button class="btn btn-main auth-submit" type="submit">
                                @lang('Login')
                            </button>
                        </div>

                        <div class="has-text-centered mt-4">
                            <x-utils.link
                                :href="route('frontend.auth.password.request')"
                                class="auth-link"
                                :text="__('Forgot Your Password?')" />
                        </div>

                        <div class="has-text-centered mt-5">
                            @include('frontend.auth.includes.social')
                        </div>
                    </x-forms.post>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection