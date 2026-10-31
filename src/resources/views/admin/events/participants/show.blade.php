@extends ('layouts.admin-default')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Events - {{ $event->display_name }} - Participant - {{ $participant->user->username }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}/participants">Participants</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $participant->user->username }}
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">

		<div class="card">
			<div class="card-header">
				Edit Participant
			</div>
			<div class="card-body">
				@if (!empty($_POST))
					Successfully Posted
				@endif
				<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="seating_table">
						<thead>
							<tr>
								<th>User</th>
								<th>Name</th>
								<th>Seat</th>
								<th>Ticket</th>
								<th>Paypal Email</th>
								<th>Gift</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>
									{{ $participant->user->username }}
									@if ($steamProfile = Helpers::getSteamProfile($participant->user->steamid))
										<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
									@endif
								</td>
								<td>{{ $participant->user->firstname }} {{ $participant->user->surname }}</td>
								<td>@if($participant->seat) {{ $participant->seat->seat }} @endif</td>
								<td>
									@if ($participant->ticket)
										{{ $participant->ticket->name }}
									@else
										No Ticket Bought / Free
									@endif
								</td>
								<td>@if ($participant->purchase) {{ $participant->purchase->paypal_email }} @endif</td>
								<td>
									@if ($participant->gift)
										<strong>Yes</strong>
										<small>Assigned by: {{ $participant->getGiftedByUser()->username }}</small>
									@else
										No
									@endif
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

	</div>
	<div class="col-lg-4">

		<div class="card">
			<div class="card-header">
				More Editing
			</div>
			<div class="card-body">
				@if ($participant->signed_in)
					<h4>User is signed in at present at the event</h4>
				@endif
				{{ html()->label('Ticket', '') }}
				@if ($participant->ticket)
					<p>{{ $participant->ticket->name }}</p>
				@else
					<p>No Ticket Bought / Free</p>
				@endif
				@if ($participant->purchase && $participant->purchase->referral_discount_total > 0)
					{{ html()->label('Referral Code Discount Applied!', '') }}
					<p>{{ config('app.currency_symbol') }}{{ $participant->purchase->referral_discount_total }}</p>
				@endif
				@if ($participant->purchase && $participant->purchase->referral_code_user_id)
					{{ html()->label('Referral Code Used!', '') }}
					<p>{{ $participant->purchase->referralUser->username }}'s Referral Code Used</p>
				@endif
				@if ($participant->purchase)
					<hr>
					<div class="mb-3">
						<a href="/admin/purchases/{{ $participant->purchase->id }}"><button class="btn w-100 btn-success">Purchase Info</button></a>
					</div>
				@endif
				@if (!$participant->signed_in)
					{{ html()->form('POST', url('/admin/events/' . $event->slug . '/participants/' . $participant->id . '/transfer'))->open() }}
						<div class="mb-3">
							{{ html()->label('Transfer to event', 'event_id') }}
							{{ html()->select('event_id', Helpers::getEventNames('DESC', 0, true), '')->class('form-select') }}
						</div>
						<div class="mb-3">
							<button type="submit" class="btn btn-success w-100">Transfer</button>
						</div>
					</form>
					<hr>
					{{ html()->form('POST', url('/admin/events/' . $event->slug . '/participants/' . $participant->id . '/signin'))->open() }}
						<div class="mb-3">
							<button type="submit" class="btn btn-success w-100">Sign in</button>
						</div>
					</form>
				@endif
				@if ($participant->purchase)
					<hr>
					<div class="mb-3">
						<a href="/admin/purchases/{{ $participant->purchase->id }}#refund" class="btn btn-danger w-100">Refund / Cancel</a>
					</div>
				@endif
			</div>
		</div>

	</div>
</div>

@endsection