@extends ('layouts.admin-default')

@section ('page_title', 'Timetables - ' . $event->display_name)

@section ('content')

<div class="row">
	<div class="col-lg-12">
		<h3 class="pb-2 mb-3 border-bottom">Timetables - {{ $timetable->name }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="/admin/events/">Events</a>
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}">{{ $event->display_name }}</a> 
			</li>
			<li class="breadcrumb-item">
				<a href="/admin/events/{{ $event->slug }}/timetables">Timetables</a>
			</li>
			<li class="breadcrumb-item active">
				{{ $timetable->name }}
			</li>
		</ol>
	</div>
</div>

@include ('layouts._partials._admin._event.dashMini')

<div class="row">
	<div class="col-lg-8">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Timeslots
			</div>
			<div class="card-body">
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
									<button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" onclick="editTimeSlot(@js($slot->id), @js($slot->start_time), @js($slot->name), @js($slot->desc))" data-bs-target="#editTimeSlotModal">Edit</button>
									{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug . '/data/' . $slot->id))->attribute('onsubmit', 'return ConfirmDelete()')->class('mt-1')->open() }}
										@method('DELETE')
										<button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
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
				<i class="fa fa-plus fa-fw"></i> Add New Slot
			</div>
			<div class="card-body">
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
						<div class="mb-3 col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name')->class('form-control') }}
						</div> 
						 <div class="mb-3 col-lg-6">
							{{ html()->label('Start', 'start_time') }}
							{{ html()->select('start_time', $timetable->getAvailableTimes())->class('form-select') }}
						</div>
						<div class="mb-3 col-lg-12">
							{{ html()->label('Description', 'desc') }}
							{{ html()->text('desc')->class('form-control') }}
						</div> 
					</div>
					<button type="submit" class="btn btn-success w-100">Submit</button>
				</form>
			</div>
		</div>
		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="card-body">
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
						<div class="mb-3 col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name', $timetable->name)->class('form-control') }}
						</div>
						<div class="mb-3 col-lg-6">
							{{ html()->label('Status', 'status') }}
							{{ 
								html()->select('status', array(
										'PUBLISHED'=>'Published',
										'DRAFT'=>'Draft'
									), strtoupper($timetable->status))->class('form-control')
							}}
						</div>
					</div>
					<div class="mb-3">
						<div class="form-check">
							<label class="form-check-label">
								@if ($timetable->primary)
									{{ html()->checkbox('primary', true, 1)->class('form-check-input') }} Primary Timetable
								@else
									{{ html()->checkbox('primary', false, 1)->class('form-check-input') }} Primary Timetable
								@endif
							</label>
						</div>
					</div>  
					<button type="submit" class="btn btn-success w-100">Submit</button>
				</form>
				<hr>
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug))->attribute('onsubmit', 'return ConfirmDelete()')->open() }}
					@method('DELETE')
					<button type="submit" class="btn btn-danger w-100">Delete</button>
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
				<h4 class="modal-title" id="editTimeSlotModal">Edit Time Slot</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug . '/data'))->id('editTimeSlotForm')->open() }}
					<div class="row">
						<div class="mb-3 col-lg-6">
							{{ html()->label('Name', 'name') }}
							{{ html()->text('name')->id('editTimetableGame')->class('form-control') }}
						</div> 
						 <div class="mb-3 col-lg-6">
							{{ html()->label('Start', 'start_time') }}
							{{ html()->select('start_time', $timetable->getAvailableTimes())->id('editTimetableStart')->class('form-select') }}
						</div>
						<div class="mb-3 col-lg-12">
							{{ html()->label('Description', 'desc') }}
							{{ html()->text('desc')->id('editTimetableDesc')->class('form-control') }}
						</div> 
					</div>
					<button type="submit" class="btn btn-outline-secondary">Submit</button>
				</form>
			</div>
		</div>
	</div>
</div>
<script>
	function editTimeSlot(id, timestamp, name, desc)
	{
		document.getElementById('editTimeSlotForm').action = '/admin/events/{{ $event->slug }}/timetables/{{ $timetable->slug }}/data/' + id;
		document.getElementById('editTimetableStart').value = timestamp;
		document.getElementById('editTimetableGame').value = name;
		document.getElementById('editTimetableDesc').value = desc;
	}
</script>

@endsection
