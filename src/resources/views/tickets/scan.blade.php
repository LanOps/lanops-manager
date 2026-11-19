{{--
	What a ticket's QR code opens (TicketsController::retrieve).
	$mode: admin (door sign-in), owner (full details) or public (event, ticket type, valid or not).
	$status: valid, signed_in, cancelled or finished.
--}}
@extends ('layouts.default')

@section ('page_title', $mode === 'admin' ? 'Sign In Ticket' : 'Ticket')

@php
	if ($participant->ticket) {
		$ticketName = $participant->ticket->name;
	} elseif ($participant->staff) {
		$ticketName = 'Staff Ticket';
	} else {
		$ticketName = 'Free Ticket';
	}
	$holder = $participant->user;
	$realName = $holder ? trim($holder->firstname . ' ' . $holder->surname) : '';
	$seat = $participant->trashed() ? null : $participant->seat;
	$dates = date('D jS M Y', strtotime($event->start));
	if (date('Y-m-d', strtotime($event->start)) !== date('Y-m-d', strtotime($event->end))) {
		$dates .= ' – ' . date('D jS M Y', strtotime($event->end));
	}
	// Ticket holders can open Private events' pages too
	$eventIsPublic = in_array($event->status, ['PUBLISHED', 'PRIVATE']);
@endphp

@section ('content')

