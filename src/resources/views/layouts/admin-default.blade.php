<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="">
		<meta name="author" content="">

		<title>
			@hasSection ('page_title')
				@yield('page_title') | {{ config('app.name') }}
			@else
				{{ config('app.name') }} Admin
			@endif
		</title>

		<link rel="icon" type="image/png" sizes="32x32" href="{{ config('app.favicon') }}">
		
		@include ('layouts._partials.color-mode-head')

		<!-- Admin CSS and JS (Bootstrap 5, Font Awesome, charts, Summernote editor) -->
		@vite(['resources/assets/sass/admin.scss', 'resources/js/admin.js'])

		<script>
			function ConfirmDelete()
			{
				var x = confirm("Are you sure you want to delete?");
				if (x)
					return true;
				else
					return false;
			}
			function ConfirmSubmit()
			{
				var x = confirm("Are you sure you want to submit?");
				if (x)
					return true;
				else
					return false;
			}
		</script>

	</head>
	<body class="admin">
		@include ('layouts._partials._admin.navigation')
		<main class="admin-main">
			@foreach (['danger', 'warning', 'success', 'info'] as $msg)
				@if (Session::has('alert-' . $msg))
					<div class="alert alert-{{ $msg }} alert-dismissible">
						<b>{{ Session::get('alert-' . $msg) }}</b>
						<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
					</div>
				@endif
			@endforeach
			@if ($errors->any())
				<div class="alert alert-danger">
					<ul class="mb-0">
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			@endif
			@yield('content')
		</main>
	</body>
</html>
