@extends('frontend.layouts.web')

@section('title', 'Home')

@section('content')
<!-- Slider Start -->
<section class="slider">
   <div class="container">
      <div class="columns is-justify-content-center">
         <div class="column is-9-widescreen is-12-desktop">
            <div class="has-text-centered">
               <span class="is-block mb-4 is-uppercase">Prepare for new future</span>
               <h1 class="animated fadeInUp mb-6 has-text-white">All About Emissions, Energy, and Environmental Trends in Uintah Basin</h1>
               <a href="#!" id="login-trigger" class="btn btn-main animated fadeInUp m-1" >Get started<i class="btn-icon fa fa-angle-right ml-2"></i></a>
               <a href="http://basinwx.com" target="_blank" class="btn btn-solid-border animated fadeInUp m-1" >Basin Weather Now</a>
            </div>
         </div>
      </div>
   </div>
</section>
<section class="mt--6 is-relative slider-cta">
   <div class="container">
      <div class="columns is-desktop is-align-items-center bg-primary rounded">
         <div class="column is-8-desktop">
            <h3 class="mb-4 has-text-white">Integrated, research-based platform that brings together emissions, energy, and environmental trends in Uintah Basin</h3>
            <p class="text-white-50">Scientists, technical staff, and students at the Bingham Research Center are dedicated to energy and environmental research in Utah and around the world. We specialize in the areas of air quality, energy, and environmental science.</p>
         </div>
         <div class="column is-4-desktop has-text-right">
            <a _tar href="https://www.usu.edu/binghamresearch/" class="btn btn-white mb-0">Bingham Research Center</a>
         </div>
      </div>
   </div>
</section>
</br>
@endsection