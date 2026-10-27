<!DOCTYPE html>
<html lang="en" class="full-height">
	<head>
		<!-- Google tag (gtag.js) -->
		<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('app.google_tag_id') }}"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());

			gtag('config', "{{ config('app.google_tag_id') }}");
		</script>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="icon" type="image/png" sizes="32x32" href="{{ config('app.favicon') }}">
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,300,700' rel='stylesheet' type='text/css' />

		@include ('layouts._partials.color-mode-head')
		@vite(['resources/assets/sass/app.scss', 'resources/js/app.js'])
    	
		<title>
			@hasSection ('page_title')
				@yield ('page_title') | {{ config('app.name') }}
			@else
				{{ config('app.tagline') }} | {{ config('app.name') }}
			@endif
		</title>
		<meta name="keywords" content="{{ config('app.seo_keywords') }}"/>
		<x-seo::meta />
	</head>
	<body class="full-height">
		@include ('layouts._partials.navigation')
		<div class="container" style="margin-top:30px;">
			<div class='row'>
				@foreach (['danger', 'warning', 'success', 'info'] as $msg)
					@if (Session::has('alert-' . $msg))
						<div class="col-12" style="margin-top:30px; margin-bottom:-40px;">
							<div class="alert alert-{{ $msg }} alert-dismissible">
								<b>{{ Session::get('alert-' . $msg) }}</b>
								<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
							</div>
						</div>
					@endif
				@endforeach
				@if (isset($errors) && $errors->any())
					<div class="col-12" style="margin-top:30px; margin-bottom:-40px;">
						<div class="alert alert-danger">
							<ul class="list-unstyled">
								@foreach ($errors->all() as $error)
									<li><strong>{{ $error }}</strong></li>
								@endforeach
							</ul>
						</div>
					</div>
				@endif
			</div>
		</div>
		<main class="page-content">
			@yield ('content')
		</main>
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
		<div class="stats section-padding">
			<div class="container">
				<div class="row">
					<div class="col-md-4 text-center">
						<div class="stats-number">
							{{ Helpers::getEventTotal() }}
						</div>
						<hr />
						<div class="stats-title">
							EVENTs we've hosted
						</div>
					</div>

					<div class="col-md-4 text-center">
						<div class="stats-number">
							{{ Helpers::getEventParticipantTotal() }}
						</div>
						<hr />
						<div class="stats-title">
							PLAYERs we've entertained
						</div>
					</div>

					<div class="col-md-4 text-center">
						<div class="stats-number">
							A LOT
						</div>
						<hr />
						<div class="stats-title">
							Pizzas We've Ordered
						</div>
					</div>
				</div>
			</div>
		</div>
		<footer class="footer">
			<div class="container">
				<div class="row align-items-center pt-md-5">
					<div class="col-lg-4 d-none d-lg-block">
						<img class="img-fluid" src="{{ config('app.logo') }}" alt="{{ config('app.name') }} logo footer">
					</div>
					<div class="col-lg-8 col-12 text-center">
						<div class="row">
							<div class="col-md-6">
								<h2>Links</h2>
								<p class="d-none"><a href="/contact">Contact Us</a></p>
								<p><a href="/news">News</a></p>
								<p><a href="/info">Frequently Asked Questions</a></p>
								<p><a href="/terms">Terms & Conditions</a></p>
								<p><a href="/about">About Us</a></p>
								<p><a href="/polls">Polls</a></p>
								<p class="d-none">Lan Guide</p>
							</div>
							<div class="col-md-6">
								<h2>Connect</h2>
								@if (config('app.facebook_link') != "")
									<p><a target="_blank" href="{{ config('app.facebook_link') }}">Facebook</a></p>
								@endif
								@if (config('app.bsky_link') != "")
									<p><a target="_blank" href="{{ config('app.bsky_link') }}">BSKY</a></p>
								@endif
								@if (config('app.discord_link') != "")
									<p><a target="_blank" href="{{ config('app.discord_link') }}">Discord</a></p>
								@endif
								@if (config('app.steam_link') != "")
									<p><a target="_blank" href="{{ config('app.steam_link') }}">Steam</a></p>
								@endif
								@if (config('app.youtube_link') != "")
									<p><a target="_blank" href="{{ config('app.youtube_link') }}">Youtube</a></p>
								@endif
							</div>
						</div>
					</div>
					<div class="col-12 text-center">
						<p>© {{ config('app.name') }} {{ date("Y") }}. All rights reserved.</p>
					</div>
				</div>
			</div>
		</footer>
	@stack('scripts')
	</body>
</html>
