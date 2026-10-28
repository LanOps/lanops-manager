<div class="card border-success">
	<div class="card-header bg-success-subtle text-success-emphasis">
		@if ($participant->ticket)
			<strong>{{ $participant->ticket->name }}</strong>
		@else
			@if ($participant->staff)
				<strong>Staff Ticket @if ($participant->seat) - Seat: {{$participant->seat->seat}} @endif</strong>
			@else
				<strong>Free Ticket @if ($participant->seat) - Seat: {{$participant->seat->seat}} @endif</strong>
			@endif
		@endif
		@if ($participant->gift == 1 && $participant->gift_accepted != 1)
			<span class="badge text-bg-info label-block float-end" style="margin-left: 3px; margin-top:2px;">This Ticket has been gifted!</span>
		@endif
		@if ($participant->ticket && !$participant->ticket->seatable)
			<span class="badge text-bg-info label-block float-end" style="margin-top:2px;">This Ticket is not eligable for a seat</span>
		@endif
		@if (\Carbon\Carbon::parse($participant->event->end)->isPast())
			<span class="badge text-bg-info label-block float-end" style="margin-top:2px;">PREVIOUS EVENT</span>
		@endif
	</div>
	<div class="card-body">
		<div class="row" style="display: flex; align-items: center;">
			@if (\Carbon\Carbon::parse($participant->event->end)->isPast())
				<div class="col-md-12 col-sm-12 col-12">
					<div class="alert alert-success">
						<strong>
							Ticket already claimed at previous event!
						</strong>
					</div>
				</div>
			@else
				<div class="col-md-8 col-sm-8 col-12">
					<div class="alert alert-success">
						<strong>
							{{ $participant->event->display_name }} @if ($participant->ticket && $participant->ticket->seatable) - Seat: @if ($participant->seat) {{$participant->seat->seat}} @if ($participant->seat->seatingPlan) <small>in {{$participant->seat->seatingPlan->name}}</small> @endif @else Not Seated @endif @endif
						</strong>
					</div>
					@if ($participant->gift != 1 && $participant->gift_accepted != 1)
						<button class="btn btn-md btn-success w-100" onclick="giftTicket('{{ $participant->id }}')" data-bs-toggle="modal" data-bs-target="#giftTicketModal">
							Gift Ticket
						</button>
					@endif
					@if ($participant->gift == 1 && $participant->gift_accepted != 1)
						<label>Gift URL:</label>
						<p>
							<strong>
								{{ config('app.url') }}/gift/accept/?url={{ $participant->gift_accepted_url }}
							</strong>
						</p>
						{{ html()->form('POST', url('/gift/' . $participant->id . '/revoke'))->id('revokeGiftTicketForm')->open() }}
							<button type="submit" class="btn btn-primary btn-md w-100">Revoke Gift</button>
						</form>
					@endif
					@if ($participant->seat && $participant->seat->seatingPlan)
						<hr>
						{{ html()->form('POST', url('/events/' . $participant->event->slug . '/seating/' . $participant->seat->seatingPlan->slug))->open() }}
							@method('DELETE')
							{{ html()->hidden('user_id', $user->id)->class('form-control') }} 
							{{ html()->hidden('participant_id', $participant->id)->class('form-control') }} 
							{{ html()->hidden('seat_number', $participant->seat->seat)->class('form-control') }} 
							<h5>
								<button class="btn btn-danger w-100"> 
									Remove Seating
								</button>
							</h5>
						</form>
					@endif
				</div>
				<div class="offset-md-2 col-md-2 offset-sm-2 col-sm-4 col-12">
					<img class="img img-fluid" src="{{ $participant->getFirstMediaUrl('qr') }}"/>
				</div>
			@endif
		</div>
	</div>
</div>

@include ('layouts._partials._gifts.modal')