<li class="nav-item dropdown">
	<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">Next Event: {{ Helpers::getNextEventName() }}</a>
	<ul class="dropdown-menu">
		<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}">Information</a></li>
		<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}#purchaseTickets">Tickets</a></li>
		<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}#timetable">Timetable</a></li>
		<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}#attendees">Attendees</a></li>
		<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}#seating">Seating</a></li>
		@if(Auth::user())
			<li><a class="dropdown-item" href="/events/{{ Helpers::getNextEventSlug() }}#yourTickets">Your Tickets</a></li>
		@endif
	</ul>
</li>
<li class="nav-item dropdown">
	<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" role="button" aria-expanded="false">Upcoming Events</a>
	<ul class="dropdown-menu">
		@if ( count($events) > 0 )
			@foreach ( $events as $event )
				<li>
					<a class="dropdown-item" href="/events/{{ $event->slug }}">
						{{ $event->display_name }}
						@if ($event->status != 'PUBLISHED')
							- {{ $event->status }}
						@endif
					</a>
				</li>
			@endforeach
		@else
			<li><a class="dropdown-item" href="#">No Future events</a></li>
		@endif
	</ul>
</li>
<li class="nav-item"><a class="nav-link" href="/events">All Events</a></li>
