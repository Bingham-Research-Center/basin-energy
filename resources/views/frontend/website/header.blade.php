<header class="navigation">
	<div class="header-top is-hidden-mobile">
		<div class="container">
			<div class="columns is-justify-content-space-between is-align-items-center">
				<div class="column is-9">
					<div class="header-top-info">
						<!-- <a href="tel:+23-345-67890"><i class="fa fa-phone mr-2"></i><span>+23-345-67890</span></a> -->
						<a href="mailto:support@gmail.com" ><i class="fa fa-envelope mr-2"></i><span>support@gmail.com</span></a>
						<a href="basinenergy.info"><i class="fa fa-globe mr-2"></i>BasinEnergy.info</a>
					</div>
				</div>
				<div class="column is-3">
					<div class="header-top-socials has-text-centered has-text-right-tablet">
						<a href="https://www.facebook.com/themefisher" target="_blank"><i class="ti-facebook"></i></a>
						<a href="https://twitter.com/themefisher" target="_blank"><i class="ti-twitter"></i></a>
						<a href="https://github.com/themefisher/" target="_blank"><i class="ti-github"></i></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<nav id="navbar" class="navbar main-nav">
		<div class="container">
			<div class="navbar-brand ml-0">
				<a class="navbar-item" href="{{ route('frontend.index') }}">
					<!-- <img src="images/logo/logo.png" alt="logo"> -->
					<h2 class="has-text-white" style="font-size:2rem">BasinEnergy</h2>
				</a>
				<button role="button" class="navbar-burger burger" data-hidden="true" data-target="navigation">
					<span aria-hidden="true"></span>
					<span aria-hidden="true"></span>
					<span aria-hidden="true"></span>
				</button>
			</div>

			<div class="navbar-menu mr-0" id="navigation">
				<ul class="navbar-end">					 
					<li class="navbar-item">
						<a class="navbar-link is-arrowless" href="{{ route('frontend.index') }}">Home</a>
					</li>
					
					<li class="navbar-item">
						<a class="navbar-link is-arrowless" href="{{ route('frontend.pages.about') }}">About</a>
					</li>
					
					<li class="navbar-item">
						<a class="navbar-link is-arrowless" href="{{ route('frontend.pages.service') }}">Service</a>
					</li>
					
					<li class="navbar-item">
						<a class="navbar-link is-arrowless" href="{{ route('frontend.pages.portfolio') }}">Portfolio</a>
					</li>

					<li class="navbar-item has-dropdown is-hoverable">
						<a class="navbar-link is-arrowless">Blog +</a>
						<div class="navbar-dropdown">
							<a class="navbar-item" href="{{ route('frontend.pages.blog') }}">Blog Grid</a>
							<a class="navbar-item" href="{{ route('frontend.pages.blogSingle') }}">Blog Single</a>
						</div>
					</li>
					
					<li class="navbar-item">
						<a class="navbar-link is-arrowless" href="{{ route('frontend.pages.contact') }}">Contact</a>
					</li>
				@auth
					@if(method_exists(auth()->user(), 'hasVerifiedEmail') && ! auth()->user()->hasVerifiedEmail())
						<li class="navbar-item">
							<a href="javascript:void(0);"
							id="verify-trigger"
							class="navbar-link is-arrowless quote-btn bg-warning rounded-btn letter-spacing is-uppercase">
								<i class="ti-alert"></i> MY ACCOUNT <i class="ti-angle-right"></i>
							</a>
						</li>
					@else
						<li class="navbar-item">
							<a href="{{ route('frontend.user.dashboard') }}"
							class="navbar-link is-arrowless quote-btn bg-primary rounded-btn letter-spacing is-uppercase">
								MY ACCOUNT <i class="ti-angle-right"></i>
							</a>
						</li>
					@endif
				@else
					<li class="navbar-item">
						<a href="javascript:void(0);"
						id="login-trigger"
						class="navbar-link is-arrowless quote-btn bg-primary rounded-btn letter-spacing is-uppercase">
							LOG IN <i class="ti-angle-right"></i>
						</a>
					</li>
				@endauth
				</ul>
			</div>
		</div>
	</nav>
</header>