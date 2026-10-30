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

		<!-- Admin CSS and JS (Bootstrap 5, Font Awesome) -->
		@vite(['resources/assets/sass/admin.scss', 'resources/js/admin.js'])

		<!-- Jquery Core JavaScript -->
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-ui@1.14.2/dist/themes/smoothness/jquery-ui.min.css" integrity="sha384-e4Bm/JKXqLbEnnDNLZIbB0u9VBy3H9D+TNdLb22ybxTLsmtWgRhQ3/BKEgJ13zU2" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/jquery-ui@1.14.2/dist/jquery-ui.min.js" integrity="sha384-tBcEcHGtNy7/Mx08+YxuvQ6v6s0N2jgehtFiT+bLtGwTj/txXtB/L5GqXfggm5sS" crossorigin="anonymous"></script>

		<!-- include summernote css/js -->
		<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-bs5.min.css" integrity="sha384-NCIOkH1RWTLh0uk0cWmHMJbcBZE8aFTbBNELvTaRgLwGsGIgaacBRlWynjlAt69p" crossorigin="anonymous" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-bs5.min.js" integrity="sha384-FycAzMryzUZA6EwTBPuugMzUKzpNjcXJhQvibi8ej/+2/imEZ6kTAsxHGtrNn8Py" crossorigin="anonymous"></script>

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
	        $(document).ready(function() {
	            $('.wysiwyg-editor').summernote({
	              height:300,
	            });
	        });
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
