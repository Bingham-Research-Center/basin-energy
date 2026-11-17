@extends('frontend.layouts.web')

@section('title', __('Reset Password'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/auth-pages.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">@lang('Reset Password')</h1>
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
                        <h2 class="title is-4 mb-2">@lang('Create a New Password')</h2>
                        <p class="has-text-grey">
                            @lang('Enter a new password for your Basin Energy account.')
                        </p>
                    </div>

                    <x-forms.post :action="route('frontend.auth.password.update')">
                        <input type="hidden" name="token" value="{{ $token }}" />
                        @error('token')
                            <div class="notification is-danger is-light" role="alert">{{ $message }}</div>
                        @enderror

                        <div class="field">
                            <label for="email" class="label">@lang('E-mail Address')</label>
                            <div class="control">
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="input @error('email') is-danger @enderror"
                                    placeholder="{{ __('E-mail Address') }}"
                                    value="{{ old('email', $email ?? '') }}"
                                    maxlength="255"
                                    required
                                    autofocus
                                    autocomplete="email"
                                    @error('email') aria-invalid="true" aria-describedby="reset-email-error" @enderror
                                />
                            </div>
                            @error('email')
                                <p class="help is-danger" id="reset-email-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="password" class="label">@lang('New Password')</label>
                            <div class="control">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="input @error('password') is-danger @enderror"
                                    placeholder="{{ __('New Password') }}"
                                    maxlength="100"
                                    required
                                    autocomplete="new-password"
                                    @error('password') aria-invalid="true" aria-describedby="reset-password-error" @enderror
                                />
                            </div>
                            @error('password')
                                <p class="help is-danger" id="reset-password-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="password_confirmation" class="label">@lang('Confirm New Password')</label>
                            <div class="control">
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    class="input"
                                    placeholder="{{ __('Confirm New Password') }}"
                                    maxlength="100"
                                    required
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>

                        <div class="field mt-5">
                            <button class="btn btn-main auth-submit" type="submit">
                                @lang('Reset Password')
                            </button>
                        </div>

                        <div class="has-text-centered mt-4">
                            <a class="auth-link" href="{{ route('frontend.auth.login') }}">
                                @lang('Back to Login')
                            </a>
                        </div>
                    </x-forms.post>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
