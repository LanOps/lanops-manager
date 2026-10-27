@extends ('layouts.admin-default')

@section ('page_title', 'Timetables - ' . $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="page-header">Timetables - {{ $timetable->name }}</h3>
		<ol class="breadcrumb">
			<li>
				<a href="/admin/events/">Events</a>
			</li>
			<li>
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li>
				<a href="/admin/events/{{ $event->slug }}/timetables">Timetables</a>
			</li>
			<li class="active">
				{{ $timetable->name }}
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench fa-fw"></i> Timeslots
			</div>
			<div class="panel-body">
				<table width="100%" class="table table-striped table-hover" id="timetable_table">
					<thead>
						<tr>
							<th>Time</th>
							<th>Name</th>
							<th>Desc</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						@foreach ($timetable->data as $slot)
							<tr class="odd gradeX">
								<td>{{ date("D", strtotime($slot->start_time)) }} - {{ date("H:i", strtotime($slot->start_time)) }}</td>
								@if($slot->name == NULL && $slot->desc == NULL)
									<td>EMPTY</td>
									<td>EMPTY</td>
								@else
									<td>{{ $slot->name }}</td>
									<td>{{ $slot->desc }}</td>
								@endif
								<td width="10%">
									<button type="button" class="btn btn-primary btn-sm btn-block" data-toggle="modal" onclick="editTimeSlot('{{ $slot->id }}', '{{ $slot->start_time }}', '{{ $slot->name }}', '{{ $slot->desc }}')" data-target="#editTimeSlotModal">Edit</button>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>

	
	</div>
	<div class="col-lg-4">

		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-plus fa-fw"></i> Add New Slot
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug . '/data'))->open() }}
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
						<div class="form-group col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name')->class('form-control') }}
						</div> 
						 <div class="form-group col-lg-6">
							{{ html()->label('Start', 'start_time') }}
							{{ html()->select('start_time', $timetable->getAvailableTimes())->class('form-control') }}
						</div>
						<div class="form-group col-lg-12">
							{{ html()->label('Description', 'desc') }}
							{{ html()->text('desc')->class('form-control') }}
						</div> 
					</div>
					<button type="submit" class="btn btn-success btn-block">Submit</button>
				</form>
			</div>
		</div>
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="panel-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug))->open() }}
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
						<div class="form-group col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name', $timetable->name)->class('form-control') }}
						</div>
						<div class="form-group col-lg-6">
							{{ html()->label('Status', 'status') }}
							{{ 
								html()->select('status', array(
										'PUBLISHED'=>'Published',
										'DRAFT'=>'Draft'
									), strtoupper($timetable->status))->class('form-control')
							}}
						</div>
					</div>
					<div class="form-group">
						<div class="checkbox">
							<label>
								@if ($timetable->primary)
									{{ html()->checkbox('primary', true, 1)}} Primary Timetable
								@else
									{{ html()->checkbox('primary', false, 1)}} Primary Timetable
								@endif
							</label>
						</div>
					</div>  
					<button type="submit" class="btn btn-success btn-block">Submit</button>
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger btn-block">Delete</button>
				</form>
			</div>
		</div>
	
	</div>
</div>

<!-- Modals -->

<div class="modal fade" id="editTimeSlotModal" tabindex="-1" role="dialog" aria-labelledby="editTimeSlotModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title" id="editTimeSlotModal">Edit Time Slot</h4>
			</div>
			<div class="modal-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug . '/data'))->id('editTimeSlotForm')->open() }}
					<div class="row">
						<div class="form-group col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name')->id('editTimetableGame')->class('form-control') }}
						</div> 
						 <div class="form-group col-lg-6">
							{{ html()->label('Start', 'start_time') }}
							{{ html()->select('start_time', $timetable->getAvailableTimes())->id('editTimetableStart')->class('form-control') }}
						</div>
						<div class="form-group col-lg-12">
							{{ html()->label('Description', 'desc') }}
							{{ html()->text('desc')->id('editTimetableDesc')->class('form-control') }}
						</div> 
					</div>
					<button type="submit" class="btn btn-default">Submit</button>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	function editTimeSlot(id, timestamp, name, desc)
	{
		$("#editTimeSlotForm").prop('action', '/admin/events/{{ $event->slug }}/timetables/{{ $timetable->slug }}/data/' + id);
		$('#editTimetableStart').val(timestamp);
		$('#editTimetableGame').val(name);
		$('#editTimetableDesc').val(desc);
	}
</script>

@endsection
