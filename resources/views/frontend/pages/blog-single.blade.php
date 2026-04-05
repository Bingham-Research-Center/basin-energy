@extends('frontend.layouts.web')

@section('title', $post->title)

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered"></div>
         </div>
      </div>
   </div>
</section>

<section class="section blog-wrap">
   <div class="container">
      <div class="columns is-desktop">
         <div class="column is-8-desktop">
            <div class="columns is-multiline">
               <div class="column is-12">
                  <div class="single-blog-item">
                     <div class="has-text-centered">
                        <span class="text-color text-sm letter-spacing is-uppercase mr-3">
                            {{ $post->categories->first()->name ?? 'General' }}
                        </span>
                     </div>

                     <h2 class="mt-1 mb-4 has-text-centered">{{ $post->title }}</h2>

                     <div class="blog-item-meta has-text-centered mb-5">
                        <span class="is-uppercase text-sm mr-3 letter-spacing">by {{ $post->author_name ?? 'Admin' }}</span>
                        <span class="is-uppercase text-sm letter-spacing">
                           <i class="ti-time mr-1"></i>
                           {{ optional($post->published_at)->format('F d, Y') }}
                        </span>
                     </div>

                     <img src="{{ $post->featured_image ? asset('storage/' . $post->featured_image) : asset('frontend/images/blog/2.jpg') }}"
                          alt="{{ $post->title }}"
                          class="rounded">

                     @if($post->excerpt)
                        <div class="mt-4">
                           {!! $post->excerpt !!}
                        </div>
                     @endif

                     <div class="mt-4">
                        {!! $post->content !!}
                     </div>
                  </div>
               </div>

               <div class="column is-12 mb-3 has-text-centered">
                  <ul class="list-inline tag-option">
                     @forelse($post->categories as $category)
                        <li class="list-inline-item mb-2 mb-lg-0">
                           <a href="#" rel="tag">{{ $category->name }}</a>
                        </li>
                     @empty
                        <li class="list-inline-item"><a href="#" rel="tag">General</a></li>
                     @endforelse
                  </ul>
               </div>

               <div class="column is-12 mb-5">
                  <div class="posts-nav bg-gray is-justify-content-space-between">
                     @if($previousPost)
                        <a class="post-prev is-align-items-center" href="{{ route('frontend.pages.blogSingle', $previousPost->slug) }}">
                           <div class="posts-prev-item">
                              <p class="is-uppercase letter-spacing text-sm mb-0">Previous Post</p>
                              <h5>{{ $previousPost->title }}</h5>
                           </div>
                        </a>
                     @else
                        <div></div>
                     @endif

                     <div class="border"></div>

                     @if($nextPost)
                        <a class="posts-next" href="{{ route('frontend.pages.blogSingle', $nextPost->slug) }}">
                           <div class="posts-next-item">
                              <p class="has-text-right-desktop is-block is-uppercase letter-spacing text-sm mb-0">Next Post</p>
                              <h5>{{ $nextPost->title }}</h5>
                           </div>
                        </a>
                     @else
                        <div></div>
                     @endif
                  </div>
               </div>
            </div>
         </div>

         <div class="column is-4-desktop">
            <div class="sidebar-wrap">
               <div class="sidebar-widget search mb-6">
                  <input type="text" class="input" placeholder="search">
                  <a href="#" class="btn btn-main is-block has-text-centered mt-2">search</a>
               </div>

               <div class="sidebar-widget mb-6 has-text-centered">
                  <img src="{{ !empty($sidebarAuthor?->image) ? asset('storage/' . $sidebarAuthor->image) : asset('frontend/images/blog/abou-us.png') }}"
                       alt="{{ $sidebarAuthor->title ?? 'Author' }}"
                       class="mx-auto">
                  <h4 class="mb-2 mt-4">{{ $sidebarAuthor->title ?? 'Author' }}</h4>
                  <p>{{ $sidebarAuthor->description ?? '' }}</p>
               </div>

               <div class="sidebar-widget latest-post mb-6">
                  <h5 class="has-text-centered mb-4">Latest Posts</h5>

                  @forelse($latestPosts as $latestPost)
                     <div class="is-flex border-bottom py-3" style="border-color:#ddd">
                        <a href="{{ route('frontend.pages.blogSingle', $latestPost->slug) }}">
                           <img class="rounded"
                                src="{{ $latestPost->featured_image ? asset('storage/' . $latestPost->featured_image) : asset('frontend/images/blog/bt-1.jpg') }}"
                                alt="{{ $latestPost->title }}">
                        </a>
                        <div class="ml-5">
                           <h6 class="mb-1">
                              <a href="{{ route('frontend.pages.blogSingle', $latestPost->slug) }}">{{ $latestPost->title }}</a>
                           </h6>
                           <span class="text-sm text-muted">{{ optional($latestPost->published_at)->format('d M Y') }}</span>
                        </div>
                     </div>
                  @empty
                     <p class="text-center">No latest posts found.</p>
                  @endforelse
               </div>

               <div class="sidebar-widget category mb-4">
                  <h5 class="mb-4 has-text-centered">Category</h5>
                  <ul class="list-group">
                     @forelse($categories as $category)
                        <li>
                           <a href="#!" class="is-flex is-justify-content-space-between is-align-items-center">
                              {{ $category->name }}
                              <span class="count">{{ $category->posts_count }}</span>
                           </a>
                        </li>
                     @empty
                        <li>
                           <a href="#!" class="is-flex is-justify-content-space-between is-align-items-center">
                              General
                              <span class="count">0</span>
                           </a>
                        </li>
                     @endforelse
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
</section>
@endsection