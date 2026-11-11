@extends ('layouts.default')

@section ('page_title', 'My Ticket')

@section ('content')

	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-sm-10 col-md-7 col-lg-5">
				<div class="card text-center my-4">
					<div class="card-header">
						<h1 class="h4 mb-0">{{ $participant->event->display_name }}</h1>
					</div>
					<div class="card-body">
						<p class="lead mb-1">
							@if ($participant->ticket)
								{{ $participant->ticket->name }}
							@elseif ($participant->staff)
								Staff Ticket
							@else
								Free Ticket
							@endif
						</p>
						<p class="mb-3">
							@if ($participant->seat)
								Seat {{ $participant->seat->seat }}@if ($participant->seat->seatingPlan) in {{ $participant->seat->seatingPlan->name }}@endif
							@else
								Not seated
							@endif
						</p>
						@if ($participant->signed_in)
							<div class="alert alert-success">You're signed in to this event.</div>
						@endif
						@if ($qr = $participant->getFirstMediaUrl('qr'))
							{{-- White box so the code scans in dark mode too --}}
							<div class="bg-white p-3 rounded d-inline-block w-100">
								<img class="img-fluid w-100" src="{{ $qr }}" alt="Ticket QR code">
							</div>
							<p class="text-body-secondary mt-3 mb-0">Show this code at the door.</p>
						@else
							<div class="alert alert-warning mb-0">Your QR code isn't ready yet. Please show your name at the door, or try again in a minute.</div>
						@endif
					</div>
					<div class="card-footer">
						<a href="/account">All my tickets</a>
					</div>
				</div>
			</div>
		</div>
	</div>

@endsection
