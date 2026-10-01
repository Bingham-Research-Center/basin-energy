@extends('frontend.layouts.web')

@section('title', $hero->title ?? 'Our services')

@section('content')

<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">
                  {{ $hero->title ?? 'Our services' }}
               </h1>
            </div>
         </div>
      </div>
   </div>
</section>

<!-- Services Start -->
<section class="section service">
   <div class="container">
      <div class="columns is-multiline is-justify-content-center">

         @forelse($serviceItems as $index => $item)

            <div class="column is-4-desktop is-6-tablet">
               <div
                  class="is-flex"
                  data-aos="fade-up"
                  data-aos-delay="{{ 200 + ($index * 100) }}"
               >
                  <i class="{{ $item->icon ?: 'ti-desktop' }} text-lg"></i>

                  <div class="ml-5">
                     <h4 class="mb-3">{{ $item->title }}</h4>
                     <p>{{ $item->description }}</p>
                  </div>
               </div>
            </div>

         @empty

            <div class="column is-12">
               <p class="has-text-centered">
                  No services available right now.
               </p>
            </div>

         @endforelse

      </div>
   </div>
</section>
<!-- Services End -->


<!-- Why BasinEnergy Start -->
<section class="section">
   <div class="container">

      <!-- Heading -->
      <div class="columns is-justify-content-center mb-6">
         <div class="column is-8-desktop has-text-centered">

            <h2 class="mb-3">
   {{ $whyBasinEnergy->where('item_key', 'main')->first()->title ?? 'Why BasinEnergy?' }}
</h2>

<p>
   {{ $whyBasinEnergy->where('item_key', 'main')->first()->description ?? '' }}
</p>

         </div>
      </div>


      <!-- Three Reasons -->
      <div class="columns is-multiline">

<!-- Research-Based -->
<div class="column is-4-desktop is-6-tablet">
   <div class="has-text-centered">

      <i class="ti-book has-text-primary text-lg"></i>

      <h3 class="mt-4 mb-3">
         {{ $whyBasinEnergy->where('item_key', 'research')->first()->title ?? 'Research-Based' }}
      </h3>

      <p>
         {{ $whyBasinEnergy->where('item_key', 'research')->first()->description ?? '' }}
      </p>

   </div>
</div>


<!-- Local Expertise -->
<div class="column is-4-desktop is-6-tablet">
   <div class="has-text-centered">

      <i class="ti-location-pin has-text-primary text-lg"></i>

      <h3 class="mt-4 mb-3">
         {{ $whyBasinEnergy->where('item_key', 'local')->first()->title ?? 'Local Expertise' }}
      </h3>

      <p>
         {{ $whyBasinEnergy->where('item_key', 'local')->first()->description ?? '' }}
      </p>

   </div>
</div>

<!-- Data & Monitoring -->
<div class="column is-4-desktop is-6-tablet">
   <div class="has-text-centered">

      <i class="ti-pulse has-text-primary text-lg"></i>

      <h3 class="mt-4 mb-3">
         {{ $whyBasinEnergy->where('item_key', 'monitoring')->first()->title ?? 'Data & Monitoring' }}
      </h3>

      <p>
         {{ $whyBasinEnergy->where('item_key', 'monitoring')->first()->description ?? '' }}
      </p>

   </div>
</div>

</div>  {{-- closes Three Reasons --}}

<!-- Explore Research -->
<div class="columns is-justify-content-center mt-6">
   <div class="column is-10-desktop">

      <div class="bg-primary rounded p-6 has-text-centered">

         <h3 class="has-text-white mb-3">
            {{ $whyBasinEnergy->where('item_key', 'cta')->first()->title ?? 'Explore Our Research' }}
         </h3>

         <p class="text-white-50 mb-5">
            {{ $whyBasinEnergy->where('item_key', 'cta')->first()->description ?? '' }}
         </p>

         <a
            href="{{ $whyBasinEnergy->where('item_key', 'cta')->first()->button_link ?? url('/portfolio') }}"
            class="btn btn-white"
         >
            {{ $whyBasinEnergy->where('item_key', 'cta')->first()->button_text ?? 'Explore Our Work' }}
            <i class="fa fa-angle-right ml-2"></i>
         </a>

      </div>

</div>
</div>

</section>
<!-- Why BasinEnergy End -->

@endsection