@extends ('layouts.bigscreen')

@section ('page_title', config('app.name') . ' - Big Screen - Seating')

@section ('content')
<div class="container">
<!-- SEATING -->
@if (
		!$event->seatingPlans->isEmpty() && 
		(
			in_array('PUBLISHED', $event->seatingPlans->pluck('status')->toArray()) ||
			in_array('PREVIEW', $event->seatingPlans->pluck('status')->toArray())
		)
	)
		<div class="page-header">
			<a name="seating"></a>
			<h3>Seating Plans <small>- {{ $event->getSeatingCapacity() - $event->getSeatedCount() }} / {{ $event->getSeatingCapacity() }} Seats Remaining</small></h3>
		</div>
		<div class="panel-group">
			@foreach ($event->seatingPlans as $seatingPlan)
				@if ($seatingPlan->status != 'DRAFT')
					<div class="card">
						<div class="card-header" role="tab" id="headingOne">
							<h4 class="card-title">
								<a class="collapsed" role="button" data-bs-toggle="collapse" data-bs-parent="#accordion" href="#collapse_{{ $seatingPlan->slug }}" aria-expanded="true" aria-controls="collapse_{{ $seatingPlan->slug }}">
									{{ $seatingPlan->name }} <small>- {{ $seatingPlan->getCapacity() - $seatingPlan->getSeatedCount() }} / {{ $seatingPlan->getCapacity() }} Seats Remaining</small>
									@if ($seatingPlan->status != 'PUBLISHED')
										<small> - {{ $seatingPlan->status }}</small>
									@endif
								</a>
							</h4>
						</div>
						<div id="collapse_{{ $seatingPlan->slug }}" class="collapse @if ($loop->first) show @endif" role="tabpanel" aria-labelledby="collaspe_{{ $seatingPlan->slug }}">
							<div class="card-body">
								@include ('layouts._partials._seating.plan', ['horizontal' => false])
							</div>
						</div>
					</div>
				@endif
			@endforeach
		</div>
	@endif
</div>
@endsection
