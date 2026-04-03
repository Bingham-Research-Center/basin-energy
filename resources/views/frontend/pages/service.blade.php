@extends('frontend.layouts.web')

@section('title', $hero->title ?? 'Our services')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">{{ $hero->title ?? 'Our services' }}</h1>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="section service">
   <div class="container">
      <div class="columns is-multiline is-justify-content-center">
         @forelse($serviceItems as $index => $item)
            <div class="column is-4-desktop is-6-tablet">
               <div class="is-flex" data-aos="fade-up" data-aos-delay="{{ 200 + ($index * 100) }}">
                  <i class="{{ $item->icon ?: 'ti-desktop' }} text-lg"></i>
                  <div class="ml-5">
                     <h4 class="mb-3">{{ $item->title }}</h4>
                     <p>{{ $item->description }}</p>
                  </div>
               </div>
            </div>
         @empty
            <div class="column is-12">
               <p class="has-text-centered">No services available right now.</p>
            </div>
         @endforelse
      </div>
   </div>
</section>
@endsection