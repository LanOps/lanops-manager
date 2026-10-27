@extends ('layouts.default')

@section ('page_title', config('app.name') . ' Tournaments - ' . $tournament->name)

@section ('content')

<div class="container">
	<div class="page-header">
		<h1>
			{{ $tournament->name }}
		</h1>
		<p><a href="/tournaments">&larr; All tournaments</a></p>
	</div>
	@include('layouts._partials._tournaments.index')
</div>

@endsection
