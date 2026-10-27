@extends ('layouts.admin-default')

@section ('page_title', 'Seating Plans - ' . $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Seating Plans - {{ $seatingPlan->name }}</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/events/">Events</a>
			</li>
			<li>
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li>
				<a href="/admin/events/{{ $event->slug }}/seating">Seating Plans</a>
			</li>
			<li class="active">
				{{ $seatingPlan->name }}
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">
		
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-desktop fa-fw"></i> Seating Plan Preview
			</div>
			<div class="panel-body">
				<div class="table-responsive text-center">
					<table class="table">
						<thead>
							<tr>
								@foreach ($seatingPlan->headers as $header)
									<th class="text-center"><h4><strong>ROW {{ucwords($header)}}</strong></h4></th>
								@endforeach
							</tr>
						 </thead>
						<tbody>
							@for ($c = $seatingPlan->columns; $c >= 1; $c--)
								<tr>
									@foreach ($seatingPlan->getSeatsForColumn($c)->sortBy('seat') as $seat)
										<td>
											<button class="btn @if ($seat->disabled) btn-danger @elseif ($seat->eventParticipant) btn-success @else btn-primary @endif btn-sm"  onclick="editSeating('{{ $seat->seat }}', '{{ $seat->event_participant_id }}')" data-toggle="modal" data-target="#editSeatingModal">
												{{ $seat->seat }} - @if ($seat->eventParticipant) {{ $seat->eventParticipant->user->username }} @elseif ($seat->disabled) Disabled @else Empty @endif
											</button>
										</td>
									@endforeach
								</tr>
							@endfor
						</tbody>

					</table>
				</div>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-th fa-fw"></i> Seating Plan
			</div>
			<div class="panel-body">
			 	<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="seating_table">
						<thead>
							<tr>
								<th></th>
								<th>Seat Number</th>
								<th>User</th>
								<th>Name</th>
								<th>Ticket ID</th>
								<th>Purchase ID</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($seats as $seat)
								<tr class="odd gradeX">
									<td></td>
									<td>{{ ucwords($seat->seat) }}</td>
									<td>
										{{ $seat->eventParticipant->user->username }}
										@if ($steamProfile = Helpers::getSteamProfile($seat->eventParticipant->user->steamid))
											<br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
										@endif
									</td>
									<td>{{ $seat->eventParticipant->user->firstname }} {{ $seat->eventParticipant->user->surname }}</td>
									<td>
										@if(empty($seat->eventParticipant->ticket_id))
											Free
										@else
											{{ $seat->eventParticipant->ticket_id }}
										@endif
									</td>
									<td>
										@if(empty($seat->eventParticipant->purchase_id))
											Free
										@else
											{{ $seat->eventParticipant->purchase_id }}
										@endif
									</td>
									<td width="10%">
										<button type="button" class="btn btn-primary btn-sm btn-block" onclick="editSeating('{{ ucwords($seat->seat) }}', '{{ $seat->eventParticipant->id }}')" data-toggle="modal" data-target="#editSeatingModal">Edit</button>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $seats->links() }}
				</div>
			</div>
		</div>
	
	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating/' . $seatingPlan->slug))->acceptsFiles()->open() }}
					@if ($errors->any())
						<div class="alert alert-danger">
							<ul>
								@foreach ($errors->all() as $error)
									<li>{{ $error }}</li>
								@endforeach
							</ul>
						</div>
					@endif
					<div class="row">
						<div class="col-lg-12 col-sm-12 form-group">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name', $seatingPlan->name)->class('form-control') }}
						</div>
						<div class="col-lg-12 col-sm-12 form-group">
							{{ html()->label('Short Name', 'name_short') }}
							{{ html()->text('name_short', $seatingPlan->name_short)->class('form-control') }}
							<small>For display on Attendance Lists</small>
						</div>
					</div>
					<hr>
					<div class="row">
						<div class="col-lg-12 col-sm-12 form-group">
								{{ html()->label('Status', 'event_status') }}
								{{ 
									html()->select('status', array(
											'draft'=>'Draft',
											'preview'=>'Preview',
											'published'=>'Published',
										), strtolower($seatingPlan->status))->class('form-control')
								}}
						</div>
					</div>
					<div class="row">
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Columns', 'columns') }}
							{{ html()->text('columns', $seatingPlan->columns)->class('form-control') }}
						</div> 
						<div class="col-lg-6 col-sm-12 form-group">
							{{ html()->label('Rows', 'rows') }}
							{{ html()->text('rows', $seatingPlan->rows)->class('form-control') }}
						</div>
					</div>
					<div class="form-group">
						{{ html()->label('Seating Plan Image', 'image') }}
						{{ html()->file('image')->class('form-control') }}
					</div>
					<div class="form-group"> 
						<div class="checkbox"> 
							<label> 
								@if ($seatingPlan->locked) 
									{{ html()->checkbox('locked', true, 1)}} Lock Seating 
								@else 
									{{ html()->checkbox('locked', false, 1)}} Lock Seating 
								@endif 
							</label> 
						</div> 
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button>
					@if ($seatingPlan->hasMedia())
						<hr>
						<h4>Image Preview</h4>
						<img src="{{ $seatingPlan->getFirstMediaUrl() }}" alt="{{ $seatingPlan->name }} Layout" class="img img-responsive" />
					@endif
				</form>
			</div>
		</div>
	
	</div>
