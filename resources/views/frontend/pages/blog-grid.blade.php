@extends('frontend.layouts.web')

@section('title', $hero->title ?? 'Blog')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <h1 class="text-capitalize mb-4 text-lg">{{ $hero->title ?? 'Blog articles' }}</h1>
            </div>
         </div>
      </div>
   </div>
</section>

<section class="section blog-wrap">
    <div class="container">
        <div class="columns is-multiline">
            @forelse($posts as $post)
                <div class="column is-6-desktop is-6-tablet">
                    <div class="blog-item has-text-centered mb-3">
                        <img src="{{ $post->featured_image ? asset('storage/' . $post->featured_image) : asset('frontend/images/blog/1.jpg') }}"
                             alt="{{ $post->title }}"
                             class="mb-4 rounded">

                        <span class="letter-spacing text-color text-sm is-uppercase">
                            {{ $post->categories->first()->name ?? 'General' }}
                        </span>

                        <h3 class="mb-2">
                            <a href="{{ route('frontend.pages.blogSingle', $post->slug) }}">{{ $post->title }}</a>
                        </h3>

                        <div class="blog-item-meta">
                            <span class="is-uppercase text-sm mr-3 letter-spacing">
                                by {{ $post->author_name ?? 'Admin' }}
                            </span>
                            <span class="is-uppercase text-sm letter-spacing">
                                <i class="ti-time mr-1"></i>
                                {{ optional($post->published_at)->format('F d, Y') }}
                            </span>
                        </div>

                        <p class="mt-3 mb-4">{{ $post->excerpt }}</p>

                        <a href="{{ route('frontend.pages.blogSingle', $post->slug) }}" class="btn btn-main">
                            Read More
                        </a>
                    </div>
                </div>
            @empty
                <div class="column is-12">
                    <p class="has-text-centered">No blog posts found.</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($posts, 'links'))
            <div class="columns mt-5 is-justify-content-center">
                <div class="column is-8-widescreen has-text-centered">
                    {{ $posts->links() }}
                </div>
            </div>
        @endif
    </div>
</section>
@endsection