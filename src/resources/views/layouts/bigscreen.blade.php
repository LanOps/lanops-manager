<!DOCTYPE html>
<html lang="en" class="full-height" data-bs-theme="light" data-color-mode="fixed">
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
		@yield ('content')
	</body>
</html>
