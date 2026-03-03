@extends('frontend.layouts.web')

@section('title', 'Blog')

@section('content')
<!-- nav part end -->
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>Our blog</p> -->
               <h1 class="text-capitalize mb-4 text-lg">Blog articles</h1>
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">Our blog</a></li>
               </ul> -->
            </div>
         </div>
      </div>
   </div>
</section>
<section class="section blog-wrap">
    <div class="container">
        <div class="columns is-multiline">
	<div class="column is-6-desktop is-6-tablet">
		<div class="blog-item has-text-centered mb-3 ">
			<img src="{{ asset('frontend/images/blog/1.jpg') }}" alt="" class=" mb-4 rounded">
			<span class="letter-spacing text-color text-sm is-uppercase">Creativity</span>
			<h3 class="mb-2"><a href="blog-single.html">Improve design with typography?</a></h3>
			<div class="blog-item-meta ">
				<span class="is-uppercase text-sm mr-3 letter-spacing">by themefisher</span>
				<span class="is-uppercase text-sm letter-spacing"><i class="ti-time mr-1"></i> January 28,2019</span>
			</div>
			<p class="mt-3 mb-4">Ipsa quasi corporis impedit dicta ipsam libero eum fuga nemo qui magnam! Et molestiae hic alias, laborum cumque saepe error fugiat. Cupiditate!</p>
			<a href="{{ route('frontend.pages.blogSingle') }}" class="btn btn-main">Read More</a>
		</div>
	</div>
	<div class="column is-6-desktop is-6-tablet">
		<div class="blog-item has-text-centered mb-3">
			<img src="{{ asset('frontend/images/blog/2.jpg') }}" alt="" class=" mb-4 rounded">
			<span class="letter-spacing text-color text-sm is-uppercase">Makreting</span>
			<h3 class="mb-2"><a href="blog-single.html">Marketing strategy your should know</a></h3>
			<div class="blog-item-meta ">
				<span class="is-uppercase text-sm mr-3 letter-spacing">by themefisher</span>
				<span class="is-uppercase text-sm letter-spacing"><i class="ti-time mr-1"></i> January 28,2019</span>
			</div>
			<p class="mt-3 mb-4">Ipsa quasi corporis impedit dicta ipsam libero eum fuga nemo qui magnam! Et molestiae hic alias, laborum cumque saepe error fugiat. Cupiditate!</p>
			<a href="{{ route('frontend.pages.blogSingle') }}" class="btn btn-main">Read More</a>
		</div>
	</div>
	<div class="column is-6-desktop is-6-tablet">
		<div class="blog-item has-text-centered mb-3">
			<img src="{{ asset('frontend/images/blog/3.jpg') }}" alt="" class=" mb-4 rounded">
			<span class="letter-spacing text-color text-sm is-uppercase">skill</span>
			<h3 class="mb-2"><a href="blog-single.html">Skill to grow business rapidly</a></h3>
			<div class="blog-item-meta ">
				<span class="is-uppercase text-sm mr-3 letter-spacing">by themefisher</span>
				<span class="is-uppercase text-sm letter-spacing"><i class="ti-time mr-1"></i> January 28,2019</span>
			</div>
			<p class="mt-3 mb-4">Ipsa quasi corporis impedit dicta ipsam libero eum fuga nemo qui magnam! Et molestiae hic alias, laborum cumque saepe error fugiat. Cupiditate!</p>
			<a href="{{ route('frontend.pages.blogSingle') }}" class="btn btn-main">Read More</a>
		</div>
	</div>
	<div class="column is-6-desktop is-6-tablet">
		<div class="blog-item has-text-centered mb-3">
			<img src="{{ asset('frontend/images/blog/4.jpg') }}" alt="" class=" mb-4 rounded">
			<span class="letter-spacing text-color text-sm is-uppercase">Creativity</span>
			<h3 class="mb-2"><a href="blog-single.html">Research your competitor result</a></h3>
			<div class="blog-item-meta ">
				<span class="is-uppercase text-sm mr-3 letter-spacing">by themefisher</span>
				<span class="is-uppercase text-sm letter-spacing"><i class="ti-time mr-1"></i> January 28,2019</span>
			</div>
			<p class="mt-3 mb-4">Ipsa quasi corporis impedit dicta ipsam libero eum fuga nemo qui magnam! Et molestiae hic alias, laborum cumque saepe error fugiat. Cupiditate!</p>
			<a href="{{ route('frontend.pages.blogSingle') }}" class="btn btn-main">Read More</a>
		</div>
	</div>
</div>

        <div class="columns mt-5 is-justify-content-center">
            <div class="column is-8-widescreen has-text-centered">
            	<nav class="post-navigation">
                    <a class="current" href="#">1</a>
                    <a href="#">2</a>
                    <a href="#">3</a>
                    <a href="#">4</a>
                    <a href="#">5</a>
                    <a href="#"><i class="ti-angle-right"></i></a>
	            </nav>
            </div>
        </div>
    </div>
</section>
@endsection