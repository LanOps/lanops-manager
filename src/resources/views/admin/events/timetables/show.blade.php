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

@php
	$slotRows = old('slots');
	if (!is_array($slotRows)) {
		$slotRows = $timetable->data->map(fn ($slot) => [
			'id'   => $slot->id,
			'day'  => date('Y-m-d', strtotime($slot->start_time)),
			'time' => date('H:i', strtotime($slot->start_time)),
			'name' => $slot->name,
			'desc' => $slot->desc,
		])->all();
		if (empty($slotRows)) {
			$slotRows = array_fill(0, 3, ['day' => array_key_first($days)]);
		}
	}
	$slotUrl = url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug . '/slots');
@endphp
<div class="row">
	<div class="col-lg-8">

		<div class="card" data-slot-grid>
			<div class="card-header d-flex flex-wrap align-items-center gap-2">
				<span class="me-auto"><i class="fa fa-wrench fa-fw"></i> Timeslots</span>
				<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#slotPastePanel" aria-expanded="false" aria-controls="slotPastePanel">
					<i class="fa fa-paste fa-fw"></i> Paste from spreadsheet
				</button>
				@if ($copySources->isNotEmpty())
					<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#slotCopyPanel" aria-expanded="false" aria-controls="slotCopyPanel">
						<i class="fa fa-copy fa-fw"></i> Copy from another event
					</button>
				@endif
			</div>

			<div class="collapse" id="slotPastePanel">
				<div class="card-body border-bottom">
					<label for="slotPasteText" class="form-label">Paste rows from a spreadsheet</label>
					<textarea id="slotPasteText" class="form-control font-monospace small" rows="5" placeholder="Friday&#9;16:00&#9;LAN Start&#9;Network up"></textarea>
					<div class="form-text">
						Copy the cells straight from your spreadsheet. Columns are Day, Time, Name, Description, or include your header row and pick the columns below. A blank day carries on from the row above.
					</div>
					<div class="row g-2 mt-1 align-items-end d-none" data-paste-columns>
						<div class="col-sm-4">
							<label for="slotPasteName" class="form-label">Name from</label>
							<select id="slotPasteName" class="form-select form-select-sm"></select>
						</div>
						<div class="col-sm-4">
							<label for="slotPasteDesc" class="form-label">Description from</label>
							<select id="slotPasteDesc" class="form-select form-select-sm"></select>
						</div>
						<div class="col-sm-4 small text-body-secondary" data-paste-summary></div>
					</div>
					@if ($timetable->data->isNotEmpty())
						<div class="form-check mt-2">
							<input class="form-check-input" type="checkbox" id="slotPasteReplace" data-replace-existing>
							<label class="form-check-label" for="slotPasteReplace">Remove the slots already in this timetable</label>
						</div>
					@endif
					<button type="button" class="btn btn-primary btn-sm mt-2" data-paste-add disabled>
						<i class="fa fa-plus fa-fw"></i> Add these rows
					</button>
				</div>
			</div>

			@if ($copySources->isNotEmpty())
				<div class="collapse" id="slotCopyPanel">
					<div class="card-body border-bottom">
						<label for="slotCopySource" class="form-label">Copy slots from</label>
						<div class="d-flex flex-wrap gap-2">
							<select id="slotCopySource" class="form-select form-select-sm w-auto mw-100 flex-grow-1">
								@foreach ($copySources as $source)
									<option value="{{ $source['id'] }}">{{ $source['label'] }}</option>
								@endforeach
							</select>
							<button type="button" class="btn btn-primary btn-sm" data-copy-add>
								<i class="fa fa-plus fa-fw"></i> Add these slots
							</button>
						</div>
						@if ($timetable->data->isNotEmpty())
							<div class="form-check mt-2">
								<input class="form-check-input" type="checkbox" id="slotCopyReplace" data-replace-existing>
								<label class="form-check-label" for="slotCopyReplace">Remove the slots already in this timetable</label>
							</div>
						@endif
						<div class="form-text">Slots keep their weekday and time. Nothing is saved until you press Save timetable.</div>
					</div>
				</div>
				<script type="application/json" id="slotCopySources">@json($copySources)</script>
			@endif

			{{ html()->form('POST', $slotUrl)->id('slotGridForm')->open() }}
				<div class="card-body">
					@if ($errors->has('slots') || $errors->has('slots.*'))
						<div class="alert alert-danger">
							<ul class="mb-0">
								@foreach ($errors->get('slots*') as $messages)
									@foreach ((array) $messages as $message)
										<li>{{ $message }}</li>
									@endforeach
								@endforeach
							</ul>
						</div>
					@endif
					<div class="alert alert-info small py-2 d-none" data-slot-notice></div>
					<table class="table table-sm align-middle slot-grid mb-2">
						<thead>
							<tr>
								<th style="width: 9.5rem">Day</th>
								<th style="width: 7.5rem">Time</th>
								<th>Name</th>
								<th>Description</th>
								<th style="width: 3rem"><span class="visually-hidden">Remove</span></th>
							</tr>
						</thead>
						<tbody data-slot-rows>
							@foreach ($slotRows as $index => $row)
								@include ('admin.events.timetables._slot-row', ['index' => $index, 'row' => (array) $row])
							@endforeach
						</tbody>
					</table>
					<button type="button" class="btn btn-outline-secondary btn-sm" data-slot-add>
						<i class="fa fa-plus fa-fw"></i> Add row
					</button>
				</div>
				<div class="card-footer d-flex flex-wrap align-items-center gap-2">
					<span class="small text-body-secondary me-auto">Empty new rows are skipped. Rows you binned are removed when you save.</span>
					<button type="submit" class="btn btn-success">
						<i class="fa fa-save fa-fw"></i> Save timetable
					</button>
				</div>
			</form>
			<template id="slotRowTemplate">
				@include ('admin.events.timetables._slot-row', ['index' => '__INDEX__', 'row' => []])
			</template>
		</div>

	</div>
	<div class="col-lg-4">

		<div class="card">
			<div class="card-header">
				<i class="fa fa-wrench fa-fw"></i> Settings
			</div>
			<div class="card-body">
				{{ html()->form('POST', url('/admin/events/' . $event->slug . '/timetables/' . $timetable->slug))->open() }}
					@if ($errors->hasAny(['name', 'status', 'primary']))
					  	<div class="alert alert-danger">
					        <ul>
					          	@foreach (collect(['name', 'status', 'primary'])->flatMap(fn ($key) => $errors->get($key)) as $error)
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

@endsection
