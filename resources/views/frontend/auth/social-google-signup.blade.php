@extends('frontend.layouts.web')

@section('title', __('Complete Google Sign Up'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/auth-pages.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
    <div class="container">
        <div class="columns">
            <div class="column is-12 has-text-centered">
                <h1 class="text-capitalize mb-4 text-lg">@lang('Complete Sign Up')</h1>
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
                        <h2 class="title is-4 mb-2">@lang('Continue with Google')</h2>
                        <p class="has-text-grey">
                            @lang('Confirm the Terms & Conditions to create your BasinEnergy account.')
                        </p>
                    </div>

                    <div class="field">
                        <label class="label">@lang('Google Account')</label>
                        <p>{{ $pending['name'] }} &mdash; {{ $pending['email'] }}</p>
                    </div>

                    <x-forms.post :action="route('frontend.auth.social.signup.store')">
                        <div class="field">
                            <label class="checkbox auth-checkbox">
                                <input type="checkbox" name="terms" value="1" required
                                    {{ old('terms') ? 'checked' : '' }}>
                                <span>
                                    @lang('I agree to the')
                                    <a href="{{ route('frontend.pages.terms') }}" target="_blank" rel="noopener noreferrer">
                                        @lang('Terms & Conditions')
                                    </a>
                                </span>
                            </label>
                            @error('terms')
                                <p class="help is-danger" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="field mt-5">
                            <button type="submit" class="btn btn-main auth-submit">
                                @lang('Create Account with Google')
                            </button>
                        </div>
                        <div class="has-text-centered mt-4">
                            <a href="{{ route('frontend.auth.login') }}" class="auth-link">
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
