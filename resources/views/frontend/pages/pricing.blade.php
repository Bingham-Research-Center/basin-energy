@extends('frontend.layouts.web')

@section('title', 'Page Name')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>Our pricing</p> -->
               <h1 class="text-capitalize mb-4 text-lg">Pricing Package</h1>
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">Our pricing</a></li>
               </ul> -->
            </div>
         </div>
      </div>
   </div>
</section>

<section class="section intro" >
	<div class="container">
		<div class="columns is-desktop is-justify-content-space-between">
			<div class="column is-5-desktop">
				<div class="pt-5 mb-4 mb-lg-0">
					<h2 class="mt-3 text-md font-secondary">We provide best solution to client with their business problem </h2>
				</div>
			</div>

			<div class="column is-6-desktop">
				<div class="columns">
					<div class="column is-6-desktop is-6-tablet"  data-aos="fade-up" data-aos-delay="200" >
						<div class="intro-item mb-4 mb-lg-0">
							<i class="ti-wand text-color"></i>
							<h4 class="mt-4 mb-3">Modern & Responsive design</h4>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Earum, aspernatur.</p>
						</div>
					</div>
					<div class="column is-6-desktop is-6-tablet">
						<div class="intro-item mb-4 mb-lg-0"  data-aos="fade-up" data-aos-delay="300" >
							<i class="ti-medall text-color"></i>
							<h4 class="mt-4 mb-3">Awarded licensed company</h4>
							<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Earum, aspernatur.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<section class="section pricing bg-primary is-relative">
    <div class="hero-img bg-overlay"></div>
    <div class="container">
       <div class="columns is-justify-content-center">
          <div class="column is-6-widescreen is-8-desktop is-10-tablet has-text-centered">
             <div class="section-title" >
                <h2 class="has-text-white mb-4">Business plan</h2>
                <p class="text-white-50">We provide a wide range of creative services adipisicing elit. Autem maxime rem modi eaque, voluptate. Beatae officiis neque </p>
             </div>
          </div>
       </div>
       <div class="columns is-multiline is-justify-content-center">
          <div class="column is-4-desktop is-6-tablet">
             <div class="card has-text-centered mb-lg-0 mb-3" data-aos="fade-down" data-aos-delay="100">
                <div class="py-6">
                   <div class="pricing-header mb-4 border-bottom pb-4">
                      <span class="mb-3 h5 plan">Free</span>
                      <p class="text-muted">User / Month</p>
                      <h1><small>$</small>0</h1>
                   </div>
                   <ul class="lh-45 mt-3 text-black">
                      <li>Up to 1 User</li>
                      <li>Max 100 Item</li>
                      <li>500 Queries</li>
                      <li>Basic Statistics</li>
                   </ul>
                   <a href="#" class="btn btn-solid-border mt-5 ">Join Now</a>
                </div>
             </div>
          </div>
          <div class="column is-4-desktop is-6-tablet">
             <div class="card has-text-centered mb-lg-0 mb-3 " data-aos="fade-down" data-aos-delay="300">
                <div class="py-6">
                   <div class="pricing-header mb-4 border-bottom pb-4 active">
                      <span class="mb-3 h5 plan ">Basic</span>
                      <p class="text-muted">User / Month</p>
                      <h1><small>$</small>12</h1>
                   </div>
                   <ul class="lh-45 mt-3 text-black">
                      <li>Up to 5 User</li>
                      <li>Max 1000 Item</li>
                      <li>5000 Queries</li>
                      <li>Standard Statistics</li>
                   </ul>
                   <a href="#" class="btn btn-main mt-5">Join Now</a>
                </div>
             </div>
          </div>
          <div class="column is-4-desktop is-6-tablet">
             <div class="card has-text-centered mb-lg-0 mb-3" data-aos="fade-down" data-aos-delay="500"  >
                <div class="py-6">
                   <div class="pricing-header mb-4 border-bottom pb-4">
                      <span class="mb-3 h5 plan">Premium</span>
                      <p class="text-muted">User / Month</p>
                      <h1><small>$</small>39</h1>
                   </div>
                   <ul class="lh-45 mt-3 text-black">
                      <li>Unlimited User</li>
                      <li>Unlimited Item</li>
                      <li>Unlimited Queries</li>
                      <li>Full Statistics</li>
                   </ul>
                   <a href="#" class="btn btn-solid-border mt-5">Join Now</a>
                </div>
             </div>
          </div>
       </div>
    </div>
 </section>
@endsection