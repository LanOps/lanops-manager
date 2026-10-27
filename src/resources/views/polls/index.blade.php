@extends ('layouts.default')

@section ('page_title', config('app.name') . ' Polls')

@section ('content')
			
<div class="container">
	<div class="page-header">
		<h1>
			Polls
		</h1> 
	</div>
	@foreach ($activePolls as $poll)
		<div class="card">
			<div class="card-header">
				<strong><a href="/polls/{{ $poll->slug }}">{{ $poll->name }}</a></strong>
				@if ($poll->status != 'PUBLISHED')
					<small> - {{ $poll->status }}</small>
				@endif
			</div>
			<div class="card-body">
				@if (!empty($poll->description))
					<p class="bg-info-subtle text-info-emphasis padding">{{ $poll->description }}</p>
				@endif
				<p class="bg-info-subtle text-info-emphasis padding">Options: {{ $poll->options->count() }}</p>
				<p class="bg-info-subtle text-info-emphasis padding">Votes: {{ $poll->getTotalVotes() }}</p>
			</div>
		</div>
	@endforeach
	<hr>
	<div class="page-header">
		<h3>
			Previous Polls
		</h3> 
	</div>
	@foreach ($endedPolls as $poll)
		<div class="card">
			<div class="card-header">
				<strong><a href="/polls/{{ $poll->slug }}">{{ $poll->name }}</a></strong>
				@if ($poll->status != 'PUBLISHED')
					<small> - {{ $poll->status }}</small>
				@endif
			</div>
			<div class="card-body">
				@if (!empty($poll->description))
					<p class="bg-info-subtle text-info-emphasis padding">{{ $poll->description }}</p>
				@endif
				<p class="bg-success-subtle text-success-emphasis padding">Start: {{ date('H:i d-m-Y', strtotime($poll->start)) }}</p>
				<p class="bg-danger-subtle text-danger-emphasis padding">End: {{ date('H:i d-m-Y', strtotime($poll->end)) }}</p>
				<p class="bg-info-subtle text-info-emphasis padding">Options: {{ $poll->options->count() }}</p>
				<p class="bg-info-subtle text-info-emphasis padding">Votes: {{ $poll->getTotalVotes() }}</p>
			</div>
		</div>
	@endforeach
	{{ $endedPolls->links() }}
</div>

@endsection
