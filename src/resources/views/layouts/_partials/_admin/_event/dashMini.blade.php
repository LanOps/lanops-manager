<div class="row">

	<div class="col-lg-2 col-md-6 col-12">
		@if ($event->status == 'PUBLISHED')
			@php 
				$eventStatusColor = 'success'; 
				$eventStatusIcon = 'fa-circle-check';
			@endphp
		@elseif ($event->status == 'DRAFT')
			@php 
				$eventStatusColor = 'danger';
				$eventStatusIcon = 'fa-circle-xmark';
			@endphp
		@elseif ($event->status == 'PREVIEW' || $event->status == 'PRIVATE')
			@php 
				$eventStatusColor = 'warning'; 
				$eventStatusIcon = 'fa-ban';
			@endphp
		@else
			@php
				$eventStatusColor = 'success';
				$eventStatusIcon = 'fa-circle-question ';
			@endphp
		@endif
		<div class="card border-{{ $eventStatusColor }}">
			<div class="card-header text-bg-{{ $eventStatusColor }}">
				<div class="row">
					<div class="col-3">
						<i class="fa {{ $eventStatusIcon }} fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">
							@if ($event->status == 'PUBLISHED')
								<small>Live</small>
							@elseif ($event->status == 'DRAFT')
								<small>Draft</small>
							@elseif ($event->status == 'PREVIEW')
								<small>Preview</small>
							@elseif ($event->status == 'PRIVATE')
								<small>Private</small>
							@else
								<small>{{ $event->status }}</small>
							@endif
						</div>
						<div>Event Status</div>
					</div>
				</div>
			</div>
			<a href="/admin/events/{{ $event->slug }}">
				<div class="card-footer">
					<span class="float-start">View Event Page</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	<div class="col-lg-2 col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-wheelchair fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ $event->getSeatedCount() }}</div>
						<div>Seated Participants</div>
					</div>
				</div>
			</div>
			<a href="/admin/events/{{ $event->slug }}/seating">
				<div class="card-footer">
					<span class="float-start">View Seating Plans</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	<div class="col-lg-2 col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-user fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ $event->eventParticipants->count() }}</div>
						<div>Attendees</div>
					</div>
				</div>
			</div>
			<a href="/admin/events/{{ $event->slug }}/participants">
				<div class="card-footer">
					<span class="float-start">View Attendees</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	<div class="col-lg-2 col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-ticket fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ config('app.currency_symbol') }}{{ $event->getTicketSalesCount() }}</div>
						<div>Ticket Sales</div>
					</div>
				</div>
			</div>
			<a href="/admin/events/{{ $event->slug }}/tickets">
				<div class="card-footer">
					<span class="float-start">View Tickets</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>
	<div class="col-lg-2 col-md-6 col-12">
		<div class="card">
			<div class="card-header">
				<div class="row">
					<div class="col-3">
						<i class="fa fa-calendar fa-5x"></i>
					</div>
					<div class="col-9 text-end">
						<div class="huge">{{ $event->getTimetableDataCount() }}</div>
						<div>Scheduled Slots</div>
					</div>
				</div>
			</div>
			<a href="/admin/events/{{ $event->slug }}/timetables">
				<div class="card-footer">
					<span class="float-start">View Timetables</span>
					<span class="float-end"><i class="fa fa-circle-arrow-right"></i></span>
					<div class="clearfix"></div>
				</div>
			</a>
		</div>
	</div>

</div>