@extends('frontend.layouts.web')

@section('title', 'Page Name')

@section('content')
<section class="page-title bg-1">
   <div class="container">
      <div class="columns">
         <div class="column is-12">
            <div class="has-text-centered">
               <!-- <p>News details</p> -->
               <!-- <h1 class="text-capitalize mb-4 text-lg">Blog Single</h1> -->
               <!-- <ul class="list-inline">
                  <li class="list-inline-item"><a href="index.html" class="text-white">Home</a></li>
                  <li class="list-inline-item"><span class="text-white">/</span></li>
                  <li class="list-inline-item"><a href="#" class="text-white-50">News details</a></li>
               </ul> -->
            </div>
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
      <div class="single-blog-item ">
         <div class="has-text-centered">
            <span class="text-color text-sm letter-spacing is-uppercase mr-3">Creativity</span>
         </div>
         <h2 class="mt-1 mb-4 has-text-centered"><a href="blog-single.html">Improve design with typography?</a></h2>
         <div class="blog-item-meta has-text-centered mb-5">
            <span class="is-uppercase text-sm mr-3 letter-spacing">by themefisher</span>
            <span class="is-uppercase text-sm letter-spacing"><i class="ti-time mr-1"></i> January 28,2019</span>
         </div>
         <img src="{{ asset('frontend/images/blog/2.jpg') }}" alt="" class=" rounded">
         <p class="mt-4">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Possimus natus, consectetur? Illum libero vel nihil nisi quae, voluptatem, sapiente necessitatibus distinctio voluptates, iusto qui. Laboriosam autem, nam voluptate in beatae.</p>
         <h3 class="quote">A brand for a company is like a reputation for a person. You earn reputation by trying to do hard things well.</h3>
         <p class="h4 mb-4 has-text-weight-normal text-black">The same is true as we experience the emotional sensation of stress from our first instances of social rejection ridicule. We quickly learn to fear and automatically.</p>
         <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Iste, rerum beatae repellat tenetur incidunt quisquam libero dolores laudantium. Nesciunt quis itaque quidem, voluptatem autem eos animi laborum iusto expedita sapiente.</p>
      </div>
   </div>
   <div class="column is-12 mb-3 has-text-centered">
      <ul class="list-inline tag-option">
         <li class="list-inline-item mb-2 mb-lg-0"><a href="#" rel="tag">Advancher</a></li>
         <li class="list-inline-item mb-2 mb-lg-0"><a href="#" rel="tag">Landscape</a></li>
         <li class="list-inline-item"><a href="#" rel="tag">Travel</a></li>
      </ul>
   </div>
   <div class="column is-12 mb-5 has-text-centered">
      <ul class="list-inline share">
         <li class="list-inline-item mb-2 mb-lg-0"><a href="#" target="_blank" class="fb"><i class="fab fa-facebook-f mr-2" aria-hidden="true"></i>Facebook</a></li>
         <li class="list-inline-item mb-2 mb-lg-0"><a href="#" target="_blank" class="twt"><i class="fab fa-twitter mr-2" aria-hidden="true"></i>Twitter</a></li>
         <li class="list-inline-item mb-2 mb-lg-0"><a href="#" target="_blank" class="pint"><i class="fab fa-pinterest-p mr-2" aria-hidden="true"></i>pinterest</a></li>
         <li class="list-inline-item"><a href="#" target="_blank" class="link"><i class="fab fa-linkedin mr-2" aria-hidden="true"></i>Linkedin</a></li>
      </ul>
   </div>
   <div class="column is-12 mb-5">
      <div class="posts-nav bg-gray is-justify-content-space-between">
         <a class="post-prev is-align-items-center" href="#">
            <div class="posts-prev-item">
               <p class="is-uppercase letter-spacing text-sm mb-0">Previous Post</p>
               <h5>
                  Donec consectetuer ligula
               </h5>
            </div>
         </a>
         <div class="border"></div>
         <a class="posts-next" href="#">
            <div class="posts-next-item">
               <p class="has-text-right-desktop is-block is-uppercase letter-spacing text-sm mb-0">Next Post</p>
               <h5>
                  Ut aliquam sollicitudin leo
               </h5>
            </div>
         </a>
      </div>
   </div>
   <div class="column is-12 mb-5">
      <div class="comment-area p-5">
         <h4 class="mb-4">Comments</h4>
         <div class="media">
				<a href="#"><img src="{{ asset('frontend/images/blog/test1.jpg') }}" alt="..." class="user-img"></a>
            <div class="ml-4">
               <h5 class="mb-0">Philip W</h5>
               <span class="date-comm">Posted October 7, 2018 </span>
               <div class="comment-meta is-pulled-right">
                  <a href="#"><i class="icofont-reply mr-2 text-muted"></i><i class="ti-back-right mr-2"></i>Reply</a>
               </div>
               <div class="comment-content mt-3">
                  <p>Some consultants are employed indirectly by the client via a consultancy staffing company, a company that provides consultants on an agency basis. </p>
               </div>
               <div class="media mt-5">
                  <a href="#"><img src="{{ asset('frontend/images/blog/test2.jpg') }}" alt="..." class="user-img"></a>
                  <div class="ml-4">
                     <h5 class="mb-0">Philip W</h5>
                     <span class="date-comm">Posted October 7, 2018 </span>
                     <div class="comment-meta is-pulled-right">
                        <a href="#"><i class="icofont-reply mr-2 text-muted"></i><i class="ti-back-right mr-2"></i>Reply</a>
                     </div>
                     <div class="comment-content mt-3">
                        <p>Some consultants are employed indirectly by the client via a consultancy staffing company, a company that provides consultants on an agency basis. </p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="media mt-4">
            <img src="{{ asset('frontend/images/blog/test1.jpg') }}" alt="..." class="user-img">
            <div class="ml-4">
               <h5 class="mb-0">Philip W</h5>
               <span class="date-comm">Posted October 7, 2018 </span>
               <div class="comment-meta is-pulled-right">
                  <a href="#"><i class="icofont-reply mr-2 text-muted"></i><i class="ti-back-right mr-2"></i>Reply</a>
               </div>
               <div class="comment-content mt-3">
                  <p>Some consultants are employed indirectly by the client via a consultancy staffing company, a company that provides consultants on an agency basis. </p>
               </div>
            </div>
         </div>
      </div>
   </div>
   <div class="column lg-12">
      <div class="comment-form bg-gray p-5 " >
         <h4 class="mb-0">Leave a Reply </h4>
         <p class="mb-4">Your email address will not be published. </p>
         <form class="comment-form" id="comment-form">
            <div class="mb-4">
               <input class="input" type="text" name="cname" id="name" placeholder="Name:">
            </div>
            <div class="mb-4">
               <input class="input" type="text" name="cmail" id="mail" placeholder="Email:">
            </div>
            <div class="mb-4">
					<textarea class="input" name="comment" id="comment" cols="30" rows="5" placeholder="Comment"></textarea>
            </div>
				
				<button class="btn btn-main" type="submit">Submit Message</button>
         </form>
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
      <img src="{{ asset('frontend/images/blog/abou-us.png') }}" alt="" class=" mx-auto">
      <h4 class="mb-2 mt-4">Arther Conal</h4>
      <ul class="list-inline author-socials mb-3">
         <li class="list-inline-item mr-4">
            <a href="#"><i class="fab fa-facebook-f text-muted"></i></a>
         </li>
         <li class="list-inline-item mr-4">
            <a href="#"><i class="fab fa-twitter text-muted"></i></a>
         </li>
         <li class="list-inline-item mr-4">
            <a href="#"><i class="fab fa-linkedin-in text-muted"></i></a>
         </li>
         <li class="list-inline-item mr-4">
            <a href="#"><i class="fab fa-pinterest text-muted"></i></a>
         </li>
         <li class="list-inline-item mr-4">
            <a href="#"><i class="fab fa-behance text-muted"></i></a>
         </li>
      </ul>
      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Incidunt, dolore.</p>
   </div>
   <div class="sidebar-widget latest-post mb-6">
      <h5 class="has-text-centered mb-4">Latest Posts</h5>
      <div class="is-flex border-bottom py-3" style="border-color:#ddd">
         <a href="#"><img class="rounded" src="{{ asset('frontend/images/blog/bt-3.jpg') }}" alt=""></a>
         <div class="ml-5">
            <h6 class="mb-1"><a href="#">Thoughtful living in los Angeles</a></h6>
            <span class="text-sm text-muted">03 Mar 2018</span>
         </div>
      </div>
      <div class="is-flex border-bottom py-3" style="border-color:#ddd">
         <a href="#"><img class="rounded" src="{{ asset('frontend/images/blog/bt-1.jpg') }}" alt=""></a>
         <div class="ml-5">
            <h6 class="mb-1"><a href="#">Vivamus molestie gravida turpis.</a></h6>
            <span class="text-sm text-muted">03 Mar 2018</span>
         </div>
      </div>
      <div class="is-flex py-3">
         <a href="#"><img class="rounded" src="{{ asset('frontend/images/blog/bt-2.jpg') }}" alt=""></a>
         <div class="ml-5">
            <h6 class="mb-1"><a href="#">Vivamus molestie gravida turpis.</a></h6>
            <span class="text-sm text-muted">03 Mar 2018</span>
         </div>
      </div>
   </div>
   <div class="sidebar-widget category mb-4">
      <h5 class="mb-4 has-text-centered">Catgeory</h5>
      <ul class="list-group">
         <li>
            <a href="#!" class="is-flex is-justify-content-space-between is-align-items-center">
            Cras justo odio
            <span class="count">14</span>
            </a>
         </li>
         <li>
            <a href="#!" class="is-flex is-justify-content-space-between is-align-items-center">
            Dapibus ac facilisis in
            <span class="count">2</span>
            </a>
         </li>
         <li>
            <a href="#!" class="is-flex is-justify-content-space-between is-align-items-center">
            Morbi leo risus
            <span class="count">1</span>
            </a>
         </li>
      </ul>
   </div>
   <div class="sidebar-widget tags mb-3">
      <h5 class="mb-4 has-text-centered w-100">Tags</h5>
      <div>
         <a href="#">Web</a>
         <a href="#">agency</a>
         <a href="#">company</a>
         <a href="#">creative</a>
         <a href="#">html</a>
         <a href="#">Marketing</a>
         <a href="#">Social Media</a>
         <a href="#">Branding</a>
      </div>
   </div>
</div>
         </div>   
      </div>
   </div>
</section>
@endsection