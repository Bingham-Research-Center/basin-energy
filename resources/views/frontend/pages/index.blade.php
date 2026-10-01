@extends('frontend.layouts.web')

@section('title', 'Home')

@section('content')
<!-- Slider Start -->
<section class="slider">
   <div class="container">
      <div class="columns is-justify-content-center">
         <div class="column is-9-widescreen is-12-desktop">
            <div class="has-text-centered">
               <span class="is-block mb-4 is-uppercase">
                  {{ $hero->description ?? 'Prepare for new future' }}
               </span>

               <h1 class="animated fadeInUp mb-6 has-text-white">
                  {{ $hero->title ?? 'All About Emissions, Energy, and Environmental Trends in Uintah Basin' }}
               </h1>

               <a
                  href="{{ $hero->button_link ?? '#!' }}"
                  id="login-trigger"
                  class="btn btn-main animated fadeInUp m-1"
               >
                  {{ $hero->button_text ?? 'Get started' }}
                  <i class="btn-icon fa fa-angle-right ml-2"></i>
               </a>

               <a
                  href="{{ $weatherButton->button_link ?? 'https://www.basinwx.com/' }}"
                  target="_blank"
                  class="btn btn-solid-border animated fadeInUp m-1"
               >
                  {{ $weatherButton->button_text ?? 'Basin Weather Now' }}
               </a>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="mt--6 is-relative slider-cta">
   <div class="container">
      <div class="columns is-desktop is-align-items-center bg-primary rounded">
         <div class="column is-8-desktop">
            <h3 class="mb-4 has-text-white">
               {{ $platform->title ?? 'Integrated, research-based platform that brings together emissions, energy, and environmental trends in Uintah Basin' }}
            </h3>

            <p class="text-white-50">
               {{ $platform->description ?? 'Scientists, technical staff, and students at the Bingham Research Center are dedicated to energy and environmental research in Utah and around the world. We specialize in the areas of air quality, energy, and environmental science.' }}
            </p>
         </div>

         <div class="column is-4-desktop has-text-right">
<a
   target="_blank"
   href="{{ $platformButton->button_link ?? 'https://www.usu.edu/binghamresearch/' }}"
   class="btn btn-white mb-0"
>
   {{ $platformButton->button_text ?? 'Bingham Research Center' }}
</a>
         </div>
      </div>
   </div>
</section>

<!-- Live Basin Conditions Start -->
<section class="section">
   <div class="container">

      <div class="columns is-justify-content-center mb-6">
         <div class="column is-8-desktop has-text-centered">
            <h2 class="mb-3">Live Basin Conditions</h2>

            <p>
               Current environmental conditions and measurements
               from the Uintah Basin.
            </p>
         </div>
      </div>

      <div class="columns is-multiline">

         <!-- Temperature -->
         <div class="column is-3-desktop is-6-tablet">
            <div class="has-text-centered p-5 bg-light rounded">
               <i class="ti-shine text-lg has-text-primary"></i>

               <h3 class="mt-3 mb-2">Temperature</h3>

               <h2 id="basin-temperature">--°F</h2>

               <p>Vernal • BasinWx</p>
            </div>
         </div>

         <!-- Wind -->
         <div class="column is-3-desktop is-6-tablet">
            <div class="has-text-centered p-5 bg-light rounded">
               <i class="ti-direction text-lg has-text-primary"></i>

               <h3 class="mt-3 mb-2">Wind</h3>

               <h2 id="basin-wind">-- mph</h2>

               <p>Vernal • BasinWx</p>
            </div>
         </div>

         <!-- PM2.5 -->
         <div class="column is-3-desktop is-6-tablet">
            <div class="has-text-centered p-5 bg-light rounded">
               <i class="ti-cloud text-lg has-text-primary"></i>

               <h3 class="mt-3 mb-2">PM2.5</h3>

               <h2 id="basin-pm25">--</h2>

               <p>QCV • BasinWx</p>
            </div>
         </div>

         <!-- NOx -->
         <div class="column is-3-desktop is-6-tablet">
            <div class="has-text-centered p-5 bg-light rounded">
               <i class="ti-bar-chart text-lg has-text-primary"></i>

               <h3 class="mt-3 mb-2">NOx</h3>

               <h2 id="basin-nox">--</h2>

               <p>A1386 • BasinWx</p>
            </div>
         </div>

      </div>

      <div class="has-text-centered mt-5">
         <p class="text-muted">
            Last updated: <span id="basin-updated">--</span>
         </p>

         <a
            href="https://www.basinwx.com/"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-main"
         >
            View Basin Weather
            <i class="fa fa-angle-right ml-2"></i>
         </a>
      </div>

   </div>
</section>
<!-- Live Basin Conditions End -->

<script>
document.addEventListener('DOMContentLoaded', function () {

   fetch('/api/basin-conditions')
      .then(function (response) {
         if (!response.ok) {
            throw new Error('Unable to load BasinWx data.');
         }

         return response.json();
      })
      .then(function (data) {

         document.getElementById('basin-temperature').textContent =
            data.temperature !== null
               ? data.temperature + '°F'
               : '--°F';

         document.getElementById('basin-wind').textContent =
            data.wind !== null
               ? data.wind + ' mph'
               : '-- mph';

document.getElementById('basin-pm25').textContent =
   data.pm25 !== null
      ? data.pm25 + ' \u00B5g/m\u00B3'
      : '--';

         document.getElementById('basin-nox').textContent =
            data.nox !== null
               ? data.nox + ' ppb'
               : '--';

         if (data.updated_at) {
            var updated = new Date(data.updated_at);

            document.getElementById('basin-updated').textContent =
               updated.toLocaleString();
         }
      })
      .catch(function (error) {
         console.error('BasinWx error:', error);

         document.getElementById('basin-temperature').textContent = '--°F';
         document.getElementById('basin-wind').textContent = '-- mph';
         document.getElementById('basin-pm25').textContent = '--';
         document.getElementById('basin-nox').textContent = '--';
         document.getElementById('basin-updated').textContent =
            'Unable to load current data';
      });
 });
</script>
</br>
@endsection
