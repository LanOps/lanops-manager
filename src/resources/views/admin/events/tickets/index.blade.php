@extends ('layouts.admin-default')

@section ('page_title', 'Tickets - ' . $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Tickets</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a>
			</li>
			<li class="breadcrumb-item active">
				Tickets
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-chart-column fa-fw"></i> Ticket Breakdown
			</div>
			<div class="card-body">
				@php
					$ticketNames = $event->tickets->pluck('name');
					$ticketCounts = $event->tickets->map(fn ($ticket) => $ticket->participants()->count());
					$ticketIncome = $event->tickets->map(fn ($ticket, $index) => round($ticket->price * $ticketCounts[$index], 2));
				@endphp
				<div class="row g-4">
					<div class="col-md-6 col-12">
						<h5 class="mb-3">Purchase Breakdown</h5>
						<div class="admin-chart">
							<canvas
								data-chart="doughnut"
								data-chart-labels="{{ $ticketNames->toJson() }}"
								data-chart-values="{{ $ticketCounts->toJson() }}"
								data-chart-series="Tickets"
								data-chart-total-label="Tickets"
								aria-label="Tickets purchased by type"
								role="img"
							></canvas>
						</div>
					</div>
					<div class="col-md-6 col-12">
						<h5 class="mb-3">Income Breakdown</h5>
						<div class="admin-chart">
							<canvas
								data-chart="bar"
								data-chart-labels="{{ $ticketNames->toJson() }}"
								data-chart-values="{{ $ticketIncome->toJson() }}"
								data-chart-series="Income"
								data-chart-format="currency"
								aria-label="Income by ticket type"
								role="img"
							></canvas>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-ticket fa-fw"></i> Tickets
			</div>
			<div class="card-body">
				<table width="100%" class="table table-striped table-hover" id="dataTables-example">
					<thead>
						<tr>
							<th>Name</th>
							<th>Type</th>
							<th>Price</th>
							<th>Quantity</th>
							<th>Purchased</th>
							<th>Purchase Period</th>
							<th>Seatable</th>
							<th></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						@foreach ($event->tickets as $ticket)
							<tr class="table-row odd gradeX" data-href="/admin/events/{{ $event->slug }}/tickets/{{ $ticket->id }}">
								<td>
									{{ $ticket->name }}
								</td>
								<td>
									{{ $ticket->type }}
								</td>
								<td>
									{{ $ticket->price }}
								</td>
								<td>
									@if ($ticket->quantity == 0)
										N/A
									@else
										{{ $ticket->quantity }}
									@endif
								</td>
								<td>
									{{ $ticket->participants()->count() }}
								</td>
								<td>
									Start:
									@if ($ticket->sale_start)
										{{ date('H:i d-m-Y', strtotime($ticket->sale_start)) }} 
									@else
										N/A
									@endif
									-
									End:
									@if ($ticket->sale_end)
										{{ date('H:i d-m-Y', strtotime($ticket->sale_end)) }}
									@else
										N/A
									@endif
								</td>
								<td>
									@if ($ticket->seatable)
										Yes
									@else
										No
									@endif
								</td>
								<td width="15%">
									<a href="/admin/events/{{ $event->slug }}/tickets/{{ $ticket->id }}">
										<button type="button" class="btn btn-primary btn-sm w-100">Edit</button>
									</a>
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/events/' . $event->slug . '/tickets/' . $ticket->id))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
										@method('DELETE')
										<button type="submit" class="btn btn-danger btn-sm w-100" data-confirm="Are you sure to delete this Ticket?">Delete</button>
									</form>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<i class="fa fa-gift fa-fw"></i> Freebies
				<a name="freebies"></a>
			</div>
			<div class="card-body">
				<table width="100%" class="table table-striped table-hover" id="dataTables-example">
					<thead>
						<tr>
							<th>Name</th>
							<th>Free Tickets</th>
							<th>Staff Tickets</th>
							<th></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						{{-- One Steam request for the whole list, instead of one per row --}}
						@php
						    Helpers::getSteamProfiles($users->pluck('steamid')->all());
						@endphp
						@foreach ( $users as $user )
							<tr class="table-row odd gradeX">
								<td>
									{{ $user->username }}
									@if ($steamProfile = Helpers::getSteamProfile($user->steamid))
										<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
									@endif
								</td>
								<td>
									{{ $user->getFreeTickets($event->id)->count() }}
								</td>
								<td>
									{{ $user->getStaffTickets($event->id)->count() }}
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/events/' . $event->slug . '/freebies/gift'))->open() }}
										{{ csrf_field() }}
										<input type="hidden" name="user_id" value="{{ $user->id }}" />
										<button type="submit" name="action" class="btn btn-success btn-sm w-100">Free Ticket</button>
									</form>
								</td>
								<td width="15%">
									{{ html()->form('POST', url('/admin/events/' . $event->slug . '/freebies/admin'))->open() }}
										{{ csrf_field() }}
										<input type="hidden" name="user_id" value="{{ $user->id }}" />
										<button type="submit" name="action" class="btn btn-success btn-sm w-100">Admin Ticket</button>
									</form>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="col-lg-4">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-plus fa-fw"></i> Add Ticket
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/tickets'))->open() }}
					@include('layouts._partials._admin._event._tickets.form', ['empty' => true])
				</form>
			</div>
		</div>

	</div>
</div>

@endsection