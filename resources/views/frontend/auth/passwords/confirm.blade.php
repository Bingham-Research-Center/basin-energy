@extends('frontend.layouts.web')

@section('title', __('Confirm Password'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/auth-pages.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12">
                <div class="has-text-centered">
                    <h1 class="text-capitalize mb-4 text-lg">@lang('Confirm Password')</h1>
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
                        <h2 class="title is-4 mb-2">@lang('Verify Your Identity')</h2>
                        <p class="has-text-grey">
                            @lang('Please confirm your password before continuing.')
                        </p>
                    </div>

                    <x-forms.post :action="route('frontend.auth.password.confirm')">
                        <div class="field">
                            <label for="password" class="label">@lang('Password')</label>
                            <div class="control">
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="input @error('password') is-danger @enderror"
                                    placeholder="{{ __('Password') }}"
                                    maxlength="100"
                                    required
                                    autofocus
                                    autocomplete="current-password"
                                    @error('password') aria-invalid="true" aria-describedby="confirm-password-error" @enderror
                                />
                            </div>
                            @error('password')
                                <p id="confirm-password-error" class="help is-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="field mt-5">
                            <button class="btn btn-main auth-submit" type="submit">
                                @lang('Confirm Password')
                            </button>
                        </div>

                        <div class="has-text-centered mt-4">
                            <a class="auth-link" href="{{ route('frontend.user.account') }}">
                                @lang('Back to My Account')
                            </a>
                        </div>
                    </x-forms.post>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
