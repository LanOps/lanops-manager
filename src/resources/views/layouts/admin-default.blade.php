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
		
		<!-- Admin CSS -->
		@vite(['resources/assets/sass/admin.scss'])

		<!-- Custom Fonts -->
		<link href="/fonts/css/font-awesome.min.css" rel="stylesheet" type="text/css">

		<!-- Jquery Core JavaScript -->
		<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-ui@1.14.2/dist/themes/smoothness/jquery-ui.min.css" integrity="sha384-e4Bm/JKXqLbEnnDNLZIbB0u9VBy3H9D+TNdLb22ybxTLsmtWgRhQ3/BKEgJ13zU2" crossorigin="anonymous">
		<script src="https://cdn.jsdelivr.net/npm/jquery-ui@1.14.2/dist/jquery-ui.min.js" integrity="sha384-tBcEcHGtNy7/Mx08+YxuvQ6v6s0N2jgehtFiT+bLtGwTj/txXtB/L5GqXfggm5sS" crossorigin="anonymous"></script>

		<!-- Bootstrap Core JavaScript -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

		<!-- Morris Charts JavaScript -->
		<script src="/js/morris/raphael.min.js"></script>
		<script src="/js/morris/morris.min.js"></script>


		<!-- include summernote css/js -->
		<link href="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote.min.css" integrity="sha384-4rKS0JSi0EkLi/0w4eqYVvfCzP9JJv6EVochgkFUUGusUbyU/Yc8ZbvTO8JwsYiV" crossorigin="anonymous" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote.min.js" integrity="sha384-P/xUbGCYuaoeXvkk0bJlBa8ag1csGCLr1Z2LGD4SXKynQzTZIkWOaqYegoho3pIJ" crossorigin="anonymous"></script>

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
	<body>
		<div id="wrapper">
			@include ('layouts._partials._admin.navigation')
			<div id="page-wrapper">
				<div class="container-fluid">
					<div class='row'>
						@foreach (['danger', 'warning', 'success', 'info'] as $msg)
							@if (Session::has('alert-' . $msg))
								<p class="alert alert-{{ $msg }}">
									<b>{{ Session::get('alert-' . $msg) }}</b> <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
								</p>
							@endif
						@endforeach
						@if ($errors->any())
							<div class="alert alert-danger">
								<ul>
									@foreach ($errors->all() as $error)
										<li>{{ $error }}</li>
									@endforeach
								</ul>
							</div>
						@endif
					</div>
					@yield('content')
				</div>
			</div>    
		</div>
	</body>
</html>
