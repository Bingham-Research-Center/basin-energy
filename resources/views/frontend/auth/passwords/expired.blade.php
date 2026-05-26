@extends('frontend.layouts.web')

@section('title', __('Your password has expired.'))

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('frontend/css/pw-exp.css') }}">
@endpush

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">
                  {{ $hero->title ?? __('Password Expired!') }}
               </h1>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="section password-expired-page">
   <div class="container">
      <div class="columns is-centered">
         <div class="column is-8-tablet is-6-desktop is-5-widescreen">
            <div class="password-expired-card">
               <div class="has-text-centered mb-5">
                  <h2 class="title is-4 mb-2">@lang('Update Your Password')</h2>
                  <p class="has-text-grey">
                     @lang('Your password has expired. Please create a new password to continue.')
                  </p>
               </div>

               <x-forms.patch :action="route('frontend.auth.password.expired.update')">
                  <div class="field">
                     <label for="current_password" class="label">@lang('Current Password')</label>
                     <div class="control">
                        <input
                           type="password"
                           id="current_password"
                           name="current_password"
                           class="input"
                           placeholder="{{ __('Enter current password') }}"
                           maxlength="100"
                           required
                           autofocus
                           autocomplete="current-password"
                        />
                     </div>
                  </div>

                  <div class="field">
                     <label for="password" class="label">@lang('New Password')</label>
                     <div class="control">
                        <input
                           type="password"
                           id="password"
                           name="password"
                           class="input"
                           placeholder="{{ __('Enter new password') }}"
                           maxlength="100"
                           required
                           autocomplete="new-password"
                        />
                     </div>
                  </div>

                  <div class="field">
                     <label for="password_confirmation" class="label">@lang('Confirm New Password')</label>
                     <div class="control">
                        <input
                           type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="input"
                           placeholder="{{ __('Confirm new password') }}"
                           maxlength="100"
                           required
                           autocomplete="new-password"
                        />
                     </div>
                  </div>

                  <div class="field mt-5">
                     <button class="btn btn-main is-fullwidth" type="submit">
                        @lang('Update Password')
                     </button>
                  </div>
               </x-forms.patch>
            </div>
         </div>
      </div>
   </div>
</section>
@endsection