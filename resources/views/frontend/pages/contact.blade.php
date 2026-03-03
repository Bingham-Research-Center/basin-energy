@extends('frontend.layouts.web')

@section('title', 'Contcat')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>Contact Us</p> -->
               <h1 class="text-capitalize mb-4 text-lg">Get in Touch</h1>
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">Contact Us</a></li>
               </ul> -->
            </div>
         </div>
      </div>
   </div>
</section>

<!-- contact form start -->
<section class="contact-form-wrap section">
   <div class="container">
      <div class="columns is-multiline is-align-items-center bg-gray">
         <div class="column is-6-desktop is-12-tablet">
            <div class="google-map">
               <div id="map"></div>
            </div>
         </div>
         <div class="column is-6-desktop is-12-tablet">
            <div class="contact-content">
               <p class="mb-4 mt-2 lead h4">Don’t Hesitate to contact <br>with us for any kind of information</p>
               <h2 class="mb-3">(+00) 123 567990</h2>
               <p>Start the collaboration with us while figuring out the best solution based on your needs.</p>
               <ul class="social-icons list-inline mt-5">
                  <li class="list-inline-item">
                     <a href="http://www.themefisher.com"><i class="fab fa-facebook-f"></i></a>
                  </li>
                  <li class="list-inline-item">
                     <a href="http://www.themefisher.com"><i class="fab fa-twitter"></i></a>
                  </li>
                  <li class="list-inline-item">
                     <a href="http://www.themefisher.com"><i class="fab fa-linkedin-in"></i></a>
                  </li>
               </ul>
            </div>
         </div>
      </div>
      <div class="columns is-justify-content-center mt-5">
         <div class="column is-8-widescreen is-10-desktop has-text-centered mt-4">
            <form id="contact-form" class="contact__form" method="post" action="mail.php">
               <!-- form message -->
               <div class="columns">
                  <div class="column is-12">
                     <div class="alert alert-success contact__msg" style="display: none" role="alert">
                        Your message was sent successfully.
                     </div>
                  </div>
               </div>
               <!-- end message -->
               <h3 class="text-md ">Contact Us</h3>
               <p class="mb-5">Reach out to the world’s most reliable services.</p>
               <div class="input-group">
                  <input name="name" type="text" class="input" placeholder="Your Name">
               </div>
               <div class="input-group">
                  <input name="email" type="email" class="input" placeholder="Email Address">
               </div>
               <div class="input-group-2 mb-4">
                  <textarea name="message" class="input" rows="4" placeholder="Your Message"></textarea>
               </div>
               <button class="btn btn-main" name="submit" type="submit">Send Message</button>
            </form>
         </div>
      </div>
   </div>
</section>
@endsection