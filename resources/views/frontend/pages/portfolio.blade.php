@extends('frontend.layouts.web')

@section('title', $hero->title ?? 'Portfolio')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">{{ $hero->title ?? 'Portfolio' }}</h1>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="section portfolio">
   <div class="container">
      <div class="columns is-justify-content-center">
         <div class="column is-6-widescreen is-8-desktop is-10-tablet has-text-centered">
            <div class="section-title">
               <h2 class="mb-4">{{ $portfolioHeader->title ?? 'Latest works' }}</h2>
               <p>{{ $portfolioHeader->description ?? 'We provide a wide range of creative services.' }}</p>
            </div>
         </div>
      </div>

      <div class="column is-12 has-text-centered mb-6">
         <div class="btn-group-toggle">
            <label class="btn active">
               <input type="radio" name="shuffle-filter" value="all" checked="checked">All
            </label>
            <label class="btn">
               <input type="radio" name="shuffle-filter" value="design">UI/UX Design
            </label>
            <label class="btn">
               <input type="radio" name="shuffle-filter" value="branding">BRANDING
            </label>
            <label class="btn">
               <input type="radio" name="shuffle-filter" value="illustration">ILLUSTRATION
            </label>
         </div>
      </div>

      <div class="columns shuffle-wrapper portfolio-gallery">
         @forelse($portfolioItems as $item)
            <div class="column is-4-desktop is-6-tablet shuffle-item"
                 data-groups='@json($item->categories ?? [])'>
               <div class="position-relative rounded inner-box">
                  <div class="image position-relative">
                     <img
                        src="{{ !empty($item->image) ? asset('storage/' . $item->image) : asset('frontend/images/portfolio/1.jpg') }}"
                        alt="{{ $item->title ?? 'portfolio-image' }}"
                        class="rounded w-100 d-block"
                     >
                     <div class="overlay-box">
                        <div class="overlay-inner">
                           <div class="overlay-content has-text-centered">
                              <h3 class="mb-2">{{ $item->title ?? 'Portfolio Item' }}</h3>
                              <p class="text-white-50">{{ $item->description ?? '' }}</p>
                              <a
                                 href="{{ !empty($item->image) ? asset('storage/' . $item->image) : asset('frontend/images/portfolio/1.jpg') }}"
                                 class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm"
                                 title="{{ $item->title ?? 'Portfolio Item' }}"
                              >
                                 view project
                              </a>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         @empty
            <div class="column is-12">
               <p class="has-text-centered">No portfolio items found.</p>
            </div>
         @endforelse
      </div>
   </div>
</section>

<section class="section pt-0 cta">
	<div class="container">
		<div class="columns is-justify-content-center">
			<div class="column is-8-widescreen is-9-desktop is-11-tablet has-text-centered">
				<span>{{ $cta->subtitle ?? 'For Every type business' }}</span>
				<h2 class="mt-5 mb-6">{{ $cta->title ?? 'Entrust your project to our best team of professionals' }}</h2>

				<a href="{{ $cta->button_link ?? route('frontend.pages.contact') }}" class="btn btn-main m-1">
                    {{ $cta->button_text ?? "Let's Talk" }}
                </a>

				<a href="{{ $cta->value['secondary_button_link'] ?? route('frontend.pages.service') }}" class="btn btn-main-border text-black m-1">
                    {{ $cta->value['secondary_button_text'] ?? 'Services' }}
                </a>
			</div>
		</div>
	</div>
</section>
@endsection