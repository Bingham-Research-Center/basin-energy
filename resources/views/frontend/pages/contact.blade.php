@extends('frontend.layouts.web')

@section('title', $hero->title ?? 'Contact')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">{{ $hero->title ?? 'Get in Touch' }}</h1>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="contact-form-wrap section">
   <div class="container">
      <div class="columns is-multiline is-align-items-center bg-gray">
         <div class="column is-6-desktop is-12-tablet">
            <div class="google-map">
               @if(!empty($map?->value['embed_url']))
                  <iframe
                      src="{{ $map->value['embed_url'] }}"
                      width="100%"
                      height="450"
                      style="border:0;"
                      allowfullscreen=""
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade">
                  </iframe>
               @else
                  <div id="map"></div>
               @endif
            </div>
         </div>

         <div class="column is-6-desktop is-12-tablet">
            <div class="contact-content">
               <p class="mb-4 mt-2 lead h4">
                  {!! nl2br(e($contactInfo->subtitle ?? 'Don’t Hesitate to contact with us for any kind of information')) !!}
               </p>

               <h2 class="mb-3">{{ $contactInfo->title ?? '(+00) 123 567990' }}</h2>

               <p>{{ $contactInfo->description ?? 'Start the collaboration with us while figuring out the best solution based on your needs.' }}</p>

               <ul class="social-icons list-inline mt-5">
                  @if(!empty($socialLinks->value['facebook']))
                     <li class="list-inline-item">
                        <a href="{{ $socialLinks->value['facebook'] }}" target="_blank">
                           <i class="fab fa-facebook-f"></i>
                        </a>
                     </li>
                  @endif

                  @if(!empty($socialLinks->value['twitter']))
                     <li class="list-inline-item">
                        <a href="{{ $socialLinks->value['twitter'] }}" target="_blank">
                           <i class="fab fa-twitter"></i>
                        </a>
                     </li>
                  @endif

                  @if(!empty($socialLinks->value['linkedin']))
                     <li class="list-inline-item">
                        <a href="{{ $socialLinks->value['linkedin'] }}" target="_blank">
                           <i class="fab fa-linkedin-in"></i>
                        </a>
                     </li>
                  @endif
               </ul>
            </div>
         </div>
      </div>

      <div class="columns is-justify-content-center mt-5">
         <div class="column is-8-widescreen is-10-desktop has-text-centered mt-4">
            <form method="POST" action="{{ route('frontend.pages.contact.submit') }}">
               @csrf

               @if(session('flash_success'))
                  <div class="columns">
                     <div class="column is-12">
                        <div class="alert alert-success contact__msg" role="alert">
                           {{ session('flash_success') }}
                        </div>
                     </div>
                  </div>
               @endif

               @if($errors->any())
                  <div class="columns">
                     <div class="column is-12">
                        <div class="alert alert-danger contact__msg" role="alert">
                           <ul class="mb-0">
                              @foreach($errors->all() as $error)
                                 <li>{{ $error }}</li>
                              @endforeach
                           </ul>
                        </div>
                     </div>
                  </div>
               @endif

               <h3 class="text-md">{{ $contactForm->title ?? 'Contact Us' }}</h3>
               <p class="mb-5">{{ $contactForm->description ?? 'Reach out to the world’s most reliable services.' }}</p>

               <div class="input-group">
                  <input name="name" type="text" class="input" placeholder="Your Name" value="{{ old('name') }}">
                  @error('name')
                     <small class="has-text-danger">{{ $message }}</small>
                  @enderror
               </div>

               <div class="input-group">
                  <input name="email" type="email" class="input" placeholder="Email Address" value="{{ old('email') }}">
                  @error('email')
                     <small class="has-text-danger">{{ $message }}</small>
                  @enderror
               </div>

               <div class="input-group-2 mb-4">
                  <textarea name="message" class="input" rows="4" placeholder="Your Message">{{ old('message') }}</textarea>
                  @error('message')
                     <small class="has-text-danger">{{ $message }}</small>
                  @enderror
               </div>
               @if(config('boilerplate.access.captcha.contact'))
                  <div class="columns">
                     <div class="column is-12 mb-4">
                           @captcha
                           <input type="hidden" name="captcha_status" value="true" />
                           @error('g-recaptcha-response')
                              <small class="has-text-danger d-block mt-2">{{ $message }}</small>
                           @enderror
                     </div>
                  </div>
               @endif

               <button class="btn btn-main" type="submit">Send Message</button>
            </form>
         </div>
      </div>
   </div>
</section>
@endsection