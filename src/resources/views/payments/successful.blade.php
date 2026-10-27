@extends ('layouts.default')

@section ('page_title', 'Payment Successful!')

@section ('content')

<div class="container">
	<div class="page-header">
		<h1>
			Thank you for your Payment!
		</h1> 
	</div>
	<div class="row">
		<div class="col-12 col-md-8">
			<h3>Your Tickets are now active</h3>
			<h4>You may now go to the <a href="/events/{{ $purchase->participants[0]->event->slug }}/#seating">Events Page and Book a Seat!</a></h4>
			<p><strong>Purchase ID:</strong> {{ $purchase->id }}</p>
			<p><strong>Payment Method:</strong> {{ $purchase->getPurchaseType() }}</p>
			@if (is_null(Auth::user()->discord_id))
				<div class="alert alert-info">
					You havent linked your discord account! <a href="/account">Click here to link it!</a>
				</div>
			@endif
			<h3>Tickets</h3>
			<hr>
			<div class="row">
				@foreach ($purchase->participants as $participant)
					<div class="col-lg-4 col-sm-6 col-12 text-center">
						<div class="card">
							<div class="card-body">
								<h5>{{ $participant->event->display_name }}</h5>
								<h5>{{ $participant->ticket->name }}</h5>
								<p>QR Codes Generating</p>
							</div>
						</div>
					</div>
				@endforeach
			</div>
		</div>
		<div class="col-12 col-md-4">
			<div class="card">
				<div class="card-header">
					<h3 class="card-title">Order Details</h3>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						@include ('layouts._partials._checkout.basket')
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection