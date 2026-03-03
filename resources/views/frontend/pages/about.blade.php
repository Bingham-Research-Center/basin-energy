@extends('frontend.layouts.web')

@section('title', 'About Us')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>Our Company</p> -->
               <h1 class="text-capitalize mb-4 text-lg">About Us</h1>
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">Our Company</a></li>
               </ul> -->
            </div>
         </div>
      </div>
   </div>
</section>



<!-- Section Intro Start -->
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
<section class="about" >
	<div class="container">
		<div class="columns is-desktop is-align-items-center">
			<div class="column is-6-desktop">
				<div class="about-img" data-aos="fade-right" data-aos-delay="200" style="line-height: 0">
					<img src="{{ asset('frontend/images/about/home-8.jpg') }}" alt="" class="rounded">
				</div>
			</div>
			<div class="column is-6-desktop">
				<div class="about-item">
					<h2 class="mt-3 mb-4 font-secondary"> Forget about <br>design limits! Build and customize your portfolio</h2>
					<p class="mb-5">We provide consulting services in the area of IFRS and management reporting, helping companies to reach their highest level. We optimize business processes, making them easier.</p>

					<a href="#" class="btn btn-main">Get started</a>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- Section About End -->
</br>
<!-- section Counter Start -->
<section class="counter bg-counter">
	<div class="container">
		<div class="columns is-touch is-multiline">
			<div class="column is-3-desktop is-6-tablet">
				<div class="counter-item is-flex is-align-items-center is-justify-content-center" data-aos="fade-up" data-aos-delay="100">
					<i class="ti-check has-text-white text-md"></i>
					<div class="ml-5">
						<h3 class="mt-2 mb-0 has-text-white"><span class="counter-stat">1730</span></h3>
						<p class="text-white-50 mb-0">Project Done</p>
					</div>
				</div>
			</div>
			<div class="column is-3-desktop is-6-tablet">
				<div class="counter-item is-flex is-align-items-center is-justify-content-center" data-aos="fade-up" data-aos-delay="200">
					<i class="ti-flag has-text-white text-md"></i>
					<div class="ml-5">
						<h3 class="mt-2 mb-0 has-text-white"><span class="counter-stat ">125 </span>M </h3>
						<p class="text-white-50 mb-0">User Worldwide</p>
					</div>
				</div>
			</div>
			<div class="column is-3-desktop is-6-tablet">
				<div class="counter-item is-flex is-align-items-center is-justify-content-center" data-aos="fade-up" data-aos-delay="300">
					<i class="ti-layers has-text-white text-md"></i>
					<div class="ml-5">
						<h3 class="mt-2 mb-0 has-text-white"><span class="counter-stat">39</span></h3>
						<p class="text-white-50 mb-0">Available Country</p>
					</div>
				</div>
			</div>
			<div class="column is-3-desktop is-6-tablet">
				<div class="counter-item is-flex is-align-items-center is-justify-content-center" data-aos="fade-up" data-aos-delay="400">
					<i class="ti-medall has-text-white text-md"></i>
					<div class="ml-5">
						<h3 class="mt-2 mb-0 has-text-white"><span class="counter-stat">14</span></h3>
						<p class="text-white-50 mb-0">Award Winner </p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- section Counter End  -->
<!--  Section Team Start -->
<section class="section team position-relative">
   <div class="container">
      <div class="columns is-justify-content-center">
         <div class="column is-6-widescreen is-8-desktop is-10-tablet has-text-centered">
            <div class="section-title">
               <h2 class="mb-4">Team</h2>
               <p>We provide a wide range of creative services adipisicing elit. Autem maxime rem modi eaque, voluptate. Beatae officiis neque </p>
            </div>
         </div>
      </div>
      <div class="columns is-multiline is-justify-content-center">
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="200" >
               <img src="{{ asset('frontend/images/team/team-1.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">Justin hammer</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="400" >
               <img src="{{ asset('frontend/images/team/team-2.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">Mikel emily</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="600" >
               <img src="{{ asset('frontend/images/team/team-3.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">David Spensor</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="200" >
               <img src="{{ asset('frontend/images/team/team-4.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">Jason Roy</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="400" >
               <img src="{{ asset('frontend/images/team/team-1.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">Peter Odin</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
         <div class="column is-4-desktop is-6-tablet">
            <div class="team-item-wrap" data-aos="fade-left" data-aos-delay="600"  >
               <img src="{{ asset('frontend/images/team/team-2.jpg') }}" alt="" class=" w-100 rounded">
               <div class="team-item-content">
                  <p class="text-sm mb-0">Project Manager</p>
                  <h3 class="mt-0 mb-2 text-capitalize">David Spensor</h3>
                  <ul class="team-social list-inline ">
                     <li class="list-inline-item">
                        <a href="#" class="facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                     </li>
                     <li class="list-inline-item">
                        <a href="#" class="linkedin"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>
<!--  Section Team End -->
<section class="section">
   <div class="container">
      <div class="columns is-gapless is-mobile is-multiline">
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision1.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision2.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision3.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision4.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision3.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision5.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision3.png') }}" alt="" class=" w-100">
         </div>
         <div class="column is-3-desktop is-4-tablet is-6-mobile">
            <img src="{{ asset('frontend/images/about/key-vision4.png') }}" alt="" class=" w-100">
         </div>
      </div>
   </div>
</section>
@endsection