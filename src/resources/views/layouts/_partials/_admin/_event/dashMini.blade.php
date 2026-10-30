@php
	$eventStatus = match ($event->status) {
		'PUBLISHED' => ['label' => 'Live', 'colour' => 'success', 'icon' => 'fa-circle-check'],
		'DRAFT' => ['label' => 'Draft', 'colour' => 'danger', 'icon' => 'fa-circle-xmark'],
		'PREVIEW' => ['label' => 'Preview', 'colour' => 'warning', 'icon' => 'fa-ban'],
		'PRIVATE' => ['label' => 'Private', 'colour' => 'warning', 'icon' => 'fa-ban'],
		default => ['label' => $event->status, 'colour' => 'success', 'icon' => 'fa-circle-question'],
	};
@endphp
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3 mb-3">
	<div class="col">
		<x-admin-stat-card
			:icon="$eventStatus['icon']"
			:value="$eventStatus['label']"
			:colour="$eventStatus['colour']"
			label="Event Status"
			href="/admin/events/{{ $event->slug }}"
			link-text="View Event Page"
		/>
	</div>
	<div class="col">
		<x-admin-stat-card
			icon="fa-wheelchair"
			:value="$event->getSeatedCount()"
			label="Seated Participants"
			href="/admin/events/{{ $event->slug }}/seating"
			link-text="View Seating Plans"
		/>
	</div>
	<div class="col">
		<x-admin-stat-card
			icon="fa-user"
			:value="$event->eventParticipants->count()"
			label="Attendees"
			href="/admin/events/{{ $event->slug }}/participants"
			link-text="View Attendees"
		/>
	</div>
	<div class="col">
		<x-admin-stat-card
			icon="fa-ticket"
			:value="config('app.currency_symbol') . number_format((float) $event->getTicketSalesCount(), 2)"
			label="Ticket Sales"
			href="/admin/events/{{ $event->slug }}/tickets"
			link-text="View Tickets"
		/>
	</div>
	<div class="col">
		<x-admin-stat-card
			icon="fa-calendar"
			:value="$event->getTimetableDataCount()"
			label="Scheduled Slots"
			href="/admin/events/{{ $event->slug }}/timetables"
			link-text="View Timetables"
		/>
	</div>
</div>
