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
                        <h2 class="title is-4 mb-2">@lang('Forgot Your Password?')</h2>
                        <p class="has-text-grey">
                            @lang('Enter your e-mail address and we will send you a password reset link.')
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="notification is-success is-light" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <x-forms.post :action="route('frontend.auth.password.email')">
                        <div class="field">
                            <label for="email" class="label">@lang('E-mail Address')</label>
                            <div class="control">
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="input @error('email') is-danger @enderror"
                                    placeholder="{{ __('E-mail Address') }}"
                                    value="{{ old('email') }}"
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

                        <div class="field mt-5">
                            <button class="btn btn-main auth-submit" type="submit">
                                @lang('Send Password Reset Link')
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