<div class="container">
	<div class="row justify-content-center">
		<div class="col-12 col-sm-10 col-md-8 col-lg-6">

			@if ($mode === 'admin')
				{{-- Door page: one glance tells staff whether to let them in --}}
				<div class="card my-4 ticket-scan">
					@if ($status === 'cancelled')
						<div class="card-header text-center bg-danger text-white py-3">
							<div class="h3 mb-0">Cancelled ticket</div>
							<div>Refunded or cancelled. Do not sign in.</div>
						</div>
					@elseif ($status === 'signed_in')
						<div class="card-header text-center bg-warning text-dark py-3">
							<div class="h3 mb-0">Already signed in</div>
							<div>This ticket has been used to sign in.</div>
						</div>
					@else
						<div class="card-header text-center bg-success text-white py-3">
							<div class="h3 mb-0">Valid ticket</div>
							<div>Not signed in yet</div>
						</div>
					@endif
					<div class="card-body">
						<div class="d-flex align-items-center gap-3 mb-3">
							<x-avatar :member="$holder" class="rounded-circle flex-shrink-0" width="72" height="72" />
							<div class="min-w-0">
								<div class="h4 mb-0 text-break">{{ $holder ? $holder->username : 'Unknown user' }}</div>
								@if ($realName !== '')
									<div class="fs-5 text-break">{{ $realName }}</div>
								@endif
							</div>
						</div>

						<dl class="row mb-0 fs-5">
							<dt class="col-4">Ticket</dt>
							<dd class="col-8">{{ $ticketName }}</dd>
							<dt class="col-4">Seat</dt>
							<dd class="col-8">
								@if ($seat)
									<strong>{{ $seat->seat }}</strong>
									@if ($seat->seatingPlan)
										<span class="text-body-secondary">({{ $seat->seatingPlan->name }})</span>
									@endif
								@else
									<span class="text-body-secondary">Not seated</span>
								@endif
							</dd>
							<dt class="col-4">Event</dt>
							<dd class="col-8">{{ $event->display_name }}<br><small class="text-body-secondary">{{ $dates }}</small></dd>
						</dl>

						@if ($status === 'finished')
							<div class="alert alert-warning mt-3 mb-0">This event has already finished.</div>
						@elseif ($isUpcoming && $status === 'valid')
							<div class="alert alert-warning mt-3 mb-0">This ticket is for an event that hasn't started yet. Check it's the right event.</div>
						@endif
						@if ($giftPending && $status !== 'cancelled')
							<div class="alert alert-info mt-3 mb-0">This ticket was sent as a gift, but the gift hasn't been accepted yet, so it is still in the sender's name.</div>
						@endif

						@if ($status === 'valid' || $status === 'finished')
							{{ html()->form('POST', url('/admin/events/' . $event->slug . '/participants/' . $participant->id . '/signin'))->class('mt-4')->open() }}
								<button type="submit" class="btn btn-success btn-lg w-100 py-3 fs-3">
									Sign in
								</button>
							{{ html()->form()->close() }}
						@endif
					</div>
					<div class="card-footer d-flex flex-wrap justify-content-between gap-2">
						@if (!$participant->trashed())
							<a href="/admin/events/{{ $event->slug }}/participants/{{ $participant->id }}">Participant details</a>
						@endif
						<a href="/admin/events/{{ $event->slug }}/participants">All participants</a>
					</div>
				</div>

			@elseif ($mode === 'owner')
				<div class="card my-4">
					<div class="card-header">
						<h1 class="h4 mb-0">Your ticket</h1>
					</div>
					<div class="card-body">
						@if ($status === 'cancelled')
							<div class="alert alert-danger">This ticket has been cancelled{{ $participant->refund ? ' and refunded' : '' }}. It can't be used to get in.</div>
						@elseif ($status === 'signed_in')
							<div class="alert alert-success">You're signed in to this event.</div>
						@elseif ($status === 'finished')
							<div class="alert alert-secondary">This event has finished.</div>
						@endif
						@if ($giftPending && $status !== 'cancelled')
							<div class="alert alert-info">You've sent this ticket as a gift. It's still yours until the gift is accepted.</div>
						@endif

						<dl class="row mb-0">
							<dt class="col-sm-4">Event</dt>
							<dd class="col-sm-8">
								@if ($eventIsPublic)
									<a href="/events/{{ $event->slug }}">{{ $event->display_name }}</a>
								@else
									{{ $event->display_name }}
								@endif
								<br><small class="text-body-secondary">{{ date('H:i D jS M Y', strtotime($event->start)) }} – {{ date('H:i D jS M Y', strtotime($event->end)) }}</small>
							</dd>
							@if ($event->venue)
								<dt class="col-sm-4">Venue</dt>
								<dd class="col-sm-8">{{ $event->venue->display_name }}</dd>
							@endif
							<dt class="col-sm-4">Ticket</dt>
							<dd class="col-sm-8">{{ $ticketName }}</dd>
							<dt class="col-sm-4">Name</dt>
							<dd class="col-sm-8">{{ $holder->username }}@if ($realName !== '') ({{ $realName }})@endif</dd>
							<dt class="col-sm-4">Seat</dt>
							<dd class="col-sm-8">
								@if ($seat)
									{{ $seat->seat }}@if ($seat->seatingPlan) in {{ $seat->seatingPlan->name }}@endif
								@elseif ($status === 'cancelled')
									None
								@else
									Not seated yet
								@endif
							</dd>
							<dt class="col-sm-4">Issued</dt>
							<dd class="col-sm-8">
								@if ($participant->purchase)
									Bought {{ date('jS M Y', strtotime($participant->purchase->created_at)) }}
								@elseif ($participant->staff || $participant->free)
									Given by LanOps
								@else
									{{ date('jS M Y', strtotime($participant->created_at)) }}
								@endif
								@if ($participant->gift_accepted && ($gifter = $participant->getGiftedByUser()))
									<br><small class="text-body-secondary">A gift from {{ $gifter->username }}</small>
								@endif
							</dd>
							<dt class="col-sm-4">Signed in</dt>
							<dd class="col-sm-8">{{ $participant->signed_in ? 'Yes' : 'Not yet' }}</dd>
						</dl>
					</div>
					@if (!$participant->trashed())
						<div class="card-footer d-flex flex-wrap justify-content-between gap-2">
							<a href="/account/tickets/{{ $participant->id }}">Show my QR code</a>
							<a href="/account">All my tickets</a>
						</div>
					@endif
				</div>

			@else
				{{-- Anyone else: enough to check a ticket is real, nothing about who holds it --}}
				<div class="card my-4 text-center">
					<div class="card-header">
						<h1 class="h4 mb-0">LanOps ticket</h1>
					</div>
					<div class="card-body">
						<p class="lead mb-1">{{ $event->display_name }}</p>
						<p class="text-body-secondary">{{ $dates }}</p>
						<p class="fs-5 mb-3">{{ $ticketName }}</p>
						@if ($status === 'cancelled')
							<div class="alert alert-danger mb-0">This ticket is not valid: it has been cancelled.</div>
						@elseif ($status === 'finished')
							<div class="alert alert-secondary mb-0">This ticket was for an event that has finished.</div>
						@else
							<div class="alert alert-success mb-0">This is a valid ticket.</div>
						@endif
					</div>
					<div class="card-footer">
						@guest
							Is this your ticket? <a href="/login">Log in</a> to see its details.
						@else
							This ticket belongs to someone else.
						@endguest
					</div>
				</div>
			@endif

		</div>
	</div>
</div>

@endsection
