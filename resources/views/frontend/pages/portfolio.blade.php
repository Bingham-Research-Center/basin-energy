@extends('frontend.layouts.web')

@section('title', 'Page Name')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>Latest works</p> -->
               <h1 class="text-capitalize mb-4 text-lg">Portfolio</h1>
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">Latest works</a></li>
               </ul> -->
            </div>
         </div>
      </div>
   </div>
</section>

<!-- portfolio -->
<section class="section portfolio">
   <div class="container">
      <div class="columns is-justify-content-center">
         <div class="column is-6-widescreen is-8-desktop is-10-tablet has-text-centered">
            <div class="section-title">
               <h2 class="mb-4">Latest works</h2>
               <p>We provide a wide range of creative services adipisicing elit. Autem maxime rem modi eaque, voluptate. Beatae officiis neque </p>
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
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;design&quot;,&quot;illustration&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/1.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/1.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;branding&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/2.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/2.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;illustration&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/3.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/3.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;design&quot;,&quot;branding&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/4.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/4.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;illustration&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/5.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/5.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;design&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/6.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/6.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;branding&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/1.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/1.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item" data-groups="[&quot;design&quot;,&quot;illustration&quot;,&quot;branding&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/2.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/2.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet shuffle-item illustration" data-groups="[&quot;illustration&quot;]">
            <div class="position-relative rounded inner-box">
               <div class="image position-relative ">
                  <img src="{{ asset('frontend/images/portfolio/4.jpg') }}" alt="portfolio-image" class=" rounded w-100 d-block">
                  <div class="overlay-box">
                     <div class="overlay-inner">
                        <div class="overlay-content has-text-centered">
                           <h3 class="mb-2">Sulfur web</h3>
                           <p class="text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit</p>
                           <a href="{{ asset('frontend/images/portfolio/4.jpg') }}" class="portfolio-image popup-gallery btn btn-white mt-3 btn-sm" title="Marketing">view project</a>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>
<!-- /portfolio -->
<section class="section pt-0 cta">
	<div class="container">
		<div class="columns is-justify-content-center">
			<div class="column is-8-widescreen is-9-desktop is-11-tablet has-text-centered">
				<span>For Every type business</span>
				<h2 class="mt-5 mb-6">Entrust your project to our best team of professionals</h2>
				<a href="contact.html" class="btn btn-main m-1">Let's Talk</a>
				<a href="service.html" class="btn btn-main-border text-black m-1">Services</a>
			</div>
		</div>
	</div>
</section>
@endsection