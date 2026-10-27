@extends ('layouts.admin-default')

@section ('page_title', 'Events')

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Events</h3>
		<ol class="breadcrumb">
			<li class="active">
				Events
			</li>
		</ol>  
	</div>
</div>

<div class="row">
	<div class="col-lg-8">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-th-list fa-fw"></i> Events
			</div>
			<div class="panel-body">
				<div class="dataTable_wrapper">
					<table width="100%" class="table table-striped table-hover" id="dataTables-example">
						<thead>
							<tr>
								<th>Name</th>
								<th>Start</th>
								<th>End</th>
								<th>Short Description</th>
								<th>Capacity</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach ($events as $event)
								<tr class="table-row" class="odd gradeX">
									<td>{{ $event->display_name }}</td>
									<td>{{ date('d-m-y H:i', strtotime($event->start)) }}</td>
									<td>{{ date('d-m-y H:i', strtotime($event->end)) }}</td>
									<td>{{ $event->desc_short }}</td>
									<td class="center">{{ $event->capacity }} <small>Seats:{{ $event->getSeatingCapacity() }}</small></td>
									<td width="15%">
										<a href="/admin/events/{{ $event->slug }}"><button type="button" class="btn btn-primary btn-sm btn-block">Edit</button></a>
									</td>
									<td width="15%">
										{{ html()->form('POST', url('/admin/events/' . $event->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
											@method('DELETE')
											<button type="submit" class="btn btn-danger btn-sm btn-block">Delete</button>
										</form>
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
					{{ $events->links() }}
				</div>
			</div>
		</div>

	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Add Event
			</div>
			<div class="panel-body">
				@if ($errors->any())
					<div class="alert alert-danger">
						<ul>
							@foreach ($errors->all() as $error)
								<li>{{ $error }}</li>
							@endforeach
						</ul>
					</div>
				@endif
				<div class="list-group">
					{{ html()->form('POST', url('/admin/events/'))->open() }}
						<div class="form-group">
							{{ html()->label('Event Name', 'event_name') }}
							{{ html()->text('event_name', '')->class('form-control') }}
						</div>
						<div class="row">
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Start Date', 'start_date') }}
								{{ html()->text('start_date', '')->class('form-control') }}
							</div>
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Start  Time', 'start_time') }}
								{{ html()->text('start_time', '16:00')->class('form-control') }}
							</div>
						</div>
						<div class="row">
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('End Date', 'end_date') }}
								{{ html()->text('end_date', '')->class('form-control') }}
							</div>
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('End Time', 'end_time') }}
								{{ html()->text('end_time', '18:00')->class('form-control') }}
							</div>
						</div>
						<div class="form-group">
							{{ html()->label('Short Description', 'desc_short') }}
							{{ html()->text('desc_short', '')->class('form-control') }}
						</div>
						<div class="form-group">
							{{ html()->label('Long Description', 'desc_long') }}
							{{ html()->textarea('desc_long', '')->class('form-control')->rows('2') }}
						</div>
						<div class="row">
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Capacity', 'capacity') }}
								{{ html()->text('capacity', '')->class('form-control') }}
							</div>
							<div class="col-lg-6 col-sm-12 form-group">
								{{ html()->label('Venue', 'venue') }}
								{{ html()->select('venue', Helpers::getVenues())->class('form-control') }}
							</div>
						</div>
						<div class="checkbox">
							<label>
								{{ html()->checkbox('allow_spec', true, 'Y') }} Allow Spectators
							</label>
						</div>
						<button type="submit" class="btn btn-success btn-block">Submit</button>
					</form>
				</div>
			</div>
		</div>

	</div>
</div>

<!-- JavaScript-->
<script type="text/javascript">
	$( function() {
		$( "#start_date" ).datepicker();
		$( "#end_date" ).datepicker();
	});
</script>

@endsection
