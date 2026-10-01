@extends('frontend.layouts.app')

@section('title', __('My Account'))

@section('content')
<div class="dashboard-page">
    <div class="card dashboard-hero mb-4">
        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <div class="dashboard-eyebrow">@lang('User settings')</div>
                <h1 class="dashboard-title mb-1">@lang('My Account')</h1>
                <p class="dashboard-subtitle">@lang('Manage your profile, password, and two-factor authentication settings.')</p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="dashboard-status-pill dashboard-status-pill-success">
                    <i class="c-icon cil-check-circle"></i> @lang('Active account')
                </span>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card dashboard-panel-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <strong>@lang('Account details')</strong>
                    <span class="text-muted small">{{ $logged_in_user->email }}</span>
                </div>

                <div class="card-body">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <x-utils.link
                                :text="__('My Profile')"
                                class="nav-link active"
                                id="my-profile-tab"
                                data-toggle="pill"
                                href="#my-profile"
                                role="tab"
                                aria-controls="my-profile"
                                aria-selected="true" />

                            <x-utils.link
                                :text="__('Edit Information')"
                                class="nav-link"
                                id="information-tab"
                                data-toggle="pill"
                                href="#information"
                                role="tab"
                                aria-controls="information"
                                aria-selected="false"/>

                            @if (! $logged_in_user->isSocial())
                                <x-utils.link
                                    :text="__('Password')"
                                    class="nav-link"
                                    id="password-tab"
                                    data-toggle="pill"
                                    href="#password"
                                    role="tab"
                                    aria-controls="password"
                                    aria-selected="false" />
                            @endif

                            <x-utils.link
                                :text="__('Two Factor Authentication')"
                                class="nav-link"
                                id="two-factor-authentication-tab"
                                data-toggle="pill"
                                href="#two-factor-authentication"
                                role="tab"
                                aria-controls="two-factor-authentication"
                                aria-selected="false"/>
                        </div>
                    </nav>

                    <div class="tab-content" id="my-profile-tabsContent">
                        <div class="tab-pane fade pt-4 show active" id="my-profile" role="tabpanel" aria-labelledby="my-profile-tab">
                            @include('frontend.user.account.tabs.profile')
                        </div>

                        <div class="tab-pane fade pt-4" id="information" role="tabpanel" aria-labelledby="information-tab">
                            @include('frontend.user.account.tabs.information')
                        </div>

                        @if (! $logged_in_user->isSocial())
                            <div class="tab-pane fade pt-4" id="password" role="tabpanel" aria-labelledby="password-tab">
                                @include('frontend.user.account.tabs.password')
                            </div>
                        @endif

                        <div class="tab-pane fade pt-4" id="two-factor-authentication" role="tabpanel" aria-labelledby="two-factor-authentication-tab">
                            @include('frontend.user.account.tabs.two-factor-authentication')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection