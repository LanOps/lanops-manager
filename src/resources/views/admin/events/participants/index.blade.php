@extends ('layouts.admin-default')

@section ('page_title', 'Participants - ' . $event->display_name . ' | ' . config('app.name') . ' Admin')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Participants</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li class="breadcrumb-item active">
				Participants
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-12">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-users fa-fw"></i> All Participants
				<a href="/admin/events/{{ $event->slug }}/tickets#freebies" class="btn btn-info btn-sm float-end">Freebies</a>
			</div>
			<div class="card-body">
				@php
					$listUrl = '/admin/events/' . $event->slug . '/participants';
					$statusLinks = ['' => 'All (' . $total . ')', 'out' => 'Not signed in (' . ($total - $signedIn) . ')', 'in' => 'Signed in (' . $signedIn . ')'];
				@endphp
				<form method="GET" action="{{ $listUrl }}" class="mb-2" role="search">
					@if ($status !== '')
						<input type="hidden" name="status" value="{{ $status }}">
					@endif
					<div class="input-group">
						<span class="input-group-text"><i class="fa fa-search fa-fw"></i></span>
						<input type="search" name="search" class="form-control" value="{{ $search }}" autofocus
							placeholder="Search username, name, email, Discord or seat" aria-label="Search participants">
						<button type="submit" class="btn btn-primary">Search</button>
						@if ($search !== '')
							<a href="{{ $listUrl }}{{ $status !== '' ? '?status=' . $status : '' }}" class="btn btn-outline-secondary">Clear</a>
						@endif
					</div>
				</form>
				<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
					<div class="btn-group btn-group-sm" role="group" aria-label="Filter by sign in">
						@foreach ($statusLinks as $value => $label)
							<a href="{{ $listUrl }}?{{ http_build_query(array_filter(['search' => $search, 'status' => $value])) }}"
								@class(['btn', 'btn-secondary' => $status === $value, 'btn-outline-secondary' => $status !== $value])
								@if ($status === $value) aria-current="true" @endif>{{ $label }}</a>
						@endforeach
					</div>
					@if ($search !== '')
						<span class="text-muted small">{{ $participants->total() }} matching "{{ $search }}"</span>
					@endif
				</div>
				<div class="table-responsive">
					<table width="100%" class="table table-striped table-hover align-middle" id="seating_table">
						<thead>
							<tr>
								<th>User</th>
								<th class="d-none d-md-table-cell">Name</th>
								<th>Seat</th>
								<th class="d-none d-md-table-cell">Ticket</th>
								<th class="d-none d-xl-table-cell">Referral Code Used</th>
								<th class="d-none d-xl-table-cell">Referral Discount Redeemed</th>
								<th class="d-none d-lg-table-cell">Free/Staff/Gift</th>
								<th>Signed in</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							 {{-- One Steam request for the whole list, instead of one per row --}}
							 @php
							     Helpers::getSteamProfiles($participants->pluck('user.steamid')->all());
							 @endphp
							 @forelse ($participants as $participant)
								<tr>
									<td>
										{{ $participant->user->username }}
										<div class="d-md-none small text-muted">{{ $participant->user->firstname }} {{ $participant->user->surname }}</div>
										@if ($steamProfile = Helpers::getSteamProfile($participant->user->steamid))
											<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
										@endif
									</td>
									<td class="d-none d-md-table-cell">{{ $participant->user->firstname }} {{ $participant->user->surname }}</td>
									<td>
										@if (isset($participant->seat)) {{ $participant->seat->seat }} @endif
									</td>
									<td class="d-none d-md-table-cell">
										@if ($participant->free) Free @endif
										@if ($participant->ticket) {{ $participant->ticket->name }} @endif
									</td>
									<td class="d-none d-xl-table-cell">
										@if ($participant->purchase && $participant->purchase->referralUser)
											Yes
										@else
											No
										@endif
									</td>
									<td class="d-none d-xl-table-cell">
										@if ($participant->purchase && $participant->purchase->referral_discount_total > 0)
											Yes
										@else
											No
										@endif
									</td>
									<td class="d-none d-lg-table-cell">
										@if ($participant->free)
											<strong>Free</strong>
											<small>Assigned by: {{ $participant->getAssignedByUser()->username }}</small>
										@elseif ($participant->staff)
											<strong>Staff</strong>
											<small>Assigned by: {{ $participant->getAssignedByUser()->username }}</small>
										@elseif ($participant->gift)
											<strong>Gift</strong>
											<small>Assigned by: {{ $participant->getGiftedByUser()->username }}</small>
										@endif
									</td>
									<td style="width: 7rem">
										@if ($participant->signed_in)
											<span class="badge text-bg-success"><i class="fa fa-check fa-fw"></i> Signed in</span>
										@else
											{{ html()->form('POST', url($listUrl . '/' . $participant->id . '/signin'))->open() }}
												<button type="submit" class="btn btn-success btn-sm w-100">Sign in</button>
											</form>
										@endif
									</td>
									<td style="width: 4.5rem">
										<a href="{{ $listUrl }}/{{ $participant->id }}" class="btn btn-primary btn-sm w-100">Edit</a>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="9" class="text-center text-muted py-4">
										@if ($search !== '' || $status !== '')
											No participants match.
										@else
											No participants yet.
										@endif
									</td>
								</tr>
							@endforelse
						</tbody>
					</table>
				</div>
					{{ $participants->links() }}
			</div>
		</div>

	</div>
</div>

@endsection