</div>

<!-- Modals -->
<div class="modal fade" id="editSeatingModal" tabindex="-1" role="dialog" aria-labelledby="editSeatingModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="editSeatingModalLabel">Edit Seating</h4>
			</div>
			<div class="modal-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating/' . $seatingPlan->slug . '/seat'))->open() }}
					<div class="form-group">
						{{ html()->label('Seat Number', 'seat_number_modal') }}
						{{ html()->text('seat_number_modal')->class('form-control') }}
					</div>
					<div class="form-group">
						{{ html()->label('Participant', 'participant_select_modal') }}
						{{ html()->select('participant_select_modal', $event->getParticipants())->class('form-control') }}
					</div> 
					{{ html()->hidden('participant_id_modal')->class('form-control') }}
					{{ html()->hidden('event_id_modal')->class('form-control') }}
					
					<a href="" id="participant_link">
						<button type="button" class="btn btn-default btn-block">Go to Participant</button>
					</a>
					<br>
					<button type="submit" class="btn btn-success btn-block">Save Changes</button>
				</form>
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating/' . $seatingPlan->slug . '/seat'))->id('clear_seat_form')->open() }}
					<hr>
					@method('DELETE')
					{{ html()->hidden('seat_number_clear') }}
					<button type="submit" class="btn btn-danger btn-block">Clear Seat</button>
				</form>
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/seating/' . $seatingPlan->slug . '/seat/disable'))->id('disable_seat_form')->open() }}
					<hr>
					{{ html()->hidden('seat_number_disable') }}
					<button type="submit" class="btn btn-danger btn-block">Enable/Disable Seat</button>
				</form>

			</div>
		</div>
	</div>
</div>

<!-- JavaScript-->
<script>
	function editSeating(seat, participant_id = null)
	{
		seat = seat.trim();
		$("#seat_number_modal").val(seat);
		$("#seat_number_clear").val(seat);
		$("#seat_number_disable").val(seat);
		var orginal_participant_id = $("#participant_id_modal").val();
		//Reset all inputs
		$("#seat_number_modal").prop('readonly', '');
		$("#participant_link").prop('href', '');
		$("#participant_link").hide();
		$("#clear_seat_form").hide();
		$("#participant_select_modal").val('');
		$("#participant_id_modal").val('');
		$('#participant_select_modal option[value="' + orginal_participant_id + '"]').attr("selected",false);
		if(participant_id != null){
			//we have a user - Fill in extra
			$("#seat_number_modal").prop('readonly', 'readonly');
			$("#participant_link").prop('href', '/admin/events/{{ $event->slug }}/participants/' + participant_id);
			$("#participant_link").show();
			$("#clear_seat_form").show();
			$('#participant_select_modal').val(participant_id);
		}
	}
</script>

@endsection
